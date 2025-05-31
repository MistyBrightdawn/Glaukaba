<?php
// templates/admin/admin_login_form.php
// Based on ADMIN_LOGIN_TEMPLATE from admin_style.pl

// Assume $page_title and $error_message might be passed
$page_title = $page_title ?? 'Admin Login';
$error_message = $error_message ?? null;

// For self path in form action
$self_url = defined('HTML_SELF') ? HTML_SELF : 'index.php';

// Minimal head, similar to MANAGER_HEAD_INCLUDE for structure
// but simplified as we are not logged in yet.
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=<?= defined('CHARSET') ? CHARSET : 'utf-8' ?>">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= htmlspecialchars($page_title) ?> - <?= defined('SITE_NAME') ? SITE_NAME : 'Wakaba' ?></title>
    <link rel="shortcut icon" href="<?= defined('FAVICON') ? expand_filename(FAVICON) : 'favicon.ico' ?>">
    <style type="text/css">
        body { font-family: sans-serif; margin: 2em; background-color: #f0f0f0; }
        .login-container { background-color: #fff; padding: 20px; border: 1px solid #ccc; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 300px; margin: 50px auto; }
        .login-container h1 { text-align: center; color: #333; margin-bottom: 1em; }
        .login-container table { width: 100%; }
        .login-container td { padding: 5px; }
        .login-container .postBlock { text-align: right; font-weight: bold; padding-right: 10px; }
        .login-container .postField input[type="text"],
        .login-container .postField input[type="password"] { width: 95%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .login-container input[type="submit"] { padding: 10px 15px; background-color: #5cb85c; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; }
        .login-container input[type="submit"]:hover { background-color: #4cae4c; }
        .error { color: red; text-align: center; margin-bottom: 10px; }
        .adminlogin-form { text-align:center; }
        .adminlogin-form > form { display:inline-block; }
    </style>
    <?php /* Links to global CSS could be added if defined and needed */ ?>
</head>
<body>

<div class="logo adminLogo" style="text-align:center;">
    <span class="title" style="font-size:1.5em; font-weight:bold;"><?= defined('TITLE') ? TITLE : 'Control Panel' ?></span>
</div>
<hr style="margin-top: 10px; margin-bottom: 20px;" />

<div class="adminlogin-form">
    <h1><?= htmlspecialchars($page_title) ?></h1>
    <?php if ($error_message): ?>
        <p class="error"><?= htmlspecialchars($error_message) ?></p>
    <?php endif; ?>

    <form id="login" action="<?= htmlspecialchars($self_url) ?>" method="post">
        <input type="hidden" name="task" value="admin_perform_login" />
        <?php // nexttask is usually mpanel, could be set here or handled by logic
              // <input type="hidden" name="nexttask" value="mpanel" /> ?>
        <table style="border-spacing: 2px">
            <tbody>
                <tr>
                    <td class="postBlock">Username</td>
                    <td class="postField"><input type="text" name="user" class="postInput"></td>
                </tr>
                <tr>
                    <td class="postBlock">Password</td>
                    <td class="postField"><input type="password" name="berra" class="postInput"></td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align:left; padding-top:10px;">
                        <label for="saveLogin">
                            <input type="checkbox" name="savelogin" id="saveLogin" value="1">
                            Remember me
                        </label>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align:center; padding-top:15px;">
                        <input type="submit" value="Login">
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</div>

<?php // Minimal footer, similar to NORMAL_FOOT_INCLUDE for structure ?>
<hr style="margin-top: 30px;">
<div style="text-align:center; font-size:0.8em; color:#555;">
    Powered by php_wakaba
</div>

</body>
</html>
