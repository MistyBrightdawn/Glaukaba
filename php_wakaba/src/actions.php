<?php

// Placeholder action functions for php_wakaba
// Each function would eventually handle the logic for a specific 'task'

if (!class_exists('Database')) {
    // This is a safeguard. Ideally, Database class is loaded by index.php before actions.php
    if (file_exists(WAKABA_BASE_DIR . '/src/Database.php')) {
        require_once WAKABA_BASE_DIR . '/src/Database.php';
    } else {
        // Fallback if WAKABA_BASE_DIR is not set correctly for some reason during direct script access (should not happen in normal flow)
        require_once dirname(__FILE__) . '/Database.php';
    }
}
if (!class_exists('Formatting')) { // Ensure Formatting class is available
    if (file_exists(WAKABA_BASE_DIR . '/src/Formatting.php')) {
        require_once WAKABA_BASE_DIR . '/src/Formatting.php';
    } else {
        require_once dirname(__FILE__) . '/Formatting.php';
    }
}


// Default action if no specific task is matched or for the main page view
function handle_show_main_page(Database $db): void {
    // This function will now build and echo the main page HTML
    echo build_main_page_html($db);
}

// --- Admin Login Actions ---

/**
 * Displays the admin login form.
 *
 * @param Database $db The database instance (unused for now, but good practice for consistency).
 * @param string|null $error_message Optional error message to display on the form.
 */
function handle_admin_show_login_form_request(Database $db, ?string $error_message = null): void {
    $template_data = [
        'page_title' => 'Admin Login',
        'error_message' => $error_message,
        // Add any other data needed by admin_login_form.php or its includes
    ];
    // Assuming WAKABA_BASE_DIR is defined, otherwise adjust path.
    // Ensure template_helpers.php (for render_template) is included in index.php
    echo render_template('templates/admin/admin_login_form.php', $template_data);
}

/**
 * Processes the admin login form submission.
 *
 * @param Database $db The database instance.
 * @param array $post_data Form data from $_POST.
 */
function handle_admin_perform_login_request(Database $db, array $post_data): void {
    $username = $post_data['user'] ?? '';
    $password = $post_data['berra'] ?? ''; // 'berra' is the password field name in wakaba.pl

    if (empty($username) || empty($password)) {
        handle_admin_show_login_form_request($db, 'Username and password are required.');
        return;
    }

    $user_row = $db->fetch("SELECT * FROM " . SQL_TABLE_USERS . " WHERE user = ?", [$username]);

    if (!$user_row) {
        handle_admin_show_login_form_request($db, 'Invalid username or password.');
        return;
    }

    // Password Verification (Simplified - NOT FOR PRODUCTION)
    // Option A: Plaintext comparison (highly insecure, for initial porting/testing only)
    $password_valid = ($password === $user_row['pass']);

    // Option B: PHP's password_verify (if passwords were hashed with password_hash)
    // $password_valid = password_verify($password, $user_row['pass']);

    // Option C: Mimic Perl's crypt() - Complex and generally not advised for new PHP code.
    // For now, we use simplified check. If migrating, a password reset strategy is better.

    if (!$password_valid) {
        // Log failed login attempt (placeholder)
        // log_action_php('admin_login_fail', $username);
        handle_admin_show_login_form_request($db, 'Invalid username or password.');
        return;
    }

    // Login successful - Start PHP session
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['admin_user_id'] = $user_row['num'];
    $_SESSION['admin_username'] = $user_row['user'];
    $_SESSION['admin_class'] = $user_row['class'];
    $_SESSION['admin_logged_in_at'] = time();

    // Regenerate session ID for security
    session_regenerate_id(true);

    // Update last login stats
    $current_ip_long = ip_to_long_php(get_user_ip());
    $db->execute("UPDATE " . SQL_TABLE_USERS . " SET lastdate = ?, lastip = ? WHERE num = ?", [time(), $current_ip_long, $user_row['num']]);

    // Log successful login (placeholder)
    // log_action_php('admin_login_success', $username);

    // Redirect to admin panel
    $mpanel_url = (defined('HTML_SELF') ? HTML_SELF : 'index.php') . '?task=mpanel';
    // To mimic wakaba.pl's admin token in URL (though session is now used):
    // $admin_token_dummy = md5($user_row['user'] . $user_row['pass']); // Dummy token
    // $mpanel_url .= '&admin=' . urlencode($admin_token_dummy);
    make_http_forward($mpanel_url);
}


// --- Admin Ban Panel Actions ---

/**
 * Displays the ban management panel.
 */
function handle_admin_ban_panel_request(Database $db): void {
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['admin_user_id'])) {
        make_http_forward((defined('HTML_SELF') ? HTML_SELF : 'index.php') . '?task=admin');
        return;
    }
    $admin_session = $_SESSION;
    if ($admin_session['admin_class'] === 'janitor') { // Or use a more sophisticated permission system
        make_generic_error("You do not have permission to access the ban panel.");
        return;
    }

    $bans = $db->fetchAll("SELECT * FROM " . SQL_TABLE_ADMIN . " ORDER BY type ASC, num ASC");

    $template_data = [
        'page_title' => 'Ban Management',
        'admin_session' => $admin_session,
        'self_url' => defined('HTML_SELF') ? HTML_SELF : 'index.php',
        'admin_link_param' => '', // Add admin token if using URL tokens
        'bans' => $bans,
        // 'stylesheets' => ...
    ];
    echo render_template('templates/admin/ban_panel.php', $template_data);
}

/**
 * Adds an IP ban.
 */
