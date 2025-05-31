<?php

// Template Helper Functions

/**
 * Formats a Unix timestamp into a date string.
 * Mimics Perl's make_date($timestamp, $style).
 *
 * @param int|null $timestamp Unix timestamp.
 * @param string|null $style Date style ('futaba', '2ch', 'localtime', 'tiny').
 * @return string Formatted date string.
 */
function make_date(?int $timestamp, ?string $style = null): string {
    if ($timestamp === null) {
        return '';
    }

    $style = $style ?? (defined('DATE_STYLE') ? DATE_STYLE : 'futaba');

    switch (strtolower($style)) {
        case '2ch':
            // Weekday (Japanese) D(ddd) H:M:S.ms
            // This is complex due to Japanese weekdays and milliseconds.
            // PHP's strftime for Japanese day names is locale-dependent.
            // For simplicity, using English day names for now.
            // Getting milliseconds in PHP: $milliseconds = floor(microtime(true) * 1000) % 1000;
            // However, $timestamp doesn't have ms.
            return strftime('%Y/%m/%d(%a) %H:%M:%S', $timestamp); // Simplified
        case 'tiny':
            return strftime('%y/%m/%d %H:%M', $timestamp);
        case 'localtime':
            return strftime('%c', $timestamp); // Locale's appropriate date and time representation
        case 'futaba':
        default:
            // YY/MM/DD(Day)HH:MM(:SS optional)
            // Example: 06/01/01(Sat)00:00
            // If timestamp is very recent (e.g. within 24 hours), S_NOWTimeString can be used.
            // This simplified version does not implement S_NOWTimeString.
            return strftime('%y/%m/%d(%a)%H:%M', $timestamp);
            // To add seconds: strftime('%y/%m/%d(%a)%H:%M:%S', $timestamp);
    }
}

/**
 * Extracts the filename from a full path.
 *
 * @param string|null $path The full path.
 * @return string The filename.
 */
function get_filename(?string $path): string {
    if ($path === null) {
        return '';
    }
    return basename($path);
}

/**
 * Expands a filename to its full path or URL if necessary.
 * For now, this is a simplified version. In Wakaba, this handles
 * IMG_DIR, THUMB_DIR, RES_DIR constants and also checks for file existence.
 * Given that PHP will run from public/index.php, paths need care.
 *
 * @param string|null $filename The filename or relative path component.
 * @return string The potentially expanded path.
 */
function expand_filename(?string $filename): string {
    if ($filename === null) {
        return '';
    }

    // This is a very basic implementation. A full one would check if $filename
    // is already a full URL, or prepend appropriate base paths/URLs from config.
    // For example, if $filename is 'favicon.ico', it should become '/favicon.ico' or similar.
    // If $filename is from THUMB_DIR like '1234s.jpg', it might become '/uploads/thumb/1234s.jpg'.

    // Check if it's likely a path relative to the web root (e.g. images, css)
    // This is a heuristic and might need to be more robust.
    if (strpos($filename, '/') === 0 || preg_match('#^https?://#i', $filename)) {
        return $filename; // Already a root-relative or absolute URL
    }

    // Specific known locations based on constants
    // These paths are relative to where index.php is (public/)
    // and how they might be accessed from the web.
    if (defined('THUMB_DIR') && strpos($filename, basename(THUMB_DIR)) !== false) {
         // e.g. $filename = "thumb/123s.jpg" -> should be web path to thumbs
         // This needs to align with how THUMB_DIR is defined (e.g. '../uploads/thumb/' from config)
         // and how web server maps it.
         // Assuming THUMB_DIR is like '../uploads/thumb/' (relative to config.php or src/)
         // and web root is 'public/', then path from web is 'uploads/thumb/'
        return 'uploads/thumb/' . basename($filename); // Simplified
    }
    if (defined('IMG_DIR') && strpos($filename, basename(IMG_DIR)) !== false) {
        return 'uploads/src/' . basename($filename); // Simplified
    }
    if (defined('RES_DIR') && strpos($filename, basename(RES_DIR)) !== false && strpos($filename, '.html') !== false) {
         // e.g. res/123.html -> this is usually a generated static file.
         // In PHP, we might link to index.php?task=view_thread&num=123
         // For now, assume it's a direct file path if static files are generated.
        return 'res/' . basename($filename); // Simplified, assumes res/ is in public/
    }


    // Default for things like 'favicon.ico' or CSS files if not handled above
    // This assumes they are at the root or in a known public path.
    // For favicon.ico, it's usually at the web root.
    if ($filename === FAVICON) {
        return '/' . $filename;
    }

    // If it's a CSS file from CSS_DIR (which is usually relative to domain root)
    if (defined('CSS_DIR') && strpos($filename, '.css') !== false) {
        // CSS_DIR is often like '/css/', so $filename might be 'futaba.css'
        // This part is tricky because CSS_DIR in Perl might be an absolute web path.
        // Assuming CSS_DIR in config.php is set like '/css/' or 'css/'
        $css_base = rtrim(CSS_DIR, '/');
        return $css_base . '/' . basename($filename);
    }

    return $filename; // Fallback
}


/**
 * Truncates a line of text if it's too long.
 * Very basic implementation. Wakaba's abbreviate_html is more complex.
 *
 * @param string|null $text The text to truncate.
 * @param int $max_length Maximum length.
 * @return string The (potentially) truncated text.
 */
function truncate_line(?string $text, int $max_length = 100): string {
    if ($text === null) {
        return '';
    }
    if (mb_strlen($text) > $max_length) {
        return mb_substr($text, 0, $max_length) . '...';
    }
    return $text;
}

