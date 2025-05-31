<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define a base path for includes if not already defined (e.g., by a front controller)
if (!defined('WAKABA_BASE_DIR')) {
    define('WAKABA_BASE_DIR', dirname(__DIR__));
}

require_once WAKABA_BASE_DIR . '/config/config.php';
require_once WAKABA_BASE_DIR . '/src/Database.php';
require_once WAKABA_BASE_DIR . '/src/template_helpers.php'; // For rendering, date, etc.
require_once WAKABA_BASE_DIR . '/src/utils.php'; // For general utilities, security placeholders
require_once WAKABA_BASE_DIR . '/src/security.php'; // For ban checks, etc.
require_once WAKABA_BASE_DIR . '/src/uploads.php'; // For file upload processing
require_once WAKABA_BASE_DIR . '/src/Formatting.php'; // For comment formatting
require_once WAKABA_BASE_DIR . '/src/Events.php'; // For event handling
require_once WAKABA_BASE_DIR . '/src/actions.php'; // For action handlers

// Start session management if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true, // Good practice
        'cookie_secure' => isset($_SERVER['HTTPS']), // Send only over HTTPS if available
        'cookie_samesite' => 'Lax' // Mitigate CSRF
    ]);
}

// Determine the task
$task = $_REQUEST['task'] ?? $_REQUEST['action'] ?? null; // Supports GET or POST, and 'action' for some legacy reason

// Initialize Database connection
$db = null;
try {
    $db_options = isset($GLOBALS['config_global']['DB_OPTIONS']) ? $GLOBALS['config_global']['DB_OPTIONS'] : [];
    $db = new Database(DB_DSN, DB_USER, DB_PASS, $db_options);
    // Database::initializeAllTables($db->getConnection()); // Initialize DB tables if needed, can be run once separately
} catch (PDOException $e) {
    // TODO: Replace with a more user-friendly error page or logging
    // For now, using the make_error function if available, or just die.
    if (function_exists('make_error')) {
        // Assuming make_error is ported and available globally or via utils.php
        // make_error("Database Connection Error: " . $e->getMessage());
        die("Database Connection Error: " . $e->getMessage() . "<br><pre>" . $e->getTraceAsString() . "</pre>");
    } else {
        die("Database Connection Error: " . $e->getMessage() . "<br><pre>" . $e->getTraceAsString() . "</pre>");
    }
}


// Basic router based on the task
// Output buffering can be started here if headers/content might be sent by handlers before a redirect
// ob_start();