function handle_admin_add_ip_ban_request(Database $db, array $post_data): void {
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['admin_user_id'])) {
        make_http_forward((defined('HTML_SELF') ? HTML_SELF : 'index.php') . '?task=admin');
        return;
    }
    $admin_session = $_SESSION;
    if ($admin_session['admin_class'] === 'janitor') {
        make_generic_error("You do not have permission to add bans.");
        return;
    }

    $ip_str = $post_data['ip'] ?? '';
    $mask_str = $post_data['mask'] ?? '255.255.255.255'; // Default to single IP
    $comment = $post_data['comment'] ?? '';
    $duration_days = !empty($post_data['duration_days']) ? intval($post_data['duration_days']) : 0;
    $is_permanent = isset($post_data['permanent']) && $post_data['permanent'] == '1';

    if (empty($ip_str) || filter_var($ip_str, FILTER_VALIDATE_IP) === false) {
        make_generic_error("Invalid IP address provided.");
        return;
    }
    // Basic mask validation (is it an IP or CIDR?)
    if (strpos($mask_str, '/') === 0) { // CIDR like /24
        $cidr = intval(substr($mask_str, 1));
        if ($cidr < 0 || $cidr > 32) {
            make_generic_error("Invalid CIDR mask."); return;
        }
        $mask_long = ~((1 << (32 - $cidr)) - 1);
    } elseif (filter_var($mask_str, FILTER_VALIDATE_IP) !== false) {
        $mask_long = ip_to_long_php($mask_str);
    } else {
        make_generic_error("Invalid mask provided."); return;
    }

    $ip_long = ip_to_long_php($ip_str);

    $current_time = time();
    $duration_seconds = 0;
    if (!$is_permanent && $duration_days > 0) {
        $duration_seconds = $current_time + ($duration_days * 24 * 60 * 60);
    }

    $insert_data = [
        'type' => 'ipban',
        'comment' => clean_string($comment),
        'private' => '', // Placeholder for private ban notes
        'ival1' => $ip_long, // Store as long
        'ival2' => $mask_long, // Store as long
        'sval1' => '', // Not used for IP bans
        'fromuser' => $admin_session['admin_username'],
        'publicfromuser' => '', // Placeholder
        'duration' => $duration_seconds,
        'perm' => $is_permanent ? 1 : 0,
        'scope' => 'global', // Placeholder
        'postnum' => null,   // Placeholder
        'board' => defined('BOARD_DIR') ? BOARD_DIR : '', // Placeholder
        'warning' => 0,      // Placeholder for warning type
        'timestamp' => $current_time,
        'active' => 1
    ];

    $db->execute(
        "INSERT INTO " . SQL_TABLE_ADMIN . " (type, comment, private, ival1, ival2, sval1, fromuser, publicfromuser, duration, perm, scope, postnum, board, warning, timestamp, active) VALUES (:type, :comment, :private, :ival1, :ival2, :sval1, :fromuser, :publicfromuser, :duration, :perm, :scope, :postnum, :board, :warning, :timestamp, :active)",
        $insert_data
    );

    log_action_php('admin_add_ip_ban', $ip_str . '/' . $mask_str, $admin_session['admin_username'], $db);
    make_http_forward((defined('HTML_SELF') ? HTML_SELF : 'index.php') . '?task=admin_ban_panel');
}

/**
 * Removes a ban entry.
 */
function handle_admin_remove_ban_request(Database $db, array $get_data): void {
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['admin_user_id'])) {
        make_http_forward((defined('HTML_SELF') ? HTML_SELF : 'index.php') . '?task=admin');
        return;
    }
     $admin_session = $_SESSION;
    if ($admin_session['admin_class'] === 'janitor') { // Example permission
        make_generic_error("You do not have permission to remove bans.");
        return;
    }

    $ban_num = $get_data['num'] ?? null;
    if (!$ban_num || !is_numeric($ban_num)) {
        make_generic_error("Invalid ban number specified.");
        return;
    }

    $db->execute("DELETE FROM " . SQL_TABLE_ADMIN . " WHERE num = ?", [$ban_num]);
    log_action_php('admin_remove_ban', "Ban #".$ban_num, $admin_session['admin_username'], $db);
    make_http_forward((defined('HTML_SELF') ? HTML_SELF : 'index.php') . '?task=admin_ban_panel');
}

/**
 * Activates or deactivates a ban.
 */
function handle_admin_toggle_ban_status_request(Database $db, array $get_data, bool $activate): void {
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['admin_user_id'])) {
        make_http_forward((defined('HTML_SELF') ? HTML_SELF : 'index.php') . '?task=admin');
        return;
    }
    $admin_session = $_SESSION;
     if ($admin_session['admin_class'] === 'janitor') {
        make_generic_error("You do not have permission to change ban status.");
        return;
    }

    $ban_num = $get_data['num'] ?? null;
    if (!$ban_num || !is_numeric($ban_num)) {
        make_generic_error("Invalid ban number specified.");
        return;
    }

    $new_status = $activate ? 1 : 0;
    $db->execute("UPDATE " . SQL_TABLE_ADMIN . " SET active = ? WHERE num = ?", [$new_status, $ban_num]);
    log_action_php($activate ? 'admin_activate_ban' : 'admin_deactivate_ban', "Ban #".$ban_num, $admin_session['admin_username'], $db);
    make_http_forward((defined('HTML_SELF') ? HTML_SELF : 'index.php') . '?task=admin_ban_panel');
}


// --- Admin Edit Post Actions ---

/**
 * Displays the form for editing a post.
 *
 * @param Database $db The database instance.
 * @param array $get_data Data from $_GET (expecting 'num').
 */
function handle_admin_edit_post_form_request(Database $db, array $get_data): void {
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['admin_user_id'])) {
        make_http_forward((defined('HTML_SELF') ? HTML_SELF : 'index.php') . '?task=admin');
        return;
    }

    $post_num = $get_data['num'] ?? null;
    if (!$post_num || !is_numeric($post_num)) {
        make_generic_error("Invalid post number specified.");
        return;
    }

    $post = $db->fetch("SELECT * FROM " . SQL_TABLE_POSTS . " WHERE num = ?", [$post_num]);
    if (!$post) {
        make_generic_error("Post not found.");
        return;
    }

    // Authorization: Simplified - any logged-in admin can edit.
    // Original Perl had a more complex check:
    // make_error("This post can only be edited by a board administrator.") if !$$row{originalcomment} and $$row{comment} and @session[1] ne 'admin';
    // This implied if originalcomment is empty (likely an HTML post by admin), only top admin can edit.

    $template_data = [
        'page_title' => 'Edit Post No. ' . $post_num,
        'admin_session' => $_SESSION,
        'self_url' => defined('HTML_SELF') ? HTML_SELF : 'index.php',
        'post' => $post,
        // 'stylesheets' => ... // if admin_style.pl defines specific ones for this page
    ];
    echo render_template('templates/admin/edit_post_form.php', $template_data);
}

/**
 * Processes the submission of the edit post form.
 *
 * @param Database $db The database instance.
 * @param array $post_data Form data from $_POST.
 */
