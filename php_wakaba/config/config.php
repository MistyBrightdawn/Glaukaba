<?php

// php_wakaba Configuration File

// System config
//define('BOARD_DIR', ''); // This board's directory. Leaving this blank enables single board mode
define('NUKE_PASS', ''); // Password to nuke a board. Change this too, NOW!
define('SECRET', ''); // Cryptographic secret. CHANGE THIS to something totally random, and long.
//define('SQL_TABLE', 'comments'); // Table (NOT DATABASE) used by image board. Please change the value for each board

// None of the options in this block need changing for now.
// define('SQL_ADMIN_TABLE', 'admin'); // Table used for admin information
// define('SQL_USER_TABLE', 'users'); // Table used for admin user information
// define('SQL_PROXY_TABLE', 'proxy'); // Table used for proxy information
// define('SQL_REPORT_TABLE', 'reports'); // Table used for proxy information

//define('USE_TEMPFILES', 1); // Set this to 1 under Unix and 0 under Windows! (Use tempfiles when creating pages) - PHP handles temp files differently

// Page look
define('TITLE', 'Wakaba Image Board'); // Name of this image board
define('SUBTITLE', '');
define('SHOWTITLETXT', 1); // Show TITLE at top (1: yes 0: no)
define('SHOWTITLEIMG', 0); // Show image at top (0: no, 1: single, 2: rotating)
define('TITLEIMG', ''); // Title image (point to a script file if rotating)
define('FAVICON', 'favicon.ico'); // Favicon.ico file
define('HOME', '../'); // Site home directory (up one level by default)
define('IMAGES_PER_PAGE', 15); // Images per page
define('REPLIES_PER_THREAD', 5); // Replies shown
define('IMAGE_REPLIES_PER_THREAD', 0); // Number of image replies per thread to show, set to 0 for no limit.
define('S_ANONAME', 'Anonymous'); // Defines what to print if there is no text entered in the name field
define('S_ANOTEXT', ''); // Defines what to print if there is no text entered in the comment field
define('S_ANOTITLE', ''); // Defines what to print if there is no text entered into subject field
define('SILLY_ANONYMOUS', ''); // Make up silly names for anonymous people (0 or '': don't display, any combination of 'day' or 'board': make names change for each day or board, 'static': static names)
define('DEFAULT_STYLE', 'Yotsuba B'); // Title of the default style for the board.
define('SOCIAL', 0); // Disabled by default

// Limitations
define('MAX_KB', 5000); // Maximum upload size in KB
define('MAX_W', 250); // Images exceeding this width will be thumbnailed
define('MAX_H', 250); // Images exceeding this height will be thumbnailed
define('MAX_RES', 9000); // Maximum topic bumps
define('MAX_POSTS', 0); // Maximum number of posts (set to 0 to disable)
define('MAX_THREADS', 0); // Maximum number of threads (set to 0 to disable)
define('MAX_AGE', 0); // Maximum age of a thread in hours (set to 0 to disable)
define('MAX_MEGABYTES', 0); // Maximum size to use for all images in megabytes (set to 0 to disable)
define('MAX_FIELD_LENGTH', 100); // Maximum number of characters in subject, name, and email
define('MAX_COMMENT_LENGTH', 8192); // Maximum number of characters in a comment
define('MAX_LINES_SHOWN', 15); // Max lines shown per post (0 = no limit)
define('MAX_IMAGE_WIDTH', 16384); // Maximum width of image before rejecting
define('MAX_IMAGE_HEIGHT', 16384); // Maximum height of image before rejecting
define('MAX_IMAGE_PIXELS', 50000000); // Maximum width*height of image before rejecting