/**
 * A simple HTML abbreviator.
 * This is a placeholder for the more complex abbreviate_html from Wakaba.
 * It focuses on line count and overall length.
 *
 * @param string|null $html The HTML string to abbreviate.
 * @param int $max_lines Max number of lines to show. From MAX_LINES_SHOWN.
 * @param int $approx_line_length Approx chars per line. From APPROX_LINE_LENGTH.
 * @return string|null The abbreviated HTML, or null if no abbreviation needed.
 */
function abbreviate_html_simple(?string $html, int $max_lines, int $approx_line_length): ?string {
    if ($html === null || $html === '') { // Handle empty string explicitly
        return null;
    }

    // Normalize newlines before splitting, handle <br /> and <br>
    $html = preg_replace('/\r\n?/', "\n", $html);
    $lines = preg_split('/<br\s*\/?>/i', $html);

    $line_count = count($lines);
    $abbreviated_by_lines = false;
    $output_lines = [];

    if ($line_count > $max_lines) {
        $abbreviated_by_lines = true;
        $lines_to_show = array_slice($lines, 0, $max_lines);
    } else {
        $lines_to_show = $lines;
    }

    $total_chars_in_shown_lines = 0;
    foreach($lines_to_show as $line) {
        $output_lines[] = $line;
        // crude approximation of visible characters
        $total_chars_in_shown_lines += mb_strlen(strip_tags($line));
    }

    $result_html = implode("<br>", $output_lines);

    // Did we actually truncate due to line limits?
    if ($abbreviated_by_lines) {
        return $result_html . "<br>"; // Add trailing <br> as per original logic for abbreviation
    }

    // Further check: even if line count is OK, is total content too long?
    // This is a very rough heuristic compared to original wakabamark.
    // APPROX_LINE_LENGTH * $max_lines gives a rough estimate of max desired characters.
    if ($total_chars_in_shown_lines > ($approx_line_length * $max_lines * 1.2)) { // 1.2 is a fudge factor
        // If it's too long by character count even if not by lines,
        // the original would return an abbreviated version.
        // This simple version doesn't re-truncate based on chars if lines were ok.
        // However, if ANY abbreviation happened (e.g. by lines), it should return the content.
        // For simplicity, if we didn't abbreviate by lines, we assume it's not abbreviated by length either
        // unless we implement a character-based truncation here.
        // The original returns null if no abbreviation happens.
        // If $abbreviated_by_lines is false, and we don't do char-based truncation, return null.
        // This part needs to be carefully considered if exact replication of abbreviation is needed.
        // For now, only line-based abbreviation is effectively handled for returning non-null.
        return null;
    }

    // If no changes were made (neither by line count nor by character count - though char count is weak here)
    if (!$abbreviated_by_lines && $line_count == count($output_lines) && $html == $result_html) {
         return null;
    }

    }

    // If we reach here, it means no abbreviation was deemed necessary by the current logic
    return null;
}


/**
 * Generates a reply link.
 *
 * @param int $post_num The post number.
 * @param int $parent_num The parent post number (0 if OP).
 * @param bool $is_admin_link If true, generate admin panel link (not implemented yet).
 * @return string The generated URL.
 */
function get_reply_link(int $post_num, int $parent_num, bool $is_admin_link = false): string {
    // TODO: Handle $is_admin_link if needed for admin panel specific links
    $base = defined('HTML_SELF') ? HTML_SELF : 'index.php'; // HTML_SELF should be 'index.php'

    // REWRITTEN_URLS affects link structure significantly.
    // This is a simplified version assuming non-rewritten URLs for task-based routing.
    // For a reply in a thread view: res/parent_num.html#post_num
    // For an OP link from index: res/op_num.html
    // With task-based routing, it might be index.php?task=view_thread&num=PARENT#pPOSTNUM

    if (defined('REWRITTEN_URLS') && REWRITTEN_URLS) {
        $res_dir = defined('RES_DIR') ? basename(RES_DIR) : 'res'; // e.g. 'res'
        $page_ext = defined('PAGE_EXT') ? PAGE_EXT : '.html';
        if ($parent_num) {
            return $res_dir . '/' . $parent_num . $page_ext . '#p' . $post_num;
        } else {
            return $res_dir . '/' . $post_num . $page_ext;
        }
    } else {
        // Task-based routing for non-rewritten URLs
        if ($parent_num) { // It's a reply, link to its parent thread page and anchor to the post
            return $base . '?task=view_thread&num=' . $parent_num . '#p' . $post_num;
        } else { // It's an OP, link to its own thread page
            return $base . '?task=view_thread&num=' . $post_num;
        }
    }
}


/**
 * Renders a PHP template file with the given data.
 *
 * @param string $template_path Path to the template file (relative to WAKABA_BASE_DIR).
 * @param array $data Associative array of data to extract into the template's scope.
 * @return string The rendered HTML.
 * @throws Exception if template file not found.
 */
function render_template(string $template_path, array $data = []): string {
    $full_template_path = WAKABA_BASE_DIR . '/' . ltrim($template_path, '/');

    if (!file_exists($full_template_path)) { // Check existence of the template file
        error_log("Template file not found: " . $full_template_path); // Log the error
        throw new Exception("Template file not found: {$full_template_path}. Base dir: " . WAKABA_BASE_DIR . ", Template path: " . $template_path);
    }

    // Make data available as variables in the template's scope
    extract($data);

    ob_start();
    include $full_template_path;
    $rendered_html = ob_get_clean();

    return $rendered_html;
}

?>