function handle_admin_update_post_request(Database $db, array $post_data): void {
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['admin_user_id'])) {
        // Should ideally check for POST method and token if implementing CSRF protection
        make_http_forward((defined('HTML_SELF') ? HTML_SELF : 'index.php') . '?task=admin');
        return;
    }
    $admin_session = $_SESSION;

    // --- Input Retrieval ---
    $num = $post_data['num'] ?? null;
    $comment_raw = $post_data['comment'] ?? '';
    $subject = $post_data['subject'] ?? '';
    $name = $post_data['name'] ?? '';
    $link = $post_data['link'] ?? ''; // Corresponds to 'email' field in DB
    $trip = $post_data['trip'] ?? '';
    $no_format = isset($post_data['no_format']);

    if (!$num || !is_numeric($num)) {
        make_generic_error("Invalid post number for update.");
        return;
    }

    // Fetch existing post to ensure it exists and for auth checks if needed
    $existing_post = $db->fetch("SELECT originalcomment, parent FROM " . SQL_TABLE_POSTS . " WHERE num = ?", [$num]);
    if (!$existing_post) {
        make_generic_error("Post to update not found.");
        return;
    }

    // Authorization: If no_format is checked, ensure admin is of high enough class (e.g., 'admin')
    $allow_raw_html = $no_format && ($admin_session['admin_class'] === 'admin');

    // --- Comment Formatting ---
    $original_comment_for_db = clean_string($comment_raw);

    $quote_handler_with_db_admin = function(string $post_num_str, ?string $board_dir_str = null) use ($db) {
        return quote_link_handler_function($db, $post_num_str, $board_dir_str);
    };

    $formatted_comment_for_db = '';
    if ($allow_raw_html) { // Admin selected "Raw HTML"
        $formatted_comment_for_db = $comment_raw;
        $original_comment_for_db = $comment_raw;
    } else {
        $decoded_comment_for_formatting = decode_string($original_comment_for_db);
        $formatted_comment_for_db = Formatting::format_comment_wakabamark_style($decoded_comment_for_formatting, $quote_handler_with_db_admin);
    }

    if (trim(strip_tags($formatted_comment_for_db)) === '') { // strip_tags might be too aggressive if $allow_raw_html
        $formatted_comment_for_db = defined('S_ANOTEXT') ? S_ANOTEXT : '(No Comment)';
    }

    // --- Database Update ---
    $update_data = [
        'comment' => $formatted_comment_for_db,
        'originalcomment' => $original_comment_for_db,
        'subject' => clean_string(decode_string($subject)),
        'name' => clean_string(decode_string($name)),
        'email' => clean_string(decode_string($link)), // 'link' from form goes to 'email' in DB
        'trip' => $trip, // Tripcodes are usually stored as-is (might contain entities)
        'num' => $num
    ];

    $sql = "UPDATE " . SQL_TABLE_POSTS . " SET
                comment = :comment,
                originalcomment = :originalcomment,
                subject = :subject,
                name = :name,
                email = :email,
                trip = :trip
            WHERE num = :num";

    $db->execute($sql, $update_data);

    // --- Cache Update (Conceptual) ---
    // build_main_page_html($db); // To regenerate main page if static
    // build_thread_cache_php($existing_post['parent'] > 0 ? $existing_post['parent'] : $num, $db); // Regenerate specific thread

    // --- Log Action (Placeholder) ---
    // log_action_php('admin_edit_post', $num, $admin_session['admin_username']);

    // --- Redirect ---
    $mpanel_url = (defined('HTML_SELF') ? HTML_SELF : 'index.php') . '?task=mpanel';
    // if ($admin_session['admin_token_for_url']) { $mpanel_url .= '&admin=' . urlencode($admin_session['admin_token_for_url']); }
    make_http_forward($mpanel_url);
}


/**
 * Handles displaying the main admin panel (list of posts, management options).
 *
 * @param Database $db The database instance.
 */
function handle_admin_panel_request(Database $db): void {
    // --- Authentication Check ---
    if (session_status() == PHP_SESSION_NONE) {
        session_start(); // Ensure session is started
    }
    if (!isset($_SESSION['admin_user_id'])) {
        make_http_forward((defined('HTML_SELF') ? HTML_SELF : 'index.php') . '?task=admin'); // Redirect to login
        return;
    }
    $admin_session = $_SESSION; // Use all session data for the template

    // --- Configuration & Parameters for Pagination etc. ---
    $images_per_page = defined('IMAGES_PER_PAGE') ? IMAGES_PER_PAGE : 15; // Same as main page for now
    $replies_per_thread = defined('REPLIES_PER_THREAD') ? REPLIES_PER_THREAD : 5; // For display abbreviation
    $max_lines_shown = defined('MAX_LINES_SHOWN') ? MAX_LINES_SHOWN : 15;
    $approx_line_length = defined('APPROX_LINE_LENGTH') ? APPROX_LINE_LENGTH : 150;

    $current_page = isset($_GET['page']) ? max(0, intval($_GET['page'])) : 0;
    $self_url = defined('HTML_SELF') ? HTML_SELF : 'index.php';
    $admin_link_param = ''; // If using URL tokens: '&admin=' . urlencode($_SESSION['admin_token_for_url'] ?? '');


    // --- Fetch Data (similar to build_main_page_html but for admin panel) ---
    $order_clause = "sticky DESC, lasthit DESC, CASE parent WHEN 0 THEN num ELSE parent END ASC, num ASC";
    $all_posts_raw = $db->fetchAll("SELECT * FROM " . SQL_TABLE_POSTS . " ORDER BY " . $order_clause);

    $threads = [];
    $op_posts = [];
    foreach ($all_posts_raw as $post) {
        if ($post['parent'] == 0) {
            $op_posts[$post['num']] = $post;
            $op_posts[$post['num']]['replies_data'] = [];
        }
    }
    foreach ($all_posts_raw as $post) {
        if ($post['parent'] != 0 && isset($op_posts[$post['parent']])) {
            $op_posts[$post['parent']]['replies_data'][] = $post;
        }
    }

    foreach ($op_posts as $op_num => $op_post_data) {
        $current_thread_all_posts = [$op_post_data];
        $current_thread_all_posts = array_merge($current_thread_all_posts, $op_post_data['replies_data']);
        $threads[] = [
            'id' => $op_num,
            'posts_data_raw' => $current_thread_all_posts,
        ];
    }

    // --- Pagination ---
    $total_threads = count($threads);
    $total_pages = max(1, ceil($total_threads / $images_per_page));
    $current_page = min($current_page, $total_pages - 1);
    $start_index = $current_page * $images_per_page;
    $threads_for_current_page_meta = array_slice($threads, $start_index, $images_per_page);

    $template_threads_data = [];
    foreach ($threads_for_current_page_meta as $thread_meta) {
        $processed_posts_for_template = [];
        $actual_reply_count = count($thread_meta['posts_data_raw']) - 1;
        $omitted_replies_count = 0;

        foreach ($thread_meta['posts_data_raw'] as $idx => $post_data_item) {
            $is_op = ($idx == 0);
            if (!$is_op && $actual_reply_count > $replies_per_thread && $idx <= ($actual_reply_count - $replies_per_thread)) {
                 // This reply is among those to be omitted initially in display
                 $omitted_replies_count++;
                 continue;
            }

            // Comment from DB is already HTML formatted. Abbreviate if necessary.
            $comment_to_display_admin = $post_data_item['comment'];
            $abbreviated_comment_html_admin = abbreviate_html_simple(
                $comment_to_display_admin, $max_lines_shown, $approx_line_length
            );

            if ($abbreviated_comment_html_admin !== null) {
                $post_data_item['comment_display'] = $abbreviated_comment_html_admin;
                $post_data_item['abbrev'] = true;
            } else {
                $post_data_item['comment_display'] = $comment_to_display_admin;
                $post_data_item['abbrev'] = false;
            }
            // TODO: Check if post is reported (needs query to reports table)
            // $post_data_item['reported'] = is_post_reported_php($db, $post_data_item['num']);
            $processed_posts_for_template[] = $post_data_item;
        }

        $template_threads_data[] = [
            'posts' => $processed_posts_for_template,
            'omit' => $omitted_replies_count,
            // 'omitimages' => 0, // TODO
        ];
    }

    $pagination_links_data = [];
    for ($i = 0; $i < $total_pages; $i++) {
        $pagination_links_data[] = [
            'page' => $i,
            'filename' => $self_url . '?task=mpanel' . $admin_link_param . '&page=' . $i,
            'current' => ($i == $current_page),
        ];
    }

    // --- Prepare Template Data ---
    $template_data = [
        'page_title' => 'Admin Panel' . ($current_page > 0 ? ' - Page ' . $current_page : ''),
        'admin_session' => $admin_session,
        'self_url' => $self_url,
        'admin_link_param' => $admin_link_param, // For constructing links within template if needed
        'stylesheets' => defined('DEFAULT_STYLE') ? [['title' => DEFAULT_STYLE, 'filename' => DEFAULT_STYLE.'.css', 'default' => true]] : [], // Simplified

        'threads_data' => $template_threads_data,
        'pages' => $pagination_links_data,
        'prevpage' => $current_page > 0 ? ($self_url . '?task=mpanel' . $admin_link_param . '&page=' . ($current_page - 1)) : null,
        'nextpage' => $current_page < $total_pages - 1 ? ($self_url . '?task=mpanel' . $admin_link_param . '&page=' . ($current_page + 1)) : null,

        'postform' => true, // Show admin post form
        'image_inp' => true, // Allow image input
        'textonly_inp' => true, // Allow no-file
        'thread_id' => null, // Not a single thread view
        'indexpage' => true, // Behaves like an index page (shows multiple threads)
    ];

    echo render_template('templates/admin/admin_panel.php', $template_data);
}