// Captcha - will require a PHP captcha solution
// define('ENABLE_CAPTCHA', ''); // You can choose between built in 'captcha', 'recaptcha', or none at all (default = none)
// define('SQL_CAPTCHA_TABLE', 'captcha'); // Use a different captcha table for each board, if you have more than one!
// define('CAPTCHA_LIFETIME', 1440); // Captcha lifetime in seconds
// define('CAPTCHA_SCRIPT', 'captcha.php'); // Changed from .pl
// define('CAPTCHA_HEIGHT', 18);
// define('CAPTCHA_SCRIBBLE', 0.2);
// define('CAPTCHA_SCALING', 0.15);
// define('CAPTCHA_ROTATION', 0.3);
// define('CAPTCHA_SPACING', 2.5);
// define('RECAPTCHA_PRIVATE_KEY', '');
// define('RECAPTCHA_PUBLIC_KEY', '');

// Load Balancing - skipping for now
// define('ENABLE_LOAD', 0);
// define('LOAD_SENDER_SCRIPT', './sender.php'); // Changed from .pl
// define('LOAD_LOCAL', 120);
// $config['LOAD_HOSTS'] = [['http://somesite/loader.php', 'password', 100]]; // Example, needs PHP array format
// define('LOAD_KBRATE', 25);

// Proxy - skipping for now
// define('ENABLE_PROXY_CHECK', 0);
// define('PROXY_COMMAND', 'proxycheck -s -d CHANGEME -c chat:CHANGEME ESMTP" -aaaa');
// define('PROXY_WHITE_AGE', 604800);
// define('PROXY_BLACK_AGE', 604800);

// Tweaks
define('THUMBNAIL_SMALL', 1); // Thumbnail small images (1: yes, 0: no)
define('THUMBNAIL_QUALITY', 60); // Thumbnail JPEG quality
define('DELETED_THUMBNAIL', ''); // Thumbnail to show for deleted images (leave empty to show text message)
define('DELETED_IMAGE', ''); // Image to link for deleted images (only used together with DELETED_THUMBNAIL)
define('ALLOW_TEXTONLY', 1); // Allow textonly posts (1: yes, 0: no)
define('ALLOW_IMAGES', 1); // Allow image posting (1: yes, 0: no)
define('ALLOW_TEXT_REPLIES', 1); // Allow replies (1: yes, 0: no)
define('ALLOW_IMAGE_REPLIES', 1); // Allow replies with images (1: yes, 0: no)
define('ALLOW_UNKNOWN', 0); // Allow unknown filetypes (1: yes, 0: no)
define('MUNGE_UNKNOWN', '.unknown'); // Munge unknown file type extensions with this.
$config['FORBIDDEN_EXTENSIONS'] = ['php','php3','php4','phtml','shtml','cgi','pl','pm','py','r','exe','dll','scr','pif','asp','cfm','jsp','vbs']; // file extensions which are forbidden
define('RENZOKU', 5); // Seconds between posts (floodcheck)
define('RENZOKU2', 10); // Seconds between image posts (floodcheck)
define('RENZOKU3', 900); // Seconds between identical posts (floodcheck)
define('NOSAGE_WINDOW', 1200); // Seconds that you can post to your own thread without increasing the sage count
define('USE_SECURE_ADMIN', 1); // Use HTTPS for the admin panel.
define('CHARSET', 'utf-8'); // Character set to use
define('CONVERT_CHARSETS', 1); // Do character set conversions internally
define('TRIM_METHOD', 0); // Which threads to trim (0: oldest - like futaba 1: least active - furthest back)
define('ARCHIVE_MODE', 0); // Old images and posts are moved into an archive dir instead of deleted (0: no 1: yes).
define('DATE_STYLE', 'futaba'); // Date style ('futaba', '2ch', 'localtime', 'tiny')
define('DISPLAY_ID', 'mask'); // How to display user IDs ('', 'day', 'board', 'mask', 'sage', 'link', 'ip', 'host')
// define('DISPLAY_ID', 1); // This was duplicated in config.pl, choose one or clarify logic
define('EMAIL_ID', 'Heaven'); // ID string to use when DISPLAY_ID is 1 and the user uses an email.
define('TRIPKEY', '!'); // this character is displayed before tripcodes
define('ENABLE_WAKABAMARK', 1); // Enable WakabaMark formatting. (0: no, 1: yes)
define('APPROX_LINE_LENGTH', 150); // Approximate line length used by reply abbreviation code
define('STUPID_THUMBNAILING', 0); // Bypass thumbnailing code and just use HTML to resize the image.
define('ALTERNATE_REDIRECT', 1); // Use alternate redirect method. (Javascript/meta-refresh instead of HTTP forwards)
define('COOKIE_PATH', 'root'); // Path argument for cookies ('root', 'current', 'parent')
define('FORCED_ANON', 0); // Force anonymous posting (0: no, 1: yes)
define('SPAM_TRAP', 1); // Enable the spam trap (0:no, 1:yes)
define('SPOILERIMAGE_ENABLED', 1);
define('NSFWIMAGE_ENABLED', 0); // disabled by default

