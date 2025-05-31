<?php
// templates/admin/parts/manager_head.php
// Translates MANAGER_HEAD_INCLUDE from admin_style.pl

// Expected variables:
// $admin_session (array, e.g., from $_SESSION containing 'admin_username', 'admin_class', etc.)
// $self_url (string, path to index.php for links)
// $current_admin_token (string, the token used to authenticate, for constructing links if needed, though session is primary)
// Constants: DOMAIN, BOARDS, HTML_SELF, S_MANARET, S_MANAPANEL, etc.

$admin_username = $admin_session['admin_username'] ?? 'Admin';
$admin_class = $admin_session['admin_class'] ?? 'unknown';
$current_board_dir = defined('BOARD_DIR') ? BOARD_DIR : ''; // Current board context

// For links that need the admin token (if not relying purely on session for all auth)
$admin_link_param = isset($current_admin_token) ? '&amp;admin=' . urlencode($current_admin_token) : '';
if (empty($admin_link_param) && isset($_SESSION['admin_token_for_url'])) { // Fallback if using a session stored token for URLs
    $admin_link_param = '&amp;admin=' . urlencode($_SESSION['admin_token_for_url']);
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=<?= defined('CHARSET') ? CHARSET : 'utf-8' ?>">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= htmlspecialchars($page_title ?? 'Admin Panel') ?> - <?= defined('SITE_NAME') ? SITE_NAME : 'Wakaba' ?></title>
    <link rel="shortcut icon" href="<?= defined('FAVICON') ? expand_filename(FAVICON) : '../favicon.ico' ?>"> <?php // Adjusted path for admin context ?>
    <link rel="stylesheet" href="../css/wakaba.css"> <?php // Adjusted path ?>
    <?php if (isset($stylesheets) && is_array($stylesheets)): ?>
        <?php foreach ($stylesheets as $style): ?>
             <?php if (basename($style['filename']) !== 'wakaba.css'): ?>
            <link rel="<?= !$style['default'] ? 'alternate ' : '' ?>stylesheet" type="text/css" href="../css/<?= basename($style['filename']) ?>" title="<?= htmlspecialchars($style['title']) ?>">
             <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
    <link rel="stylesheet" href="../css/manage.css"> <?php // Adjusted path ?>
    <script src="../js/jquery.jqote2.min.js"></script> <?php // Assuming local and adjusted path ?>
    <script type="text/javascript">
        var style_cookie = "<?= defined('STYLE_COOKIE') ? STYLE_COOKIE : 'wakabastyle' ?>";
        var sitevars = {
            "boarddir": "<?= defined('BOARD_DIR') ? BOARD_DIR : '' ?>",
            "domain": "//<?= defined('DOMAIN') ? DOMAIN : ($_SERVER['HTTP_HOST'] ?? '') ?>",
            "self": "<?= $self_url ?? 'index.php' ?>", // Ensure self_url is passed to this template
            "admin": 1, // Since this is an admin page part
            "htmlext": "<?= defined('PAGE_EXT') ? PAGE_EXT : '.html' ?>",
            "imgdir": "<?= defined('IMG_DIR_WEBPAT') ? IMG_DIR_WEBPAT : '../uploads/src/' ?>", // Adjusted path
            "thumbdir": "<?= defined('THUMB_DIR_WEBPAT') ? THUMB_DIR_WEBPAT : '../uploads/thumb/' ?>", // Adjusted path
            "resdir": "<?= defined('RES_DIR_WEBPAT') ? RES_DIR_WEBPAT : '../res/' ?>" // Adjusted path
        };
    </script>
    <script type="text/javascript" src="../js/<?= defined('JS_FILE') ? JS_FILE : 'glaukaba.js' ?>"></script> <?php // Adjusted path ?>
    <?php /* <script type="text/javascript" src="../js/glaukaba-admin.js"></script> */ ?>
</head>
<body>
<a id="top"></a>

<?php if (isset($admin_session['admin_user_id'])): // Only show if logged in ?>
<div class="topNavContainer" id="adminNav">
    <div class="topNavLeft">
        <span style="vertical-align: middle">
        <strong>Navigation:&nbsp;&nbsp;</strong>
        <select id="managerBoardList" onchange="if (this.value) window.location = '//<?= DOMAIN ?>/'+this.value+'/<?= basename($self_url) ?>?task=mpanel<?= $admin_link_param ?>'">
            <option value="">Boards</option>
            <?php if ($admin_class === 'janitor' && isset($admin_session['boards']) && is_array($admin_session['boards'])): ?>
                <?php foreach ($admin_session['boards'] as $board_info): ?>
                    <option value="<?= htmlspecialchars($board_info['dir']) ?>" <?= ($board_info['dir'] == $current_board_dir) ? 'selected' : '' ?>>/<?= htmlspecialchars($board_info['dir']) ?>/</option>
                <?php endforeach; ?>
            <?php elseif ($admin_class !== 'janitor' && defined('BOARDS') && is_array(BOARDS)): ?>
                <?php foreach (BOARDS as $board_cfg): ?>
                    <option value="<?= htmlspecialchars($board_cfg['dir']) ?>" <?= ($board_cfg['dir'] == $current_board_dir) ? 'selected' : '' ?>>/<?= htmlspecialchars($board_cfg['dir']) ?>/ - <?= htmlspecialchars($board_cfg['name']) ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        </span>
    </div>
    <div class="topNavRight">
        <span style="vertical-align: middle">
        <?php /* TODO: Add notifications for messages, reports, ban requests if $admin_session has that info */ ?>
        <?php /* Example: if ($admin_session['new_messages'] > 0): ?>[<a href="<?= $self_url ?>?task=inbox<?= $admin_link_param ?>">New Messages</a>]<?php endif; */ ?>
        Logged in as: <strong><?= htmlspecialchars($admin_username) ?></strong> (<?= htmlspecialchars($admin_class) ?>)
        </span>
    </div>
</div>
<div class="logo adminLogo">
    <span class="title"><?= defined('TITLE') ? TITLE : 'Admin Panel' ?></span>
</div>
<hr style="margin-top: 10px; margin-bottom: 20px;" />

[<a href="<?= defined('HTML_SELF') ? expand_filename(HTML_SELF) : 'index.php' ?>"><?= defined('S_MANARET') ? S_MANARET : 'Return to Board' ?></a>]
[<a href="<?= $self_url ?>?task=mpanel<?= $admin_link_param ?>"><?= defined('S_MANAPANEL') ? S_MANAPANEL : 'Admin Panel' ?></a>]

<?php if ($admin_class === 'mod' || $admin_class === 'admin'): ?>
    [<a href="<?= $self_url ?>?task=admin_ban_panel<?= $admin_link_param ?>"><?= defined('S_MANABANS') ? S_MANABANS : 'Bans' ?></a>]
    [<a href="<?= $self_url ?>?task=passlist<?= $admin_link_param ?>">Pass List</a>]
    [<a href="<?= $self_url ?>?task=rebuild<?= $admin_link_param ?>"><?= defined('S_MANAREBUILD') ? S_MANAREBUILD : 'Rebuild Cache' ?></a>]
    [<a href="<?= $self_url ?>?task=listrequests<?= $admin_link_param ?>">Ban Requests</a>]
    [<a href="<?= $self_url ?>?task=viewlog<?= $admin_link_param ?>">View Log</a>]
<?php endif; ?>
<?php if ($admin_class === 'admin'): ?>
    [<a href="<?= $self_url ?>?task=proxy<?= $admin_link_param ?>"><?= defined('S_MANAPROXY') ? S_MANAPROXY : 'Proxy' ?></a>]
    [<a href="<?= $self_url ?>?task=spam<?= $admin_link_param ?>"><?= defined('S_MANASPAM') ? S_MANASPAM : 'Spam Filter' ?></a>]
    [<a href="<?= $self_url ?>?task=sqldump<?= $admin_link_param ?>"><?= defined('S_MANASQLDUMP') ? S_MANASQLDUMP : 'SQL Dump' ?></a>]
    [<a href="<?= $self_url ?>?task=sql<?= $admin_link_param ?>"><?= defined('S_MANASQLINT') ? S_MANASQLINT : 'SQL Interface' ?></a>]
<?php endif; ?>

[<a href="<?= $self_url ?>?task=manageusers<?= $admin_link_param ?>"><?= ($admin_class === 'admin' ? 'Manage Users' : 'User List') ?></a>]
[<a href="<?= $self_url ?>?task=edituser&amp;user=<?= urlencode($admin_username) ?><?= $admin_link_param ?>">Edit Profile</a>]
[<a href="<?= $self_url ?>?task=inbox<?= $admin_link_param ?>">Inbox</a>]
[<a id="reportQueueButton" href="<?= $self_url ?>?task=viewreports<?= $admin_link_param ?>"><?= defined('S_REPORTS') ? S_REPORTS : 'Reports' ?></a>]
[<a href="<?= $self_url ?>?task=logout&amp;type=admin">Logout</a>] <?php // Logout type might be handled differently ?>

<div class="passvalid"><?= defined('S_MANAMODE') ? S_MANAMODE : 'Management Mode' ?></div><br />
<?php endif; // end if logged in ?>
<div id="mainAdminContent" style="padding: 10px;">
    <?php // Main admin panel content will go here ?>
</div>
