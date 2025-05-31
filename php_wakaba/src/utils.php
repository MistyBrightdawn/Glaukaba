<?php

/**
 * Redirects the user to a new location.
 *
 * @param string $location The URL to redirect to.
 * @param bool $alternate_method If true, use JavaScript/meta-refresh (not implemented yet).
 * @param string|null $postdata If provided, this would be used for POST redirects (not implemented yet).
 */
function make_http_forward(string $location, bool $alternate_method = false, ?string $postdata = null): void {
    if ($alternate_method || $postdata) {
        // For alternate method (JS/meta-refresh) or POST data, more complex handling is needed.
        // This is a placeholder for now.
        // In a real scenario, you might output HTML with JavaScript or a meta tag.
        if ($alternate_method) {
            echo "<!DOCTYPE html><html><head><title>Redirecting...</title>";
            echo "<meta http-equiv=\"refresh\" content=\"0;url=" . htmlspecialchars($location) . "\">";
            echo "<script type=\"text/javascript\">window.location.href = '" . addslashes($location) . "';</script>";
            echo "</head><body><p>Redirecting to <a href=\"" . htmlspecialchars($location) . "\">" . htmlspecialchars($location) . "</a>...</p></body></html>";
        } else {
            // Simple header redirect if no alternate method and no postdata
            // Note: True POST redirects typically require cURL or similar server-side requests,
            // or a form that auto-submits via JavaScript.
            // This basic function will just do a GET redirect.
            header("Location: " . $location);
        }
    } else {
        header("Location: " . $location);
    }
    exit;
}

/**
 * Cleans a string for display, converting special HTML characters.
 * Similar to Perl's clean_string, but using PHP's htmlspecialchars.
 *
 * @param string $string The input string.
 * @param bool $no_quote_replace If true, ENT_NOQUOTES is used.
 * @return string The cleaned string.
 */
function clean_string(string $string, bool $no_quote_replace = false): string {
    $flags = $no_quote_replace ? ENT_NOQUOTES : ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401;
    // Assuming CHARSET is defined globally from config.php
    $charset = defined('CHARSET') ? CHARSET : 'UTF-8';
    return htmlspecialchars($string, $flags, $charset);
}

/**
 * Decodes a string that was previously HTML-encoded for display.
 * Similar to Perl's decode_string, but using PHP's html_entity_decode.
 *
 * @param string $string The input string with HTML entities.
 * @param string|null $charset The character set to use (e.g., 'UTF-8'). Defaults to CHARSET constant or 'UTF-8'.
 * @param bool $is_filename Unused in this PHP version, kept for compatibility signature.
 * @return string The decoded string.
 */
function decode_string(string $string, ?string $charset = null, bool $is_filename = false): string {
    $charset = $charset ?: (defined('CHARSET') ? CHARSET : 'UTF-8');
    return html_entity_decode($string, ENT_QUOTES | ENT_HTML401, $charset);
}

// --- Functions needed for post handling ---

// Define a basic protocol regex, similar to $protocol_re in Perl
if (!defined('PROTOCOL_REGEX_PHP')) {
    define('PROTOCOL_REGEX_PHP', '/^(?:http|https|ftp|mailto|nntp):/i');
}


/**
 * Gets the user's IP address.
 * (Port of get_ip from wakautils.pl)
 */