// Internal paths and files - these should map to the new directory structure
define('IMG_DIR', '../uploads/src/'); // Image directory
define('THUMB_DIR', '../uploads/thumb/'); // Thumbnail directory
define('RES_DIR', '../templates/res/'); // Reply cache directory (will store HTML snippets for threads)
define('ARCHIVE_DIR', '../uploads/arch/'); // Root of archive directories
//define('REDIR_DIR', 'redir/'); // Redir directory, used for redirecting clients when load balancing - skipping for now
define('HTML_SELF', 'index.php'); // Name of main script file (changed from wakaba.html)
define('PAGE_EXT', '.html'); // Extension used for board pages after first
define('ERRORLOG', ''); // Writes out all errors seen by user, mainly useful for debugging

// Web paths for JS (relative to domain root, e.g., '/uploads/src/')
define('IMG_DIR_WEBPAT', 'uploads/src/');
define('THUMB_DIR_WEBPAT', 'uploads/thumb/');
define('RES_DIR_WEBPAT', 'res/'); // If using static .html files for threads
define('CSS_DIR_WEBPAT', 'css/'); // Path to CSS files, relative to public/ or domain root
define('JS_DIR_WEBPAT', 'js/');   // Path to JS files

// Icons for filetypes
$config['FILETYPES'] = [
    // Audio files
    'mp3' => 'icons/audio-mp3.png',
    'ogg' => 'icons/audio-ogg.png',
    'aac' => 'icons/audio-aac.png',
    'm4a' => 'icons/audio-aac.png',
    'mpc' => 'icons/audio-mpc.png',
    'mpp' => 'icons/audio-mpp.png',
    'mod' => 'icons/audio-mod.png',
    'it' => 'icons/audio-it.png',
    'xm' => 'icons/audio-xm.png',
    'fla' => 'icons/audio-flac.png',
    'flac' => 'icons/audio-flac.png',
    'sid' => 'icons/audio-sid.png',
    'mo3' => 'icons/audio-mo3.png',
    'spc' => 'icons/audio-spc.png',
    'nsf' => 'icons/audio-nsf.png',
    // Archive files
    'zip' => 'icons/archive-zip.png',
    'rar' => 'icons/archive-rar.png',
    'lzh' => 'icons/archive-lzh.png',
    'lha' => 'icons/archive-lzh.png',
    'gz' => 'icons/archive-gz.png',
    'bz2' => 'icons/archive-bz2.png',
    '7z' => 'icons/archive-7z.png',
    // Other files
    'swf' => 'icons/flash.png',
    'torrent' => 'icons/torrent.png',
    // To stop Wakaba from renaming image files, put their names in here like this:
    'gif' => '.',
    'jpg' => '.',
    'png' => '.',
];

define('DISPLAY_VERSION', 1);

// This $config array will hold settings that are better represented as arrays
// or require more complex initialization.
$config_global = []; // Using a distinct name to avoid conflict if 'config.php' is included in a function scope