switch ($task) {
    case 'post':
        handle_post_request($db, $_POST, $_FILES);
        break;
    case 'delete':
        handle_delete_request($db, $_POST);
        break;
    case 'admin': // Show login form
        handle_admin_show_login_form_request($db);
        break;
    case 'admin_perform_login': // Process login form
        handle_admin_perform_login_request($db, $_POST);
        break;
    case 'logout':
        handle_admin_logout_request($db);
        break;
    case 'mpanel': // Admin panel main view
        handle_admin_panel_request($db);
        break;
    case 'admin_ban_panel': // Renamed from 'bans' for clarity
        handle_admin_ban_panel_request($db);
        break;
    case 'admin_add_ip_ban': // Renamed from 'addip'
        handle_admin_add_ip_ban_request($db, $_POST);
        break;
    case 'admin_remove_ban': // Renamed from 'removeban'
        handle_admin_remove_ban_request($db, $_GET);
        break;
    case 'admin_activate_ban':
        handle_admin_toggle_ban_status_request($db, $_GET, true);
        break;
    case 'admin_deactivate_ban':
        handle_admin_toggle_ban_status_request($db, $_GET, false);
        break;
    // case 'addstring': // Placeholder for string/word bans
    //    handle_admin_add_string_ban_request($db);
    //    break;
    case 'deleteall':
        handle_admin_delete_all_request($db);
        break;
    case 'proxy':
        handle_admin_proxy_request($db);
        break;
    case 'addproxy':
        handle_admin_add_proxy_request($db);
        break;
    case 'removeproxy':
        handle_admin_remove_proxy_request($db);
        break;
    case 'spam':
        handle_admin_spam_request($db);
        break;
    case 'updatespam':
        handle_admin_update_spam_request($db);
        break;
    case 'sqldump':
        handle_admin_sql_dump_request($db);
        break;
    case 'sql': // SQL Interface
        handle_admin_sql_interface_request($db);
        break;
    case 'mpost': // Admin make post form
        handle_admin_make_post_request($db);
        break;
    case 'rebuild':
        handle_admin_rebuild_cache_request($db);
        break;
    case 'nuke':
        handle_admin_nuke_database_request($db);
        break;
    case 'list': // Disabled in wakaba.pl
        handle_list_request($db);
        // Or perhaps: make_error("Task 'list' is disabled.");
        break;
    case 'cat': // Disabled in wakaba.pl
        handle_cat_request($db);
        // Or perhaps: make_error("Task 'cat' is disabled.");
        break;
    case 'stickdatshit': // Toggle sticky
        handle_toggle_sticky_request($db);
        break;
    case 'permasage':
        handle_toggle_permasage_request($db);
        break;
    case 'lockthread':
        handle_toggle_lock_thread_request($db);
        break;
    case 'report':
        handle_report_post_request($db);
        break;
    case 'register': // Admin register user form
        handle_admin_register_user_form_request($db);
        break;
    case 'adduser':
        handle_admin_add_user_request($db);
        break;
    case 'manageusers':
        handle_admin_manage_users_request($db);
        break;
    case 'removeuser':
        handle_admin_remove_user_request($db);
        break;
    case 'edituser': // Admin edit user form
        handle_admin_edit_user_form_request($db);
        break;
    case 'setnewpass': // Admin update user details (pass/class/email)
        handle_admin_update_user_password_request($db);
        break;
    case 'composemsg':
        handle_admin_compose_message_form_request($db);
        break;
    case 'sendmsg':
        handle_admin_send_message_request($db);
        break;
    case 'inbox':
        handle_admin_inbox_request($db);
        break;
    case 'viewmsg':
        handle_admin_view_message_request($db);
        break;
    case 'viewthreads': // Admin view of threads (paginated)
        handle_admin_view_threads_request($db);
        break;
    case 'viewthread': // Admin view of a single thread
        handle_admin_view_thread_request($db);
        break;
    case 'viewposts':
        handle_admin_view_posts_request($db);
        break;
    case 'ippage':
        handle_admin_ip_page_request($db);
        break;
    case 'updateban':
        handle_admin_update_ban_request($db);
        break;
    case 'admin_edit_post_form': // Changed from 'editpost' to be more specific
        handle_admin_edit_post_form_request($db, $_GET);
        break;
    case 'admin_update_post': // Changed from 'updatepost'
        handle_admin_update_post_request($db, $_POST);
        break;
    case 'viewreports':
        handle_admin_view_reports_request($db);
        break;
    case 'viewreport':
        handle_admin_view_report_request($db);
        break;
    case 'requestban': // Ban request form (from report)
        handle_admin_request_ban_form_request($db);
        break;
    case 'submitrequest': // Submit ban request
        handle_admin_submit_ban_request($db);
        break;
    case 'dismiss': // Dismiss report
        handle_admin_dismiss_report_request($db);
        break;
    case 'listrequests': // List ban requests
        handle_admin_list_ban_requests_request($db);
        break;
    case 'dismissrequests': // Dismiss ban requests for IP/Post
        handle_admin_dismiss_ban_requests_request($db);
        break;
    case 'bantemplate': // Ban page template (from request)
        handle_admin_ban_template_request($db);
        break;
    case 'viewdeletedpost':
        handle_admin_view_deleted_post_request($db);
        break;
    case 'viewlog':
        handle_admin_view_log_request($db);
        break;
    case 'clearlog':
        handle_admin_clear_log_request($db);
        break;
    case 'getpass': // Show Pass Registration Form
        handle_get_pass_form_request($db);
        break;
    case 'addpass': // Process Pass Registration
        handle_add_pass_request($db);
        break;
    case 'passauth': // Show Pass Authorization Form
        handle_pass_auth_form_request($db);
        break;
    case 'authpass': // Process Pass Authorization
        handle_auth_pass_request($db);
        break;
    case 'passlist': // Admin View Pass List
        handle_admin_pass_list_request($db);
        break;
    case 'updatepass': // Admin Update Pass Status
        handle_admin_update_pass_request($db);
        break;
    case 'restart': // Admin Restart Script (FastCGI)
        handle_admin_restart_script_request($db);
        break;
    case null: // No task, default action (show main page)
        // In wakaba.pl, this involves build_cache() then make_http_forward(HTML_SELF, ...).
        // For now, we'll just echo something or directly call a main page handler.
        // The actual HTML_SELF is 'index.php' in our PHP version.
        // If we want to mimic the "build cache then show" behavior, that logic would go here or in handle_show_main_page.
        // For this step, simply forwarding to itself to avoid errors if HTML_SELF is not set for some reason.
        // Or, if HTML_SELF is 'index.php', this would be where you call the function that renders the main board view.
        // handle_show_main_page($db); // This function would prepare and show the main page.
        // For now, let's assume the main page is just index.php without specific task.
        // If you access index.php without a task, it might show a default page.
        // If the intention is to redirect like wakaba.pl, it would be:
        // make_http_forward(HTML_SELF, defined('ALTERNATE_REDIRECT') ? ALTERNATE_REDIRECT : false);
        // For now, let's just output a placeholder:
        // echo "Displaying main page (task was null).<br>";
        // To actually display something, you'd call a function like:
        handle_show_main_page($db); // display_main_board_view($db);
        break;
    default:
        // Unknown task
        // In wakaba.pl: make_http_forward(HTML_SELF,ALTERNATE_REDIRECT);
        // handle_unknown_task($db, $task); // This is a placeholder in actions.php
        // For now, let's make unknown tasks also show the main page, or an error.
        // Later, this can be a dedicated error or redirect as in original.
        echo "Unknown task: " . htmlspecialchars($task ?? 'NULL') . ". Displaying main page instead.<br>";
        handle_show_main_page($db);
        // Original behavior:
        // $redirectTo = defined('HTML_SELF') ? HTML_SELF : 'index.php';
        // $useAlternate = defined('ALTERNATE_REDIRECT') ? ALTERNATE_REDIRECT : false;
        // make_http_forward($redirectTo, $useAlternate);
        break;
}

// ob_end_flush(); // If output buffering was started

?>
