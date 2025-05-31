<?php
// templates/banned_page.php
// Based on BAN_PAGE_TEMPLATE from futaba_style.pl

// Expected variables:
// $page_title (string)
// $ip_address (string, user's IP)
// $ban_info (array, details of the active ban, or an array of bans if multiple apply though typically first one is shown)
// $stylesheets (array)
// Constants: DOMAIN, SITE_NAME, CHARSET, FAVICON, S_RETURN etc.

$page_title = $page_title ?? 'You Are Banned';
$ip_address = $ip_address ?? (function_exists('get_user_ip') ? get_user_ip() : 'Unknown');
$ban_details_to_show = [];

if (isset($ban_info) && is_array($ban_info)) {
    // If $ban_info is a single ban, wrap it in an array for consistent looping
    if (isset($ban_info['num'])) { // Heuristic to check if it's a single ban entry
        $ban_details_to_show[] = $ban_info;
    } elseif (!empty($ban_info)) { // It might already be an array of bans (though check_ip_ban returns one)
        $ban_details_to_show = $ban_info;
    }
}

// Include minimal head, similar to MINIMAL_HEAD_INCLUDE
// This could be a separate file: templates/parts/minimal_head.php
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=<?= defined('CHARSET') ? CHARSET : 'utf-8' ?>">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= htmlspecialchars($page_title) ?> - <?= defined('SITE_NAME') ? SITE_NAME : 'Wakaba' ?></title>
    <link rel="shortcut icon" href="<?= defined('FAVICON') ? expand_filename(FAVICON) : 'favicon.ico' ?>">
    <style type="text/css">
        body { font-family: sans-serif; margin: 0; padding: 0; background: #F0E0D6; color: #800000; }
        .container { text-align: center; margin: 50px auto; padding: 20px; background: #F0F0F0; border: 1px solid #D0B0A0; width: 80%; max-width: 800px; }
        h1 { color: red; }
        .ban-details p { margin: 10px 0; font-size: 10pt; }
        .ban-image { float: left; width: 45%; margin-right: 5%; }
        .ban-text { float: left; width: 50%; text-align: left;}
        hr { border: 0; border-top: 1px solid #D0B0A0; margin: 20px 0; }
        .return-link { margin-top: 20px; }
        .clear { clear:both; }
    </style>
    <?php /* Stylesheet links from config could be added here if needed */ ?>
</head>
<body>
<div class="container">
    <h1>You (<?= htmlspecialchars($ip_address) ?>) have been banned!</h1>
    <hr>
    <div class="ban-content">
        <div class="ban-image">
            <?php if (defined('BANNED_IMAGE_PATH') && BANNED_IMAGE_PATH): // Configurable banned image ?>
                <img src="<?= htmlspecialchars(BANNED_IMAGE_PATH) ?>" alt="Banned">
            <?php else: // Default generic image or message ?>
                <img src="//<?= defined('DOMAIN') ? DOMAIN : '' ?>/img/banned.png" alt="Banned" style="max-width:100%; height:auto;">
                <?php // Fallback if banned.png is not available, or use a text message ?>
            <?php endif; ?>
        </div>
        <div class="ban-text">
            <?php if (!empty($ban_details_to_show)): ?>
                <?php foreach ($ban_details_to_show as $ban_detail): ?>
                    <p>
                        You were <?= (isset($ban_detail['warning']) && $ban_detail['warning'] == 1) ? 'warned' : 'banned' ?>
                        on <?= htmlspecialchars(make_date((int)($ban_detail['timestamp'] ?? time()), defined('DATE_STYLE') ? DATE_STYLE : 'futaba')) ?>.
                        <br>
                        <strong>Reason:</strong> "<em><?= htmlspecialchars($ban_detail['comment'] ?? 'No reason specified.') ?></em>"
                        <?php if (isset($ban_detail['perm']) && $ban_detail['perm'] == 0 && isset($ban_detail['warning']) && $ban_detail['warning'] != 1 && isset($ban_detail['duration'])): ?>
                            <br>Your ban will expire on <?= htmlspecialchars(make_date((int)$ban_detail['duration'], defined('DATE_STYLE') ? DATE_STYLE : 'futaba')) ?>.
                        <?php elseif (isset($ban_detail['perm']) && $ban_detail['perm'] == 1): ?>
                            <br>This ban is permanent.
                        <?php endif; ?>
                    </p>
                <?php endforeach; ?>
            <?php else: ?>
                <p>You have been banned from accessing this service. Please contact the administrator if you believe this is an error.</p>
            <?php endif; ?>
        </div>
        <div class="clear"></div>
    </div>
    <hr>
    <div class="return-link">
        <a href="<?= (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : ('//' . (defined('DOMAIN') ? DOMAIN : '')) ?>">
            <?= defined('S_RETURN') ? S_RETURN : 'Return to previous page' ?>
        </a>
    </div>
</div>

<?php // Minimal footer ?>
</body>
</html>