/**
 * Builds the HTML for the main board page (index or paginated view).
 *
 * @param Database $db The database instance.
 * @return string The rendered HTML for the main page.
 */
function build_main_page_html(Database $db): string {
    // --- Configuration & Parameters ---
    $images_per_page = defined('IMAGES_PER_PAGE') ? IMAGES_PER_PAGE : 10;
    $replies_per_thread = defined('REPLIES_PER_THREAD') ? REPLIES_PER_THREAD : 5;
    $max_lines_shown = defined('MAX_LINES_SHOWN') ? MAX_LINES_SHOWN : 15;
    $approx_line_length = defined('APPROX_LINE_LENGTH') ? APPROX_LINE_LENGTH : 150;

    $current_page = isset($_GET['page']) ? max(0, intval($_GET['page'])) : 0;

    // --- Fetch all posts ---
    // Similar to wakaba.pl: ORDER BY sticky DESC, lasthit DESC, CASE parent WHEN 0 THEN num ELSE parent END ASC, num ASC
    $order_clause = "sticky DESC, lasthit DESC, CASE parent WHEN 0 THEN num ELSE parent END ASC, num ASC";
    // ... (rest of the function remains largely the same, but comment display needs to be consistent)

    $all_posts_raw = $db->fetchAll("SELECT * FROM " . SQL_TABLE_POSTS . " ORDER BY " . $order_clause);

    // --- Process posts into threads ---
    $threads = [];
    $op_posts = [];
    $post_map = [];

    foreach ($all_posts_raw as $post) {
        $post_map[$post['num']] = $post;
        if ($post['parent'] == 0) {
            $op_posts[$post['num']] = $post;
            $op_posts[$post['num']]['replies'] = [];
        }
    }

    foreach ($all_posts_raw as $post) {
        if ($post['parent'] != 0) {
            if (isset($op_posts[$post['parent']])) {
                $op_posts[$post['parent']]['replies'][] = $post;
            }
        }
    }

    $threads_on_page = [];
    foreach ($op_posts as $op_num => $op_post_data) {
        $current_thread_posts = [$op_post_data];
        $current_thread_posts = array_merge($current_thread_posts, $op_post_data['replies']);

        $threads[] = [
            'id' => $op_num,
            'posts_data' => $current_thread_posts,
            'omit' => 0,
            'omitimages' => 0,
        ];
    }

    // --- Pagination Logic ---
    $total_threads = count($threads);
    $total_pages = max(1, ceil($total_threads / $images_per_page));
    $current_page = min($current_page, $total_pages - 1);
    $start_index = $current_page * $images_per_page;
    $threads_for_current_page_raw = array_slice($threads, $start_index, $images_per_page);

    $template_threads_data = [];

    foreach ($threads_for_current_page_raw as $thread_raw) {
        $processed_posts_for_template = [];
        $op_post_for_template = null;
        $replies_for_template = [];

        $all_thread_posts_data = $thread_raw['posts_data'];
        $op_post_from_data = $all_thread_posts_data[0];

        // Process OP post
        $op_post_for_template = $op_post_from_data;

        // Comment for display should be the already formatted HTML from DB
        $comment_to_display = $op_post_for_template['comment'];
        $abbreviated_comment_html = abbreviate_html_simple(
            $comment_to_display, $max_lines_shown, $approx_line_length
        );

        if ($abbreviated_comment_html !== null) {
            $op_post_for_template['comment_display'] = $abbreviated_comment_html;
            $op_post_for_template['abbrev'] = true;
        } else {
            $op_post_for_template['comment_display'] = $comment_to_display;
            $op_post_for_template['abbrev'] = false;
        }
        $processed_posts_for_template[] = $op_post_for_template;

        // Process replies
        $actual_reply_count = count($all_thread_posts_data) - 1;
        $replies_to_show_data_raw = array_slice($all_thread_posts_data, 1);

        $omitted_replies_count = 0;
        if ($actual_reply_count > $replies_per_thread) {
            $omitted_replies_count = $actual_reply_count - $replies_per_thread;
            // Show last N replies
            $replies_to_show_data_processed = array_slice($replies_to_show_data_raw, $omitted_replies_count);
        } else {
            $replies_to_show_data_processed = $replies_to_show_data_raw;
        }

        foreach ($replies_to_show_data_processed as $reply_post_data) {
            $reply_post_for_template = $reply_post_data;
            $reply_comment_to_display = $reply_post_for_template['comment'];
            $abbreviated_reply_html = abbreviate_html_simple(
                $reply_comment_to_display, $max_lines_shown, $approx_line_length
            );

            if ($abbreviated_reply_html !== null) {
                $reply_post_for_template['comment_display'] = $abbreviated_reply_html;
                $reply_post_for_template['abbrev'] = true;
            } else {
                $reply_post_for_template['comment_display'] = $reply_comment_to_display;
                $reply_post_for_template['abbrev'] = false;
            }
            $processed_posts_for_template[] = $reply_post_for_template;
        }

        $template_threads_data[] = [
            'posts' => $processed_posts_for_template,
            'omit' => $omitted_replies_count,
            'omitimages' => 0,
        ];
    }

    $pagination_links = [];
    for ($i = 0; $i < $total_pages; $i++) {
        $pagination_links[] = [
            'page' => $i,
            'filename' => (defined('HTML_SELF')?HTML_SELF:'index.php') . '?page=' . $i,
            'current' => ($i == $current_page),
        ];
    }

    $template_data = [
        'page_title' => $current_page > 0 ? 'Page ' . $current_page : (defined('TITLE')?TITLE:'Imageboard'),
        'stylesheets' => defined('DEFAULT_STYLE') ? [['title' => DEFAULT_STYLE, 'filename' => DEFAULT_STYLE . '.css', 'default' => true]] : [],
        'self' => defined('HTML_SELF') ? HTML_SELF : 'index.php',
        'admin' => false,
        'thread_id' => null,
        'indexpage' => true,
        'noextra' => false,

        'prevpage' => $current_page > 0 ? ((defined('HTML_SELF')?HTML_SELF:'index.php') . '?page=' . ($current_page - 1)) : null,
        'nextpage' => $current_page < $total_pages - 1 ? ((defined('HTML_SELF')?HTML_SELF:'index.php') . '?page=' . ($current_page + 1)) : null,
        'pages' => $pagination_links,

        'postform' => (defined('ALLOW_TEXTONLY') && ALLOW_TEXTONLY) || (defined('ALLOW_IMAGES') && ALLOW_IMAGES),
        'image_inp' => defined('ALLOW_IMAGES') && ALLOW_IMAGES,
        'textonly_inp' => defined('ALLOW_TEXTONLY') && ALLOW_TEXTONLY && (defined('ALLOW_IMAGES') && ALLOW_IMAGES),

        'threads_data' => $template_threads_data,
    ];

    return render_template('templates/main_page.php', $template_data);
}