function get_user_ip(): string {
    if (defined('USE_CLOUDFLARE') && USE_CLOUDFLARE && !empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/**
 * Converts an IP address string to a long integer.
 * (Port of dot_to_dec from wakautils.pl)
 * In PHP, ip2long already does this, but might return false or -1 for invalid IPs.
 */
function ip_to_long_php(string $ip_address): int {
    return ip2long($ip_address); // Returns false on failure, which might need handling. Perl's unpack is more direct.
                                 // unpack('N', pack('C4', explode('.', $ip_address))) is closer to Perl.
}
function long_to_ip_php(int $long_ip): string {
    return long2ip($long_ip);
}


/**
 * Placeholder for a generic error display function.
 * In wakaba.pl, make_error stops script execution.
 */
function make_generic_error(string $message): void {
    // In a real app, this would use a proper template or error handling system.
    // For now, just echo and exit.
    echo "<hr><p style='color:red; text-align:center; font-weight:bold;'>ERROR: " . htmlspecialchars($message) . "</p><hr>";
    // It's often useful to see where the error was triggered during development:
    // debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
    exit;
}

/**
 * Placeholder for RC4 encryption.
 * WARNING: RC4 is cryptographically insecure and should not be used for sensitive data.
 * This is provided for functional parity if direct porting of hide_data/process_tripcode is needed.
 * Consider using modern encryption like OpenSSL AES-256-CBC if security is a concern.
 */
function rc4_php(string $key, string $str): string {
    // This is a simplified placeholder. A full RC4 implementation is more complex.
    // For testing purposes, this might just XOR or return something predictable.
    // For now, just a dummy implementation.
    if (empty($key) || empty($str)) return $str;
    // return $str; // Effectively no encryption for placeholder

    // Basic XOR 'encryption' as a stand-in for RC4 for non-security critical tripcodes
    $out = '';
    $key_len = strlen($key);
    for ($i = 0; $i < strlen($str); $i++) {
        $out .= $str[$i] ^ $key[$i % $key_len];
    }
    return $out;
}

/**
 * Placeholder for hide_data from wakautils.pl
 * Uses a simplified RC4 for functional parity if needed for tripcodes.
 */
function hide_data_php(string $data, int $bytes, string $key_name, string $secret, bool $base64_encode = false): string {
    // Simplified key generation (not matching Perl's make_key which uses rc4 itself)
    $encryption_key = substr(hash('sha256', $key_name . $secret, true), 0, 16); // Use a more standard key derivation

    // Simplified "encryption" - this is NOT secure RC4.
    $encrypted_data = rc4_php($encryption_key, $data);
    $output = substr($encrypted_data, 0, $bytes);

    if ($base64_encode) {
        return base64_encode($output); // Standard base64
    }
    return $output;
}

/**
 * Processes a name string to extract a tripcode.
 * Placeholder for porting process_tripcode from wakautils.pl.
 */
function process_tripcode_php(string $name_field, string $tripkey_char, string $secret, string $charset): array {
    $name_part = $name_field;
    $tripcode = '';

    $tripkey_pos = strpos($name_part, $tripkey_char);

    if ($tripkey_pos !== false) {
        $actual_name = substr($name_part, 0, $tripkey_pos);
        $potential_trip = substr($name_part, $tripkey_pos + 1);

        // Secure trip: ##tripseed or #tripseed#secureseed
        if (substr($potential_trip, 0, 1) === $tripkey_char && strlen($potential_trip) > 1) { // Secure trip like ##seed
            $secure_seed = substr($potential_trip, 1);
            // IMPORTANT: hide_data_php uses a simplified RC4.
            // The original Perl used a specific RC4 for key generation and then another for data.
            // This will NOT produce compatible tripcodes with Perl wakaba without a full RC4 and matching key logic.
            $tripcode = $tripkey_char . $tripkey_char . hide_data_php($secure_seed, 8, "trip", $secret, true); // 8 bytes, base64
            $name_part = $actual_name;
        } elseif ($secret && preg_match('/^(.*?)(#)(.*)$/', $potential_trip, $matches_secure_legacy)) {
             // Legacy secure trip: #trip#key -> #trip + ##secure_part
             // This part is complex due to the dual trip part in original.
             // For simplicity, if # is found, assume it's a non-secure trip part for now.
             // $name_part = $actual_name;
             // $tripcode = generate_legacy_trip($potential_trip, $tripkey_char, $charset);
        }

        if (empty($tripcode) && !empty($potential_trip)) { // Non-secure trip
            // Original uses Shift_JIS encoding for trip part before crypt.
            // PHP's crypt() is different from Perl's. Salt generation is also different.
            // This will NOT generate compatible non-secure trips with Perl wakaba.
            // Using a simple hash for placeholder.
            $salt = substr(preg_replace('/[^\.-z]/', '.', $potential_trip . "H.."), 1, 2);
            // PHP's crypt behavior with 2-char salt depends on system (often DES-based)
            // $tripcode = $tripkey_char . substr(crypt($potential_trip, $salt), -10);
            // More portable approach:
            $tripcode = $tripkey_char . substr(md5($potential_trip . $salt), 0, 10); // Simple, non-compatible placeholder
            $name_part = $actual_name;
        }
    }

    // Final cleaning of name_part (decode_string is for entities, not full charset conversion here)
    $name_part = clean_string(decode_string($name_part, $charset));

    return [$name_part, $tripcode];
}

/**
 * Placeholder for format_comment from wakautils.pl
 * This is a very complex function if WakabaMark is fully implemented.
 * For now, a simplified version.
 */
function format_comment_php(string $comment): string {
    // 1. Basic HTML escaping (already done by clean_string on input)
    // $comment = htmlspecialchars($comment, ENT_QUOTES, CHARSET);
    // 2. Convert newlines to <br>
    $comment_processed = nl2br($comment, false); // false for not using XHTML <br />
    // 3. TODO: Implement WakabaMark (>>, >, links, spoiler tags, code tags, etc.)
    //    This is a major undertaking.
    //    For now, just basic line breaks and maybe URL linking.
    $comment_processed = preg_replace_callback(
        '/(https?:\/\/[^\s<>()"]+)/',
        function ($matches) {
            return "<a href='" . htmlspecialchars($matches[0]) . "' rel='nofollow'>" . htmlspecialchars($matches[0]) . "</a>";
        },
        $comment_processed
    );
    return $comment_processed;
}

/**
 * Placeholder for make_anonymous from wakaba.pl
 */
function make_anonymous_php(string $ip, int $timestamp): string {
    if (defined('SILLY_ANONYMOUS') && SILLY_ANONYMOUS) {
        // Logic for generating silly names based on IP and time.
        // This is complex and relies on cfg_expand and hide_data.
        // For now, return a simpler anonymous name.
        return (S_ANONAME ?? 'Anonymous') . substr(md5($ip . $timestamp . (SECRET ?? '')), 0, 4);
    }
    return S_ANONAME ?? 'Anonymous';
}

/**
 * Placeholder for make_id_code from wakaba.pl
 */
function make_id_code_php(string $ip, int $timestamp, string $email, int $parent_num, Database $db): string {
    // Logic for ID generation (DISPLAY_ID setting)
    // Can be based on IP, date, board, masked IP, etc.
    // Relies on hide_data, mask_ip.
    if (defined('DISPLAY_ID') && DISPLAY_ID) {
        if (DISPLAY_ID === 'mask') { // Example
            // return mask_ip_php($ip, make_key_php("mask", SECRET, 32));
            return 'ID:' . substr(md5($ip . (SECRET ?? '') . date('Y-m-d')), 0, 6); // Simplified
        }
    }
    return '';
}

/**
 * Placeholder for make_cookies from wakautils.pl
 */
function make_cookies_php(array $cookies_data): void {
    $charset = $cookies_data['-charset'] ?? (defined('CHARSET') ? CHARSET : 'UTF-8');
    $expires_offset = $cookies_data['-expires'] ?? (14 * 24 * 3600); // Default 14 days
    $autopath_mode = $cookies_data['-autopath'] ?? (defined('COOKIE_PATH') ? COOKIE_PATH : 'root');
    $path_override = $cookies_data['-path'] ?? null;

    $expires = 0; // Session cookie by default
    if (is_numeric($expires_offset)) {
        $expires = time() + $expires_offset;
    } elseif (strtolower($expires_offset) !== 'session') {
        // Parse date string if needed, or specific handling for "Thu, 01-Jan-1970 00:00:01 GMT" for deletion
        $parsed_expires = strtotime($expires_offset);
        if ($parsed_expires !== false) $expires = $parsed_expires;
    }


    $path = '/'; // Default root path
    if ($path_override) {
        $path = $path_override;
    } else {
        $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
        if ($autopath_mode === 'current') {
            $path = dirname($script_name) . '/';
        } elseif ($autopath_mode === 'parent') {
            $path = dirname(dirname($script_name)) . '/';
        }
        // Ensure path is clean (e.g. replace multiple slashes)
        $path = preg_replace('~/{2,}~', '/', $path);
    }


    foreach ($cookies_data as $name => $value) {
        if (strpos($name, '-') === 0) continue; // Skip option keys like '-charset'

        // TODO: Implement cookie_encode_php for proper value encoding if complex characters are expected
        // For now, raw value.
        setcookie($name, $value, [
            'expires' => $expires,
            'path' => $path,
            'domain' => '', // Current domain
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax' // Or 'Strict' or 'None' (None requires Secure)
        ]);
    }
}

// Placeholder for functions that would normally be in a dedicated security.php or similar
function check_password_php(string $admin_pass_form, Database $db): bool { /* Placeholder */ return false; }
function is_whitelisted_php(int $num_ip, Database $db): bool { /* Placeholder */ return false; }
function ban_check_php(int $num_ip, string $name, string $subject, string $comment, Database $db): bool { /* Placeholder - true if check passes, false if banned */ return true;}
function spam_engine_php(array $post_data, Database $db): bool { /* Placeholder */ return true; } // True if passes
function is_trusted_php(string $tripcode, Database $db): bool { /* Placeholder */ return false; }
function check_captcha_php(string $captcha_input, string $ip, int $parent_num, Database $db): bool { /* Placeholder */ return true; } // True if passes
function proxy_check_php(string $ip, Database $db): bool { /* Placeholder */ return true; } // True if passes
function flood_check_php(int $num_ip, int $timestamp, string $comment, bool $has_file, Database $db): bool { /* Placeholder */ return true; } // True if passes
function trim_database_php(Database $db): void { /* Placeholder */ }
function build_thread_cache_php(int $thread_id, Database $db): void { /* Placeholder */ }

/**
 * Fetches a single post from the database.
 * Used for >>quote linking and other features.
 *
 * @param Database $db The database instance.
 * @param int $post_num The post number to fetch.
 * @param string|null $board_dir The board directory for cross-board linking (not fully implemented).
 * @return array|false The post data as an associative array, or false if not found.
 */
function get_post_php(Database $db, int $post_num, ?string $board_dir = null) {
    // For now, ignore $board_dir and assume current board context
    if ($board_dir !== null && $board_dir !== (defined('BOARD_DIR') ? BOARD_DIR : '')) {
        // Cross-board linking logic would go here.
        // This might involve different table names or even different DB connections
        // depending on the multi-board setup. For simplicity, return false.
        return false;
    }

    if (!defined('SQL_TABLE_POSTS')) {
        return false; // Should not happen if config is loaded
    }

    return $db->fetch("SELECT num, parent, subject FROM " . SQL_TABLE_POSTS . " WHERE num = ?", [$post_num]);
}


/**
 * Default handler for >>quote links used by Formatting::format_comment_wakabamark_style.
 *
 * @param Database $db The database instance.
 * @param string $post_num_str The post number as a string.
 * @param string|null $board_dir_str Optional board directory for cross-board links.
 * @return string HTML string for the quote link or original text if post not found.
 */
function quote_link_handler_function(Database $db, string $post_num_str, ?string $board_dir_str = null): string {
    $post_num = intval($post_num_str);
    if ($post_num <= 0) {
        return "&gt;&gt;" . htmlspecialchars($post_num_str); // Invalid post number format
    }

    $post_data = get_post_php($db, $post_num, $board_dir_str);

    $quote_text_display = $board_dir_str ? "&gt;&gt;&gt;/" . htmlspecialchars($board_dir_str) . "/" . $post_num : "&gt;&gt;" . $post_num;

    if ($post_data) {
        $link_href = get_reply_link($post_data['num'], $post_data['parent']); // from template_helpers.php
        // TODO: Add onclick="highlight(...)" if JS function is available
        return "<a href=\"" . htmlspecialchars($link_href) . "\" class=\"postlink\">" . $quote_text_display . "</a>";
    } else {
        return "<span class=\"quote\">" . $quote_text_display . "</span>";
    }
}


?>
