<?php

// src/Captcha.php

class Captcha {

    /**
     * Generates a random alphanumeric string for the CAPTCHA.
     *
     * @param int $length Length of the CAPTCHA text.
     * @return string The generated CAPTCHA text.
     */
    public static function generateCaptchaText(int $length = 6): string {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $char_count = strlen($characters);
        $random_string = '';
        for ($i = 0; $i < $length; $i++) {
            $random_string .= $characters[mt_rand(0, $char_count - 1)];
        }
        return $random_string;
    }

    /**
     * Creates a CAPTCHA image with the given text.
     * Outputs the image directly to the browser.
     *
     * @param string $text The CAPTCHA text.
     * @param string|null $font_path Path to the TTF font file. If null, uses a basic built-in font.
     */
    public static function createCaptchaImage(string $text, ?string $font_path = null): void {
        $width = defined('CAPTCHA_WIDTH') ? CAPTCHA_WIDTH : 150; // New config, was dynamic in Perl
        $height = defined('CAPTCHA_HEIGHT') ? CAPTCHA_HEIGHT : 30; // From config
        $font_size = $height * 0.6; // Relative font size

        $image = imagecreatetruecolor($width, $height);
        if (!$image) {
            // Handle image creation failure
            header("HTTP/1.1 500 Internal Server Error");
            echo "Failed to create CAPTCHA image.";
            return;
        }

        // Colors
        $bg_color = imagecolorallocate($image, rand(220, 255), rand(220, 255), rand(220, 255)); // Light background
        $text_color = imagecolorallocate($image, rand(0, 100), rand(0, 100), rand(0, 100));     // Dark text
        $noise_color1 = imagecolorallocate($image, rand(100, 200), rand(100, 200), rand(100, 200));
        $noise_color2 = imagecolorallocate($image, rand(100, 200), rand(100, 200), rand(100, 200));

        imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);

        // Add noise (lines) - similar to CAPTCHA_SCRIBBLE concept somewhat
        $num_lines = defined('CAPTCHA_NOISE_LINES') ? CAPTCHA_NOISE_LINES : 5;
        for ($i = 0; $i < $num_lines; $i++) {
            imageline(
                $image,
                mt_rand(0, $width), mt_rand(0, $height),
                mt_rand(0, $width), mt_rand(0, $height),
                (rand(0,1) ? $noise_color1 : $noise_color2)
            );
        }

        // Add noise (dots/pixels)
        $num_pixels = defined('CAPTCHA_NOISE_PIXELS') ? CAPTCHA_NOISE_PIXELS : 500;
        for ($i = 0; $i < $num_pixels; $i++) {
            imagesetpixel(
                $image,
                mt_rand(0, $width), mt_rand(0, $height),
                (rand(0,1) ? $noise_color1 : $noise_color2)
            );
        }


        // Draw text
        if ($font_path && file_exists($font_path)) {
            // Calculate text box to center it
            $textbox = imagettfbbox($font_size, 0, $font_path, $text);
            if ($textbox) {
                $x = ($width - ($textbox[4] - $textbox[0])) / 2;
                $y = ($height - ($textbox[5] - $textbox[1])) / 2; // $textbox[5] is baseline
                 imagettftext($image, $font_size, rand(-5, 5), (int)$x, (int)$y, $text_color, $font_path, $text);
            } else { // Fallback if imagettfbbox fails
                 imagestring($image, 5, 10, $height/4, $text, $text_color);
            }
        } else {
            // Fallback to built-in font if no TTF font provided or found
            // Adjust x, y for built-in font; it's less precise
            $font_gd = 5; // GD built-in font size (1-5)
            $text_width = imagefontwidth($font_gd) * strlen($text);
            $text_height = imagefontheight($font_gd);
            $x = ($width - $text_width) / 2;
            $y = ($height - $text_height) / 2;
            imagestring($image, $font_gd, (int)$x, (int)$y, $text, $text_color);
        }

        header('Content-Type: image/png');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        imagepng($image);
        imagedestroy($image);
    }

    /**
     * Stores the CAPTCHA data in the database.
     *
     * @param Database $db
     * @param string $ip User's IP address.
     * @param string $page_key Key for the page (e.g., 'mainpage' or 'resTHREAD_ID').
     * @param string $text CAPTCHA text.
     * @return bool True on success, false on failure.
     */
    public static function storeCaptcha(Database $db, string $ip, string $page_key, string $text): bool {
        if (!defined('SQL_TABLE_CAPTCHA')) return false;

        // Delete existing CAPTCHA for this IP and pagekey first, as per wakaba.pl's save_captcha_word
        $db->execute("DELETE FROM " . SQL_TABLE_CAPTCHA . " WHERE ip = ? AND pagekey = ?", [$ip, $page_key]);

        $timestamp = time();
        return $db->execute(
            "INSERT INTO " . SQL_TABLE_CAPTCHA . " (ip, pagekey, word, timestamp) VALUES (?, ?, ?, ?)",
            [$ip, $page_key, $text, $timestamp]
        ) > 0;
    }

    /**
     * Validates user input against stored CAPTCHA data.
     *
     * @param Database $db
     * @param string $ip User's IP address.
     * @param string $page_key Key for the page.
     * @param string $userInput User-provided CAPTCHA text.
     * @return bool True if valid, false otherwise.
     */
    public static function validateCaptcha(Database $db, string $ip, string $page_key, string $userInput): bool {
        if (!defined('SQL_TABLE_CAPTCHA') || !defined('CAPTCHA_LIFETIME')) return false;

        // Delete expired CAPTCHAs
        $min_time = time() - CAPTCHA_LIFETIME;
        $db->execute("DELETE FROM " . SQL_TABLE_CAPTCHA . " WHERE timestamp < ?", [$min_time]);

        // Check for a match (case-insensitive, as in wakaba.pl)
        // Note: SQLite's default LIKE is case-insensitive for ASCII. For true Unicode CI, use COLLATE NOCASE or LOWER().
        $sql = "SELECT word FROM " . SQL_TABLE_CAPTCHA . " WHERE ip = ? AND pagekey = ? AND LOWER(word) = LOWER(?)";
        if (defined('SQL_DBTYPE') && SQL_DBTYPE === 'pgsql') { // PostgreSQL is case-sensitive by default
            $sql = "SELECT word FROM " . SQL_TABLE_CAPTCHA . " WHERE ip = ? AND pagekey = ? AND word ILIKE ?";
        }

        $captcha_entry = $db->fetch($sql, [$ip, $page_key, $userInput]);

        if ($captcha_entry) {
            // Valid CAPTCHA, delete it
            $db->execute("DELETE FROM " . SQL_TABLE_CAPTCHA . " WHERE ip = ? AND pagekey = ? AND LOWER(word) = LOWER(?)", [$ip, $page_key, $userInput]);
            return true;
        }
        return false;
    }

    /**
     * Generates the page key for storing/retrieving CAPTCHA.
     * Mimics get_captcha_key from captcha.pl
     *
     * @param int|null $parent_id Thread ID if replying, null for new thread.
     * @return string The page key.
     */
    public static function getCaptchaPageKey(?int $parent_id): string {
        if ($parent_id && $parent_id > 0) {
            return 'res' . $parent_id;
        }
        return 'mainpage';
    }
}