/**
 * Handles a new post submission.
 *
 * @param Database $db The database instance.
 * @param array $post_data Form data from $_POST.
 * @param array $files_data File data from $_FILES.
 */
function handle_post_request(Database $db, array $post_data, array $files_data): void {
    // Define S_ defines for error messages, or ensure they are loaded from a strings file.
    // These would typically be loaded from a language file similar to strings_en.pl
    if (!defined('S_UNJUST')) define('S_UNJUST', 'Post via GET?');
    if (!defined('S_NOTALLOWED')) define('S_NOTALLOWED', 'Posting not allowed.');
    if (!defined('S_TOOLONG')) define('S_TOOLONG', 'Input field too long.');
    if (!defined('S_NOPIC')) define('S_NOPIC', 'No file selected for new thread.');
    if (!defined('S_NOTEXT')) define('S_NOTEXT', 'No comment for post with no file.');
    if (!defined('S_WRONGPASS')) define('S_WRONGPASS', 'Admin password incorrect or not provided for admin functions.');
    if (!defined('S_LOCKED')) define('S_LOCKED', 'Thread is locked.');
    if (!defined('S_UNUSUAL')) define('S_UNUSUAL', 'Unusual characters in input.');


    // --- Input Retrieval ---
    $parent = isset($post_data['parent']) ? (int)$post_data['parent'] : 0;
    $name = $post_data['field1'] ?? '';
    $email = $post_data['field2'] ?? '';
    $subject = $post_data['field3'] ?? '';
    $comment = $post_data['field4'] ?? '';
    $password = $post_data['password'] ?? '';
    $nofile = isset($post_data['nofile']); // Checkbox, 'on' or not present
    $admin_pass = $post_data['admin'] ?? null; // Admin password if provided for admin functions

    // Admin-only flags from form (these would require admin_pass to be valid)
    $no_captcha_flag = isset($post_data['no_captcha']);
    $no_format_flag = isset($post_data['no_format']);
    $postfix = $post_data['postfix'] ?? null; // Special admin text append
    $sticky = isset($post_data['sticky']);
    $permasage = isset($post_data['permasage']);
    $locked_form_flag = isset($post_data['locked']); // From form, distinct from thread's actual locked state
    $capcode = isset($post_data['capcode']); // Admin/mod capcode display
    $spoiler = isset($post_data['spoiler']); // Image spoiler
    $nsfw = isset($post_data['nsfw']);       // NSFW image flag

    // File data
    $uploaded_file = $files_data['file'] ?? null;
    $uploadname = $uploaded_file['name'] ?? null;


    // --- Initial Validations ---
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        make_generic_error(S_UNJUST); // Assumes make_generic_error is a global error handler
        return;
    }
    if (empty($_SERVER['HTTP_USER_AGENT'])) {
        make_generic_error('Your post looks automated.');
        return;
    }

    $is_admin_session = false; // Placeholder: This would be set by check_password_php($admin_pass)
    // if ($admin_pass) { $is_admin_session = check_password_php($admin_pass, $db); }


    if (!$is_admin_session) {
        if ($no_captcha_flag || $no_format_flag || $postfix || $sticky || $permasage || $locked_form_flag || $capcode) {
            make_generic_error(S_WRONGPASS); // Admin features require valid admin pass
            return;
        }
    }

    $parent_post_data = null;
    if ($parent) {
        $parent_post_data = $db->fetch("SELECT locked, permasage, sticky FROM " . SQL_TABLE_POSTS . " WHERE num = ? AND parent = 0", [$parent]);
        if (!$parent_post_data) {
            make_generic_error('Thread not found.'); // S_NOTHREADERR
            return;
        }
        if ($parent_post_data['locked'] && !$is_admin_session) {
            make_generic_error(S_LOCKED);
            return;
        }
    }

    // Check posting allowances
    $has_file = $uploaded_file && $uploaded_file['error'] == UPLOAD_ERR_OK && $uploaded_file['size'] > 0;

    if ($parent) { // Replying
        if ($has_file && !(defined('ALLOW_IMAGE_REPLIES') && ALLOW_IMAGE_REPLIES) && !$is_admin_session) {
            make_generic_error(S_NOTALLOWED . " (Image replies not allowed)");
            return;
        }
        if (!$has_file && !(defined('ALLOW_TEXT_REPLIES') && ALLOW_TEXT_REPLIES) && !$is_admin_session) {
            make_generic_error(S_NOTALLOWED . " (Text replies not allowed)");
            return;
        }
    } else { // New thread
        if ($has_file && !(defined('ALLOW_IMAGES') && ALLOW_IMAGES) && !$is_admin_session) {
            make_generic_error(S_NOTALLOWED . " (Image posts not allowed)");
            return;
        }
        if (!$has_file && !(defined('ALLOW_TEXTONLY') && ALLOW_TEXTONLY) && !$is_admin_session) {
            make_generic_error(S_NOTALLOWED . " (Text-only threads not allowed)");
            return;
        }
    }

    // Field length checks (simplified from Perl's regex checks for weird chars)
    if (preg_match('/[\n\r]/', $name) || preg_match('/[\n\r]/', $email) || preg_match('/[\n\r]/', $subject)) {
        make_generic_error(S_UNUSUAL);
        return;
    }
    if (mb_strlen($name) > MAX_FIELD_LENGTH) { make_generic_error(S_TOOLONG . " (Name)"); return; }
    if (mb_strlen($email) > MAX_FIELD_LENGTH) { make_generic_error(S_TOOLONG . " (Email)"); return; }
    if (mb_strlen($subject) > MAX_FIELD_LENGTH) { make_generic_error(S_TOOLONG . " (Subject)"); return; }
    if (mb_strlen($comment) > MAX_COMMENT_LENGTH && !$is_admin_session) { make_generic_error(S_TOOLONG . " (Comment)"); return; }

    if (!$parent && !$has_file && !$nofile && !$is_admin_session) {
        make_generic_error(S_NOPIC);
        return;
    }
    if (trim($comment) === '' && !$has_file) {
        make_generic_error(S_NOTEXT);
        return;
    }

    // --- IP and Host ---
    $ip = get_user_ip(); // Needs to be ported from wakautils.pl get_ip()
    $num_ip = ip_to_long_php($ip); // Needs to be ported from dot_to_dec()
    // $host = gethostbyaddr($ip); // Optional, can be slow

    // --- Ban Check ---
    // Ensure security.php is included in index.php or autoloaded
    if (function_exists('check_ip_ban')) {
        $ban_details = check_ip_ban($db, $num_ip);
        if ($ban_details) {
            // User is banned, show banned page and exit
            $banned_page_data = [
                'page_title' => 'You Are Banned',
                'ip_address' => $ip,
                'ban_info' => $ban_details,
                // 'stylesheets' => ... // if needed by banned_page.php
            ];
            echo render_template('templates/banned_page.php', $banned_page_data);
            exit;
        }
    }
    // TODO: Implement and call check_word_bans() here as well.
    // if (check_word_bans($db, $name, $subject, $comment)) {
    //     make_generic_error(S_STRREF ?? 'Banned word found in post.'); // S_STRREF is from strings_en.pl
    //     return;
    // }

    // --- CAPTCHA Check ---
    if (defined('ENABLE_CAPTCHA') && (ENABLE_CAPTCHA === 'builtin' || ENABLE_CAPTCHA === 'captcha') && !$is_admin_session && !$no_captcha_flag) {
        // $no_captcha_flag is an admin override from form
        // Also need to check for trusted users (e.g. tripcodes) or users with valid 'pass' if that system is ported.

        $captcha_input = $post_data['captcha_input'] ?? '';
        $page_key = Captcha::getCaptchaPageKey($parent); // $parent is 0 for new threads

        if (!Captcha::validateCaptcha($db, $ip, $page_key, $captcha_input)) {
            make_generic_error(S_BADCAP ?? 'Incorrect CAPTCHA entered.');
            return;
        }
    }


    // --- Timestamp ---
    $time_now = time();

    // --- Tripcode Processing (Placeholder) ---
    list($processed_name, $tripcode) = process_tripcode_php($name, defined('TRIPKEY') ? TRIPKEY : '!', defined('SECRET') ? SECRET : '', defined('CHARSET') ? CHARSET : 'UTF-8');
    $name = $processed_name;

    // --- Security Checks (Placeholders) ---
    // if (!is_whitelisted_php($num_ip, $db) && !ban_check_php($num_ip, $name, $subject, $comment, $db)) { /* Error or die */ return; }
    // if (!is_whitelisted_php($num_ip, $db) && !spam_engine_php($_POST, $db)) { /* Error or die */ return; }
    // if (defined('ENABLE_CAPTCHA') && ENABLE_CAPTCHA && !$no_captcha_flag && !is_trusted_php($tripcode, $db) /* && !pass_is_valid() */ ) {
    //    if (!check_captcha_php($_POST['captcha'] ?? '', $ip, $parent, $db)) { /* Error or die */ return; }
    // }
    // if (!is_whitelisted_php($num_ip, $db) && defined('ENABLE_PROXY_CHECK') && ENABLE_PROXY_CHECK) {
    //    if (!proxy_check_php($ip, $db)) { /* Error or die */ return; }
    // }

    // --- Thread Information (if reply) ---
    $lasthit = $time_now; // Default for new thread
    if ($parent && $parent_post_data) {
        $lasthit = $parent_post_data['lasthit'];
        // Inherit sticky/permasage/locked status from parent if applicable (original Perl logic)
        if ($parent_post_data['sticky']) $sticky = true;
        if ($parent_post_data['permasage']) $permasage = true;
        // $locked is already checked (thread lock, not form flag)
    }

    // --- Input Cleaning & Formatting ---
    // clean_string and decode_string are in utils.php
    $email_cleaned = clean_string(decode_string($email)); // field2
    $subject_cleaned = clean_string(decode_string($subject)); // field3
    $uploadname_cleaned = $uploadname ? clean_string(decode_string($uploadname)) : null; // from $_FILES

    $sage = false;
    $noko = false; // Redirect to thread after posting
    if (preg_match('/^(noko|sage)/i', $email_cleaned, $matches)) {
        $command = strtolower($matches[1]);
        if ($command === 'noko') $noko = true;
        if ($command === 'sage') $sage = true;
        if ($noko || (defined('SILENT_SAGE') && SILENT_SAGE && $sage) ) $email_cleaned = ''; // Clear email field
        // else if ($sage) $email_cleaned = 'sage'; // Keep 'sage' if not silent & not noko
    }
    if (defined('AUTO_NOKO') && AUTO_NOKO) $noko = true;

    if ($email_cleaned && $email_cleaned !== 'sage' && !preg_match(PROTOCOL_REGEX_PHP, $email_cleaned)) {
        $email_cleaned = "mailto:" . $email_cleaned;
    }

    // Comment formatting
    $original_comment_for_db = clean_string(decode_string($comment)); // Store the cleaned raw input for 'originalcomment'

    // Define the quote link handler callback, ensuring $db is in its scope.
    $quote_handler_with_db = function(string $post_num_str, ?string $board_dir_str = null) use ($db) {
        return quote_link_handler_function($db, $post_num_str, $board_dir_str);
    };

    if ($no_format_flag && $is_admin_session) { // Admin posting raw HTML
        // If admin posts raw HTML, originalcomment might be the same as comment, or could be empty
        // depending on how we want to handle edits later. For now, assume raw comment is stored in 'comment'
        // and originalcomment might be the raw input if different, or same if no special meaning.
        // Wakaba.pl stores the raw HTML input into 'comment' and $originalcomment becomes the same.
        $comment_formatted_for_db = $comment;
        $original_comment_for_db = $comment; // For raw HTML posts, original and formatted are the same.
    } else {
        // $original_comment_for_db is already set from clean_string(decode_string($comment))
        $comment_formatted_for_db = Formatting::format_comment_wakabamark_style($original_comment_for_db, $quote_handler_with_db);
    }
    if ($postfix && $is_admin_session) $comment_formatted_for_db .= $postfix;


    // Default values for empty fields
    $parent = $parent ?: 0;
    if (empty($name) && empty($tripcode)) $name = make_anonymous_php($ip, $time_now); // make_anonymous_php needs porting
    if (empty($subject_cleaned)) $subject_cleaned = defined('S_ANOTITLE') ? S_ANOTITLE : 'No Subject';
    if (trim(strip_tags($comment_formatted)) === '') $comment_formatted = defined('S_ANOTEXT') ? S_ANOTEXT : 'No Comment';


    // --- Flood Protection (Placeholder) ---
    // if (!$is_admin_session && !flood_check_php($num_ip, $time_now, $comment_formatted, $has_file, $db)) { /* Error or die */ return; }

    // --- Date and ID ---
    $date_str = make_date($time_now, defined('DATE_STYLE') ? DATE_STYLE : null); // make_date is in template_helpers.php
    $post_id_str = '';
    // if (defined('DISPLAY_ID') && DISPLAY_ID && !$capcode) {
    //    $post_id_str = make_id_code_php($ip, $time_now, $email_cleaned, $parent, $db); // make_id_code_php needs porting
    // }

    // --- File Processing (Placeholder, major part) ---
    $file_details = null;
    if ($has_file) {
        // $file_details = process_file_php($uploaded_file, $uploadname_cleaned, $time_now, $nsfw, $db);
        // if ($file_details['error']) { make_generic_error($file_details['error']); return; }
        // Expected keys in $file_details: image_path, md5, width, height, thumb_path, tn_width, tn_height, filesize
        // For now, let's assume some dummy values if a file was uploaded
        $file_details = [
            'image_path' => 'uploads/src/dummy_image.jpg', 'md5' => md5_file($uploaded_file['tmp_name']),
            'width' => 100, 'height' => 100, 'filesize' => $uploaded_file['size'],
            'thumb_path' => 'uploads/thumb/dummy_thumb.jpg', 'tn_width' => 50, 'tn_height' => 50,
            'original_filename' => $uploadname_cleaned
        ];
         echo "<p>File processing placeholder: {$uploadname_cleaned} would be processed.</p>";
    }


    // --- Database Insertion ---
    $insert_data = [
        'parent' => $parent,
        'timestamp' => $time_now,
        'lasthit' => $lasthit, // For OP, this is $time_now. For reply, it's parent's lasthit (conditionally updated later)
        'ip' => $num_ip, // Store as long/decimal
        'id' => $post_id_str,
        'date' => $date_str,
        'name' => $name,
        'trip' => $tripcode,
        'email' => $email_cleaned,
        'subject' => $subject_cleaned,
        'password' => $password, // TODO: Consider hashing post passwords if desired
        'comment' => $comment_formatted_for_db, // Use the fully processed comment
        'originalcomment' => $original_comment_for_db, // Store the cleaned, "original" input
        'image' => $file_details['image_path'] ?? null,
        'size' => $file_details['filesize'] ?? 0,
        'md5' => $file_details['md5'] ?? null,
        'width' => $file_details['width'] ?? 0,
        'height' => $file_details['height'] ?? 0,
        'thumbnail' => $file_details['thumb_path'] ?? null,
        'tn_width' => $file_details['tn_width'] ?? 0,
        'tn_height' => $file_details['tn_height'] ?? 0,
        'sticky' => $sticky && $is_admin_session ? 1 : 0,
        'permasage' => $permasage && $is_admin_session ? 1 : 0,
        'locked' => ($parent && $parent_post_data && $parent_post_data['locked']) || ($locked_form_flag && $is_admin_session) ? 1 : 0,
        'filename' => $file_details['original_filename'] ?? null,
        'tnmask' => $spoiler ? 1 : 0, // Based on form spoiler checkbox
        'staffpost' => $capcode && $is_admin_session ? 1 : 0, // Simplified staffpost logic
        'passnum' => null // Placeholder for pass system integration
    ];

    $db->execute(
        "INSERT INTO " . SQL_TABLE_POSTS . " (parent, timestamp, lasthit, ip, id, date, name, trip, email, subject, password, comment, originalcomment, image, size, md5, width, height, thumbnail, tn_width, tn_height, sticky, permasage, locked, filename, tnmask, staffpost, passnum) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        array_values($insert_data)
    );
    $new_post_id = $db->lastInsertId();

    // Bumping logic
    if ($parent && !$sage && $parent_post_data && !$parent_post_data['permasage']) {
        // Check SAGE_COUNT against MAX_RES etc.
        // Simplified: $db->execute("UPDATE " . SQL_TABLE_POSTS . " SET lasthit = ? WHERE num = ? OR parent = ?", [$time_now, $parent, $parent]);
         $db->execute("UPDATE " . SQL_TABLE_POSTS . " SET lasthit = ? WHERE num = ?", [$time_now, $parent]);
    }

    // If it was an OP, update its own 'id' if make_id_code uses the post number
    if (!$parent && $new_post_id && defined('DISPLAY_ID') && DISPLAY_ID && !$capcode) {
        // $post_id_str_updated = make_id_code_php($ip, $time_now, $email_cleaned, $new_post_id, $db);
        // if ($post_id_str_updated !== $post_id_str) {
        //    $db->execute("UPDATE " . SQL_TABLE_POSTS . " SET id = ? WHERE num = ?", [$post_id_str_updated, $new_post_id]);
        // }
    }


    // --- Trimming (Placeholder) ---
    // trim_database_php($db);

    // --- Cache Update ---
    // For now, just regenerate the main page. Individual thread cache is more complex.
    // build_main_page_html($db); // This returns HTML, not writes to file yet.
    // if ($parent) { build_thread_cache_php($parent, $db); }
    // else { build_thread_cache_php($new_post_id, $db); }
    // For simplicity, we will redirect and let the next page load handle display.

    // --- Cookies ---
    // make_cookies_php(['name' => $name, 'email' => $email, 'password' => $password]);

    // --- Redirection ---
    $redirect_url = defined('HTML_SELF') ? HTML_SELF : 'index.php';
    if ($noko && $new_post_id) {
        $redirect_url = get_reply_link($new_post_id, $parent); // from template_helpers.php
    }

    echo "<p>Post successful (placeholder)! ID: {$new_post_id}. Redirecting to {$redirect_url}...</p>";
    echo "<meta http-equiv='refresh' content='2;url={$redirect_url}'>"; // Simple meta refresh for now
    // make_http_forward($redirect_url, defined('ALTERNATE_REDIRECT') ? ALTERNATE_REDIRECT : false);
}