// For settings that were arrays in Perl (like FORBIDDEN_EXTENSIONS, FILETYPES, LOAD_HOSTS)
// We use a $config_global array or define them as PHP arrays directly.
$config_global['FORBIDDEN_EXTENSIONS'] = ['php','php3','php4','phtml','shtml','cgi','pl','pm','py','r','exe','dll','scr','pif','asp','cfm','jsp','vbs'];
$config_global['FILETYPES'] = $config['FILETYPES']; // Copying from the above definition for now
// $config_global['LOAD_HOSTS'] = [['http://somesite/loader.php', 'password', 100]]; // Example

// Ensure essential passwords are set (reminders)
if (NUKE_PASS == '') {
    // In a real application, you might throw an error or log this
    // For now, this is a placeholder comment
    // trigger_error("NUKE_PASS is not set in config.php. Please set it now!", E_USER_WARNING);
}

if (SECRET == '') {
    // trigger_error("SECRET is not set in config.php. Please set it now!", E_USER_WARNING);
}

// -- Database Settings --
// Replace with your actual database connection details.
// SQL_DBTYPE is used by the Database class to adjust SQL syntax if necessary (e.g. for autoincrement)
// Valid values for SQL_DBTYPE: 'mysql', 'pgsql', 'sqlite'
define('SQL_DBTYPE', 'mysql'); // Or 'pgsql', 'sqlite'
define('DB_DSN', 'mysql:host=localhost;dbname=wakaba_php;charset=utf8mb4');
define('DB_USER', 'wakaba_user');
define('DB_PASS', 'wakaba_password');

// Original SQL table names (can be kept or changed)
define('SQL_TABLE_POSTS', 'comments'); // For posts and threads
define('SQL_TABLE_ADMIN', 'admin');     // For admin session data, etc.
define('SQL_TABLE_USERS', 'users');     // For admin user accounts
define('SQL_TABLE_PROXY', 'proxy');     // For proxy check data
define('SQL_TABLE_REPORTS', 'reports'); // For user reports
define('SQL_TABLE_CAPTCHA', 'captcha'); // For captcha data

// The $config_global array can store DSN if preferred, or other DB related arrays
$config_global['DB_OPTIONS'] = [
    // PDO::ATTR_PERSISTENT => true, // Example: uncomment for persistent connections
];

// -- CAPTCHA Settings --
define('ENABLE_CAPTCHA', 'builtin'); // false, 'builtin', or 'recaptcha' (reCAPTCHA not implemented yet)
define('CAPTCHA_LIFETIME', 1440);    // Seconds before CAPTCHA expires
define('SQL_TABLE_CAPTCHA', 'captcha'); // Name of the CAPTCHA table in the database
define('CAPTCHA_TTF_FONT', 'dummy_font.ttf'); // Place in resources/fonts/
define('CAPTCHA_WIDTH', 150);      // Width of the CAPTCHA image
define('CAPTCHA_HEIGHT', 30);       // Height of the CAPTCHA image (Perl default was 18)
define('CAPTCHA_LENGTH', 6);        // Number of characters in CAPTCHA text
define('CAPTCHA_NOISE_LINES', 5);   // Number of noise lines
define('CAPTCHA_NOISE_PIXELS', 150); // Number of noise pixels/dots

// String constants (can be moved to a language file later)
if (!defined('S_BADCAP')) define('S_BADCAP', 'Invalid CAPTCHA response.');


// The SQL settings will need to be adapted for PHP's database extensions (PDO or MySQLi)
// For example:
// $config_global['SQL_DSN'] = 'mysql:host=localhost;dbname=your_database;charset=utf8mb4';
// $config_global['SQL_USER'] = 'your_username';
// $config_global['SQL_PASS'] = 'your_password';


// Make constants available globally if this file is included within a function/method
foreach (get_defined_constants(true)['user'] as $key => $value) {
    if (!defined($key)) {
        define($key, $value);
    }
}

// Make $config_global available globally
$GLOBALS['config_global'] = $config_global;

?>