/**
 * Handles post deletion requests.
 *
 * @param Database $db The database instance.
 * @param array $post_data Form data from $_POST.
 */
function handle_delete_request(Database $db, array $post_data): void {
    // Define S_ defines for error messages if not globally available
    if (!defined('S_BADDELPASS')) define('S_BADDELPASS', 'Password incorrect or not provided for deletion.');
    if (!defined('S_NOTFOUND')) define('S_NOTFOUND', 'Post not found.');

    // --- Input Retrieval ---
    $form_password = $post_data['password'] ?? null;
    $file_only = isset($post_data['fileonly']); // Checkbox 'on' or not present
    $archive_mode = isset($post_data['archive']) && $post_data['archive']; // Admin only feature
    $admin_token = $post_data['admin'] ?? null; // Admin session token/password
    $posts_to_delete = $post_data['delete'] ?? []; // Array of post numbers

    if (!is_array($posts_to_delete)) {
        $posts_to_delete = [$posts_to_delete]; // Ensure it's an array
    }
    $posts_to_delete = array_map('intval', $posts_to_delete);
    $posts_to_delete = array_filter($posts_to_delete, fn($val) => $val > 0);


    // --- Authentication/Authorization (Simplified) ---
    $is_admin = false;
    if ($admin_token) {
        // $is_admin = check_password_php($admin_token, $db); // Returns true if admin, false otherwise
        // For now, let's assume any admin token makes it admin for testing
        if ($admin_token === "sekrit") $is_admin = true; // Dummy check
    }

    if (empty($posts_to_delete)) {
        make_generic_error("No posts selected for deletion.");
        return;
    }

    if (!$is_admin && empty($form_password)) {
        make_generic_error(S_BADDELPASS . " (No password provided for user deletion)");
        return;
    }

    if ($archive_mode && !$is_admin) {
        make_generic_error("Archive mode is admin-only."); // Should not happen if form prevents it
        return;
    }

    $deleted_something = false;

    foreach ($posts_to_delete as $post_num) {
        $post_data_db = $db->fetch("SELECT * FROM " . SQL_TABLE_POSTS . " WHERE num = ?", [$post_num]);

        if (!$post_data_db) {
            echo "<p>Post #{$post_num} not found. Skipping.</p>"; // Or collect errors
            continue;
        }

        if (!$is_admin && $post_data_db['password'] !== $form_password) {
            echo "<p>Password incorrect for post #{$post_num}. Skipping.</p>"; // Or collect errors
            continue;
        }

        // log_action_php($is_admin ? 'deletepost_admin' : 'deletepost_user', $post_num, $is_admin ? $admin_token : get_user_ip());

        $deleted_something = true;

        if ($file_only) {
            // --- File-Only Deletion ---
            if (!empty($post_data_db['image'])) {
                $image_path_fs = WAKABA_BASE_DIR . '/public/' . $post_data_db['image'];
                $thumb_path_fs = WAKABA_BASE_DIR . '/public/' . $post_data_db['thumbnail'];

                if (file_exists($image_path_fs)) unlink($image_path_fs);
                if ($post_data_db['thumbnail'] && strpos($post_data_db['thumbnail'], basename(THUMB_DIR ?? 'thumb')) !== false && file_exists($thumb_path_fs) && $image_path_fs != $thumb_path_fs) {
                     unlink($thumb_path_fs);
                }

                $db->execute("UPDATE " . SQL_TABLE_POSTS . " SET image=NULL, size=0, md5=NULL, width=0, height=0, thumbnail=NULL, tn_width=0, tn_height=0, filename=NULL WHERE num = ?", [$post_num]);
                echo "<p>File(s) for post #{$post_num} deleted.</p>";
            } else {
                echo "<p>Post #{$post_num} has no file to delete.</p>";
            }
            // build_thread_cache_php($post_data_db['parent'] ?: $post_num, $db); // Update cache for the thread
        } else {
            // --- Full Post/Thread Deletion ---
            $posts_to_remove_files_for = $db->fetchAll("SELECT image, thumbnail FROM " . SQL_TABLE_POSTS . " WHERE num = ? OR parent = ?", [$post_num, $post_num]);

            foreach ($posts_to_remove_files_for as $file_info) {
                if (!empty($file_info['image'])) {
                    $image_path_fs = WAKABA_BASE_DIR . '/public/' . $file_info['image'];
                    $thumb_path_fs = WAKABA_BASE_DIR . '/public/' . $file_info['thumbnail'];

                    // TODO: Archive mode logic
                    // if ($archive_mode && $is_admin) { ... rename files ... } else { ... unlink ... }
                    if (file_exists($image_path_fs)) unlink($image_path_fs);
                    if ($file_info['thumbnail'] && strpos($file_info['thumbnail'], basename(THUMB_DIR ?? 'thumb')) !== false && file_exists($thumb_path_fs) && $image_path_fs != $thumb_path_fs) {
                         unlink($thumb_path_fs);
                    }
                }
            }

            $db->execute("DELETE FROM " . SQL_TABLE_POSTS . " WHERE num = ? OR parent = ?", [$post_num, $post_num]);
            // Delete from report table
            if (defined('SQL_TABLE_REPORTS')) {
                 $db->execute("DELETE FROM " . SQL_TABLE_REPORTS . " WHERE postnum = ? AND board = ?", [$post_num, BOARD_DIR]);
            }
            echo "<p>Post #{$post_num} (and replies, if any) deleted.</p>";

            // if ($post_data_db['parent'] == 0) { /* unlink RES_DIR.$post_num.PAGE_EXT; */ }
            // else { build_thread_cache_php($post_data_db['parent'], $db); }
        }
    }

    if ($deleted_something) {
        // --- Cache Update (Simplified) ---
        // build_main_page_html($db); // Regenerate main page view (if we were writing to static files)
        // For dynamic rendering, the next request to main page will show changes.
        echo "<p>Deletion process complete. Content will be updated on next page load.</p>";
    } else {
        echo "<p>No posts were deleted (perhaps due to password errors or posts not found).</p>";
    }

    // --- Redirection ---
    $redirect_url = $is_admin ? ( (defined('HTML_SELF')?HTML_SELF:'index.php') . '?task=mpanel&admin=' . urlencode($admin_token ?? '')) : (defined('HTML_SELF')?HTML_SELF:'index.php');
    echo "<p>Redirecting in 3 seconds to: <a href='{$redirect_url}'>{$redirect_url}</a></p>";
    echo "<meta http-equiv='refresh' content='3;url={$redirect_url}'>";
    // make_http_forward($redirect_url, defined('ALTERNATE_REDIRECT') ? ALTERNATE_REDIRECT : false);
}

[end of php_wakaba/src/actions.php]
