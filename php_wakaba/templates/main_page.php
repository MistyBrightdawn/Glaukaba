<?php
// Main Page Template for php_wakaba
// Derived from PAGE_TEMPLATE in futaba_style.pl

// Ensure all required variables are at least defined to avoid errors,
// using null coalescing in the template itself is generally better.
// Example of data expected by this template (see build_main_page_html for actual provision):
// $title (string, page title part)
// $stylesheets (array of ['title' => ..., 'filename' => ..., 'default' => bool])
// $self (string, path to index.php)
// $admin (bool, is admin?) - Not fully used yet
// $thread_id (int, current thread ID if in thread view, else 0 or null) - Mapped from $thread in Perl
// $indexpage (bool, is this an index page?)
// $noextra (bool, for BOARD_OPTIONS and JS) - Simplified for now
// $prevpage (string|null, URL to previous page)
// $nextpage (string|null, URL to next page)
// $pages (array of ['page'=>int, 'filename'=>string, 'current'=>bool])
// $postform (bool, show post form?)
// $image_inp (bool, show image input in form?)
// $textonly_inp (bool, show "no file" checkbox?)
// $threads_data (array of threads, where each thread has 'posts' array and 'omit' counts)
// Constants like CHARSET, TITLE, SUBTITLE, DOMAIN, etc., are assumed to be defined globally from config.php

// Template Helpers (assumed loaded, e.g. via an autoloader or direct include in controller)
// make_date(), get_filename(), expand_filename(), truncate_line(), get_reply_link(), sprintf()

// Default $noextra if not set, affects BOARD_OPTIONS display
$noextra = $noextra ?? false;
$thread_id = $thread_id ?? null; // Equivalent to $thread in Perl template

// For get_reply_link helper (to be created)
function get_reply_link($num, $parent_num) {
    $base = defined('HTML_SELF') ? HTML_SELF : 'index.php';
    if ($parent_num) { // It's a reply
        return $base . '?task=view_thread&num=' . $parent_num . '#p' . $num;
    } else { // It's an OP
        return $base . '?task=view_thread&num=' . $num;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=<?= CHARSET ?>">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="description" content="<?= defined('SUBTITLE') ? SUBTITLE : '' ?>">
    <title><?php if (isset($page_title) && $page_title): ?><?= htmlspecialchars($page_title) ?> - <?php endif; ?><?= defined('TITLE') ? TITLE : 'Wakaba' ?></title>
    <link rel="shortcut icon" href="<?= expand_filename(defined('FAVICON') ? FAVICON : 'favicon.ico') ?>">
    <style type="text/css">
        form { margin-bottom: 0px }
        form .trap { display:none }
        .reflink a { color: inherit; text-decoration: none }
        .reply .filesize { margin-left: 20px }
        .userdelete { float: right; text-align: center; white-space: nowrap }
        .replypage .replylink { display: none } /* This might be handled by JS or specific page context */
        .sjis { font-family: Mona,'MS PGothic' !important; font-size: 12pt; } /* Specific to Shift_JIS which we are not focusing on */
        .recaptchatable { border: none; }
    </style>
    <?php // Base CSS - assuming it's always 'wakaba.css' or a similar default from the copied assets ?>
    <link rel="stylesheet" type="text/css" href="css/wakaba.css" title="<?= defined('DEFAULT_STYLE') ? htmlspecialchars(DEFAULT_STYLE) : 'Wakaba' ?>">
    <?php if (isset($stylesheets) && is_array($stylesheets)): ?>
        <?php foreach ($stylesheets as $style): ?>
            <?php if (basename($style['filename']) !== 'wakaba.css'): // Avoid duplicating the base if it's listed ?>
            <link rel="<?= !$style['default'] ? 'alternate ' : '' ?>stylesheet" type="text/css" href="css/<?= basename($style['filename']) ?>" title="<?= htmlspecialchars($style['title']) ?>">
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
    <link rel="stylesheet" href="css/mobile.css">
    <link href="css/prettify.css" type="text/css" rel="stylesheet">

    <script type="text/javascript">
        var sitevars = {
            "sitename": "<?= defined('SITE_NAME') ? SITE_NAME : 'Wakaba' ?>",
            "domain": "//<?= defined('DOMAIN') ? DOMAIN : ($_SERVER['HTTP_HOST'] ?? '') ?>", // Use current host if DOMAIN undefined
            "boarddir": "<?= defined('BOARD_DIR') ? BOARD_DIR : '' ?>",
            "boardpath": "//<?= defined('DOMAIN') ? DOMAIN : ($_SERVER['HTTP_HOST'] ?? '') ?>/<?= defined('BOARD_DIR') ? BOARD_DIR : '' ?>/",
            "self": "<?= htmlspecialchars($self ?? 'index.php') ?>", // $self should be index.php
            "admin": <?php echo (isset($admin_session) && !empty($admin_session['admin_user_id'])) ? '1' : '0'; ?>,
            "htmlext": "<?= defined('PAGE_EXT') ? PAGE_EXT : '.html' ?>", // For JS link generation
            "imgdir": "<?= defined('IMG_DIR_WEBPAT') ? IMG_DIR_WEBPAT : 'uploads/src/' ?>", // Web path to image dir
            "thumbdir": "<?= defined('THUMB_DIR_WEBPAT') ? THUMB_DIR_WEBPAT : 'uploads/thumb/' ?>", // Web path to thumb dir
            "resdir": "<?= defined('RES_DIR_WEBPAT') ? RES_DIR_WEBPAT : 'res/' ?>", // Web path to res dir (if static files)
            <?php if (defined('ENABLE_CAPTCHA') && ENABLE_CAPTCHA == 'recaptcha'): ?>"captcha": 1,<?php endif; ?>
            <?php if (defined('PREVALIDATE_RECAPTCHA') && PREVALIDATE_RECAPTCHA): ?>"preval": 1,<?php endif; ?>
            "textonly": <?php echo (isset($textonly_inp) && $textonly_inp) ? '1' : '0'; ?>,
            "spoiler": <?php echo (defined('SPOILERIMAGE_ENABLED') && SPOILERIMAGE_ENABLED) ? '1' : '0'; ?>,
            "nsfw": <?php echo (defined('NSFWIMAGE_ENABLED') && NSFWIMAGE_ENABLED) ? '1' : '0'; ?>,
            "social": <?php echo (defined('SOCIAL') && SOCIAL) ? '1' : '0'; ?>,
            "noext": <?php echo (defined('REWRITTEN_URLS') && REWRITTEN_URLS) ? '1' : '0'; ?>
        };
    </script>

    <?php if (!$noextra): ?>
        <script src="js/jquery.jqote2.min.js"></script> <?php // Assuming local copy ?>
    <?php endif; ?>
    <script type="text/javascript">var style_cookie = "<?= defined('STYLE_COOKIE') ? STYLE_COOKIE : 'wakabastyle' ?>";</script>
    <script type="text/javascript" src="js/<?= defined('JS_FILE') ? JS_FILE : 'glaukaba.js' ?>"></script>
    <?php if (!$noextra): ?>
        <script type="text/javascript" src="js/<?= defined('EXTRA_JS_FILE') ? EXTRA_JS_FILE : 'extra.js' ?>"></script>
        <?php if (defined('SHOWTITLEIMG') && SHOWTITLEIMG == 2 && defined('TITLEIMGSCRIPT')): ?>
            <script type="text/javascript" src="js/<?= TITLEIMGSCRIPT ?>"></script>
        <?php endif; ?>
        <script type="text/javascript" src="js/prettify/prettify.js"></script>
    <?php endif; ?>
</head>

<body class="<?= $indexpage ? 'indexpage' : ($thread_id ? 'replypage' : '') ?>">
<a id="top"></a>

<div id="topNavStatic" class="staticNav">
    [<?php if (defined('BOARDS') && is_array(BOARDS)): $is_first_board = true; foreach (BOARDS as $board_item): ?>
        <?php if (!$is_first_board): ?> / <?php endif; ?><a href="//<?= DOMAIN ?>/<?= htmlspecialchars($board_item['dir']) ?>/" title="/<?= htmlspecialchars($board_item['dir']) ?>/ - <?= htmlspecialchars($board_item['name']) ?>"><?= htmlspecialchars($board_item['dir']) ?></a>
    <?php $is_first_board = false; endforeach; endif; ?>]
    <?php if (defined('LINKS') && is_array(LINKS) && !empty(LINKS)): ?>
    [<?php $is_first_link = true; foreach (LINKS as $link_item): ?>
        <?php if (!$is_first_link): ?> / <?php endif; ?><a href="<?= htmlspecialchars($link_item['url']) ?>" <?php if (!empty($link_item['rel'])): ?>rel="<?= htmlspecialchars($link_item['rel']) ?>"<?php endif; ?>><?= htmlspecialchars($link_item['name']) ?></a>
    <?php $is_first_link = false; endforeach; ?>]
    <?php endif; ?>
    <div style="float:right">
        [<a href="javascript:void(0)" onclick="toggleNavMenu(this,0);">Settings</a>]
        [<a href="//<?= DOMAIN ?>">Home</a>]
    </div>
</div>

<div class="topNavContainer">
    <?php include WAKABA_BASE_DIR . '/templates/parts/header.php'; ?>
    <div class="topNavRight">
        <span>[<a href="javascript:void(0)" onclick="toggleNavMenu(this,0);">Settings</a>]</span>
    </div>
</div>

<div class="logo">
    <?php if (defined('SHOWTITLEIMG') && SHOWTITLEIMG): ?>
        <div id="image"><img src="<?= TITLEIMG ?>" class='banner' alt="<?= TITLE ?>"></div>
    <?php endif; ?>
    <h1 class="title"><?= TITLE ?></h1>
    <h2 class="logoSubtitle"><?= SUBTITLE ?></h2>
    <?php if (!$noextra && defined('SHOWTITLEIMG') && SHOWTITLEIMG == 2 && defined('TITLEIMGSCRIPT')): ?>
        <script type="text/javascript" src="/js/<?= TITLEIMGSCRIPT ?>"></script>
    <?php endif; ?>
</div>

<?php if (!$thread_id && $indexpage): ?>
    <div id="topPageNumber" class="pageNumber">
        <?php if (isset($prevpage) && $prevpage): ?><button onclick="location.href='<?= htmlspecialchars($prevpage) ?>'"><?= S_PREV ?></button><?php else: ?><?= S_FIRSTPG ?><?php endif; ?>
        <?php if (isset($pages) && is_array($pages)): foreach ($pages as $page_item): ?>
            <?php if (!$page_item['current']): ?>[<a href="<?= htmlspecialchars($page_item['filename']) ?>"><?= $page_item['page'] ?></a>]<?php else: ?>[<?= $page_item['page'] ?>]<?php endif; ?>
        <?php endforeach; endif; ?>
        <?php if (isset($nextpage) && $nextpage): ?><button onclick="location.href='<?= htmlspecialchars($nextpage) ?>'"><?= S_NEXT ?></button><?php else: ?><?= S_LASTPG ?><?php endif; ?>

        <?php if (defined('ENABLE_CATALOG') && ENABLE_CATALOG): ?>
            <div class="catalogLink">
                <a href="//<?= DOMAIN ?>/<?= BOARD_DIR ?>/catalog<?= defined('REWRITTEN_URLS') && !REWRITTEN_URLS ? '.html' : '' ?>">Catalog</a>
            </div>
        <?php endif; ?>
        <?php if (defined('ENABLE_LIST') && ENABLE_LIST): ?>
            <div class="catalogLink">
                <a href="//<?= DOMAIN ?>/<?= BOARD_DIR ?>/subback<?= defined('REWRITTEN_URLS') && !REWRITTEN_URLS ? '.html' : '' ?>">Thread List</a>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
<hr class="postinghr">

<?php // BOARD_OPTIONS placeholder - this is a complex component ?>
<?php if (!$noextra): ?>
<div id="boardOptionsPlaceholder" style="text-align:center; padding:10px; border:1px dashed #ccc; margin:10px;">
    Board Options Placeholder (Originally BOARD_OPTIONS constant and JavaScript)
    <div id="overlay" style="display:none;">Nav Menu Overlay Content</div>
</div>
<?php endif; ?>


<?php if (!isset($admin) || !$admin): // Show top ad if not admin ?>
    <div class="denguses"><?php @include WAKABA_BASE_DIR . '/templates/parts/topad.php'; ?></div>
<?php endif; ?>


<div id="content">
    <?php if (isset($thread_id) && $thread_id): ?>
        <div class="desktop threadlinks">
            [<a href="//<?= DOMAIN ?>/<?= BOARD_DIR ?>"><?= S_RETURN ?></a>]
            [<a href="javascript:void(0)" class="gallerylink">Gallery Mode</a>]
            <?php if (defined('ENABLE_CATALOG') && ENABLE_CATALOG): ?>[<a href="//<?= DOMAIN ?>/<?= BOARD_DIR ?>/catalog<?= defined('REWRITTEN_URLS') && !REWRITTEN_URLS ? '.html' : '' ?>">Catalog</a>]<?php endif; ?>
            <?php if (defined('ENABLE_LIST') && ENABLE_LIST): ?>[<a href="//<?= DOMAIN ?>/<?= BOARD_DIR ?>/subback<?= defined('REWRITTEN_URLS') && !REWRITTEN_URLS ? '.html' : '' ?>">Thread List</a>]<?php endif; ?>
            [<a href="#bottom">Bottom</a>]
        </div>
        <div class="mobile threadlinks">
             <a href="//<?= DOMAIN ?>/<?= BOARD_DIR ?>" class="button"><?= S_RETURN ?></a>
             <a href="javascript:void(0)" class="gallerylink button">Gallery</a>
            <?php if (defined('ENABLE_CATALOG') && ENABLE_CATALOG): ?><a class="button" href="//<?= DOMAIN ?>/<?= BOARD_DIR ?>/catalog<?= defined('REWRITTEN_URLS') && !REWRITTEN_URLS ? '.html' : '' ?>">Catalog</a><?php endif; ?>
            <?php if (defined('ENABLE_LIST') && ENABLE_LIST): ?><a class="button" href="//<?= DOMAIN ?>/<?= BOARD_DIR ?>/subback<?= defined('REWRITTEN_URLS') && !REWRITTEN_URLS ? '.html' : '' ?>">Thread List</a><?php endif; ?>
            <a class="button" href="#bottom">Bottom</a>
        </div>
        <div class="theader desktop"><?= S_POSTING ?></div>
    <?php endif; ?>

    <?php if (isset($postform) && $postform): ?>
        <div style="text-align:center">
            <a id="postFormToggle" class="button" onclick="togglePostForm()" href="javascript:void(0)">
                <?= isset($thread_id) && $thread_id ? 'Reply' : 'New Thread' ?>
            </a>
        </div>
        <form action="<?= htmlspecialchars($self ?? '') ?>" method="post" id="post_form" name="post_form" enctype="multipart/form-data">
            <input type="hidden" name="task" value="post">
            <?php if (isset($thread_id) && $thread_id): ?><input type="hidden" name="parent" value="<?= $thread_id ?>"><?php endif; ?>
            <?php if (!isset($image_inp) || !$image_inp && (!isset($thread_id) || !$thread_id) && defined('ALLOW_TEXTONLY') && ALLOW_TEXTONLY): ?>
                <input type="hidden" name="nofile" value="1">
            <?php endif; ?>
            <?php if (defined('FORCED_ANON') && FORCED_ANON): ?><input type="hidden" name="name"><?php endif; ?>
            <?php if (defined('SPAM_TRAP') && SPAM_TRAP): ?>
                <div class="trap"><?= S_SPAMTRAP ?><input type="text" name="name" autocomplete="off"><input type="text" name="link" autocomplete="off"></div>
            <?php endif; ?>

            <div id="postForm" <?php /* Default style for post form might be display:none, toggled by JS */ ?>>
                <?php if (!defined('FORCED_ANON') || !FORCED_ANON): ?>
                    <div class="postrow">
                        <div class="postBlock">Name</div>
                        <div class="postField"><input type="text" class="postInput" name="field1" id="field1"></div>
                    </div>
                <?php endif; ?>
                <div class="postrow">
                    <div class="postBlock">Link</div>
                    <div class="postField"><input type="text" class="postInput" name="field2" id="field2"></div>
                </div>
                <div class="postrow">
                    <div class="postBlock">Subject</div>
                    <div class="postField">
                        <input type="text" name="field3" class="postInput" id="field3">
                        <input type="submit" id="field3s" value="Submit">
                    </div>
                </div>
                <div class="postrow">
                    <div class="postBlock">Comment</div>
                    <div class="postField"><textarea name="field4" class="postInput" id="field4"></textarea></div>
                </div>

                <?php if (defined('ENABLE_CAPTCHA') && ENABLE_CAPTCHA == 'recaptcha'): ?>
                    <div class="postrow" id="recaptchaContainer">
                        <div class="postBlock" id="captchaPostBlock"><?= S_CAPTCHA ?></div>
                        <div class="colspan">
                        <div class="postField">
                             <!-- reCAPTCHA display logic, simplified -->
                            <div>[reCAPTCHA Placeholder: Public Key <?= RECAPTCHA_PUBLIC_KEY ?>]</div>
                            <?php if (defined('PASS_ENABLED') && PASS_ENABLED): ?>
                                <div class="passNotice">
                                    Bypass this CAPTCHA.
                                    [<a href="<?= (defined('REWRITTEN_URLS') && REWRITTEN_URLS ? '//'.DOMAIN.'/pass/' : ($self ?? 'index.php').'?task=getpass') ?>">Learn More</a>]
                                </div>
                            <?php endif; ?>
                        </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (defined('ENABLE_CAPTCHA') && (ENABLE_CAPTCHA === 'builtin' || ENABLE_CAPTCHA === 'captcha') && !(defined('ENABLE_CAPTCHA') && ENABLE_CAPTCHA === 'recaptcha') ): ?>
                    <div class="postrow" id="captchaRow">
                        <div class="postBlock"><?= S_CAPTCHA ?? 'CAPTCHA' ?></div>
                        <div class="postField">
                            <?php
                            $captcha_parent_param = '';
                            if (isset($thread_id) && $thread_id > 0) { // If it's a reply, scope CAPTCHA to thread
                                $captcha_parent_param = '?parent=' . $thread_id;
                            }
                            // Add a random query string to prevent caching by browser/proxies
                            $nocache = substr(md5(mt_rand()), 0, 7);
                            ?>
                            <img src="captcha_image.php<?= $captcha_parent_param ? ($captcha_parent_param . '&amp;nocache=' . $nocache) : ('?nocache=' . $nocache) ?>" alt="CAPTCHA Image" class="captchaImage" onclick="this.src='captcha_image.php<?= $captcha_parent_param ? ($captcha_parent_param . '&amp;nocache=') : '?nocache=' ?>'+Math.random();">
                            <br>
                            <input type="text" name="captcha_input" class="postInput captchaInput" size="10" autocomplete="off">
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($image_inp) && $image_inp): ?>
                    <div class="postrow" id="uploadField">
                        <div class="postBlock">File</div>
                        <div class="postField">
                            <input type="file" name="file" id="file"><br>
                            <?php if (isset($textonly_inp) && $textonly_inp): ?><label>[<input type="checkbox" name="nofile" value="on">No File]</label><?php endif; ?>
                            <?php if (defined('SPOILERIMAGE_ENABLED') && SPOILERIMAGE_ENABLED): ?><label>[<input type="checkbox" name="spoiler" value="1">Spoiler]</label><?php endif; ?>
                            <?php if (defined('NSFWIMAGE_ENABLED') && NSFWIMAGE_ENABLED): ?><label>[<input type="checkbox" name="nsfw" value="1">NSFW]</label><?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="postrow">
                    <div class="postBlock">Password</div>
                    <div class="postField">
                        <input type="password" class="postInput pass-field" id="password" name="password"/>
                        <span class="passDesc">(for post and file deletion)</span>
                    </div>
                </div>
                <div class="rules">
                    <?php @include WAKABA_BASE_DIR . '/templates/parts/rules.php'; ?>
                </div>
            </div>
        </form>
        <script type="text/javascript">setPostInputs()</script> <?php // This JS function would need to be available ?>
    <?php endif; ?>

    <div class="denguses"><?php @include WAKABA_BASE_DIR . '/templates/parts/middlead.php'; ?></div>

    <div id="announcement">
        <?php @include WAKABA_BASE_DIR . '/templates/parts/announcement.php'; // Path might need adjustment ?>
    </div>

    <form id="delform" action="<?= htmlspecialchars($self ?? '') ?>" method="post">
    <input type="hidden" name="task" value="delete">
        <?php if (isset($threads_data) && is_array($threads_data)): ?>
            <?php foreach ($threads_data as $thread): ?>
                <?php // $thread is an associative array with 'posts' (array), 'omit', 'omitimages', etc. ?>
                <?php $op_post = $thread['posts'][0] ?? null; ?>
                <?php if (!$op_post) continue; ?>

                <div class="thread">
                    <?php foreach ($thread['posts'] as $idx => $post): ?>
                        <?php $is_op = ($idx == 0); ?>
                        <?php if ($is_op): ?>
                            <div class="parentContainer">
                            <div class="parentPost post" id="parent<?= $post['num'] ?>">
                                <div class="mobile mobileParentPostInfo">
                                    <input type="checkbox" name="delete[]" value="<?= $post['num'] ?>">
                                    <div class="leftblock">
                                        <?php if (!empty($post['subject'])): ?><span class="filetitle"><?= htmlspecialchars($post['subject']) ?></span><?php endif; ?>
                                        <span class="postername">
                                            <?php if (!empty($post['email'])): ?><a href="<?= htmlspecialchars($post['email']) ?>"><?= htmlspecialchars($post['name']) ?></a><?php else: ?><?= htmlspecialchars($post['name']) ?><?php endif; ?>
                                        </span>
                                        <?php if (!empty($post['trip'])): ?> <span class="postertrip"><?php if (!empty($post['email'])): ?><a href="<?= htmlspecialchars($post['email']) ?>"><?= $post['trip'] ?></a><?php else: ?><?= $post['trip'] ?><?php endif; ?></span><?php endif; ?>
                                        <?php if (!empty($post['id'])): ?><span class="posterid">(ID: <span class="posteridnum"><?= htmlspecialchars($post['id']) ?></span>)</span><?php endif; ?>
                                    </div>
                                    <div class="rightblock">
                                        <span class="date"><?= htmlspecialchars($post['date'] ?? '') ?></span>
                                        <span class="reflink">
                                            <a class="refLinkInner" href="<?= get_reply_link($post['num'], 0) ?>#p<?= $post['num'] ?>">No.<?= $post['num'] ?></a>
                                            <?php if (!empty($post['sticky'])): ?><img src="//<?= DOMAIN ?>/img/sticky.gif" alt="Stickied"/><?php endif; ?>
                                            <?php if (!empty($post['locked'])): ?><img src="//<?= DOMAIN ?>/img/closed.gif" alt="Locked"/><?php endif; ?>
                                        </span>
                                    </div>
                                    <div style="clear:both"></div>
                                </div>

                                <?php if (!empty($post['image'])): ?>
                                    <div class="fileinfo"><span class="filesize"><?= S_PICNAME ?>
                                    <a target="_blank" href="<?= expand_filename($post['image']) ?>" title="<?= htmlspecialchars($post['filename'] ?? '') ?>" class="filename">
                                        <?= htmlspecialchars(!empty($post['filename']) ? truncate_line($post['filename']) : get_filename($post['image'])) ?></a>
                                    - (<?= (int)(($post['size'] ?? 0)/1024) ?> KB, <?= $post['width'] ?? 0 ?>x<?= $post['height'] ?? 0 ?>)</span>
                                    </div>
                                    <?php if (!empty($post['thumbnail'])): ?>
                                        <a target="_blank" class="thumbLink" href="<?= expand_filename($post['image']) ?>">
                                            <?php if (empty($post['tnmask'])): ?>
                                                <img src="<?= expand_filename($post['thumbnail']) ?>" style="width:<?= $post['tn_width'] ?>px; height:<?= $post['tn_height'] ?>px;" data-md5="<?= htmlspecialchars($post['md5'] ?? '') ?>" alt="<?= $post['size'] ?? 0 ?>" class="thumb opThumb">
                                            <?php else: ?>
                                                <img src="//<?= DOMAIN ?>/img/spoiler.png" data-md5="<?= htmlspecialchars($post['md5'] ?? '') ?>" alt="<?= $post['size'] ?? 0 ?>" class="thumb opThumb">
                                            <?php endif; ?>
                                        </a>
                                    <?php elseif (defined('DELETED_THUMBNAIL') && DELETED_THUMBNAIL): ?>
                                         <a target="_blank" class="thumbLink" href="<?= defined('DELETED_IMAGE') ? expand_filename(DELETED_IMAGE) : '#' ?>">
                                            <img src="<?= expand_filename(DELETED_THUMBNAIL) ?>" style="width:<?= $post['tn_width'] ?? 0 ?>px; height:<?= $post['tn_height'] ?? 0 ?>px;" alt="Deleted" class="thumb opThumb">
                                        </a>
                                    <?php else: ?>
                                        <div class="thumb nothumb">
                                            <a target="_blank" class="thumbLink" href="<?= expand_filename($post['image']) ?>"><?= S_NOTHUMB ?></a>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <a id="p<?= $post['num'] ?>"></a>
                                <div class="parentPostInfo">
                                     <input type="checkbox" name="delete[]" value="<?= $post['num'] ?>">
                                    <span class="filetitle"><?= htmlspecialchars($post['subject'] ?? '') ?></span>
                                    <span class="postername">
                                        <?php if (!empty($post['email'])): ?><a href="<?= htmlspecialchars($post['email']) ?>"><?= htmlspecialchars($post['name']) ?></a><?php else: ?><?= htmlspecialchars($post['name']) ?><?php endif; ?>
                                    </span>
                                    <?php if (!empty($post['trip'])): ?> <span class="postertrip"><?php if (!empty($post['email'])): ?><a href="<?= htmlspecialchars($post['email']) ?>"><?= $post['trip'] ?></a><?php else: ?><?= $post['trip'] ?><?php endif; ?></span><?php endif; ?>
                                    <?php if (!empty($post['id'])): ?><span class="posterid">(ID: <span class="posteridnum"><?= htmlspecialchars($post['id']) ?></span>)</span><?php endif; ?>
                                    <span class="date"><?= htmlspecialchars($post['date'] ?? '') ?></span>
                                    <span class="reflink">
                                        <a class="refLinkInner" href="<?= get_reply_link($post['num'], 0) ?>#p<?= $post['num'] ?>">No.<?= $post['num'] ?></a>
                                        <?php if (!empty($post['sticky'])): ?><img src="//<?= DOMAIN ?>/img/sticky.gif" alt="Stickied"/><?php endif; ?>
                                        <?php if (!empty($post['locked'])): ?><img src="//<?= DOMAIN ?>/img/closed.gif" alt="Locked"/><?php endif; ?>
                                    </span>&nbsp;
                                    <?php if (!$thread_id): ?>[<a href="<?= get_reply_link($post['num'], 0) ?>"><?= S_REPLY ?></a>]<?php endif; ?>
                                    <a href="javascript:void(0)" onclick="togglePostMenu(this);" class="postMenuButton" id="postMenuButton<?= $post['num'] ?>">[<span></span>]</a>
                                    <?php /* Placeholder for post menu */ ?>
                                </div>
                                <blockquote class="<?= strpos($post['email'] ?? '', 'aa') !== false ? 'aa' : '' ?>">
                                    <?= $post['comment'] /* This should be pre-sanitized HTML */ ?>
                                    <?php if (!empty($post['abbrev'])): ?>
                                        <div class="abbrev"><?= sprintf(S_ABBRTEXT, get_reply_link($post['num'], $post['parent'])) ?></div>
                                    <?php endif; ?>
                                    <?php /* TODO: Staff replies section if SHOW_STAFF_POSTS */ ?>
                                </blockquote>
                            </div> <?php // .parentPost ?>

                            <?php if (!$thread_id): ?>
                                <div class="mobilePostReplyLink mobile">
                                <?php if (isset($thread['omit']) && $thread['omit']): ?>
                                    <span class="omittedposts mobile">
                                        <?php if (isset($thread['omitimages']) && $thread['omitimages']): ?>
                                            <?= sprintf(S_ABBRIMG_M, $thread['omit'], $thread['omitimages']) ?>
                                        <?php else: ?>
                                            <?= sprintf(S_ABBR_M, $thread['omit']) ?>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                                <a class="button" href="<?= get_reply_link($post['num'], 0) ?>"><?= S_REPLY ?></a>
                                </div>
                            <?php endif; ?>
                             <?php /* Placeholder for mobile post menu */ ?>
                            </div> <?php // .parentContainer ?>
                            <?php if (isset($thread['omit']) && $thread['omit'] && !$thread_id): ?>
                                <span class="omittedposts desktop">
                                     <?php if (isset($thread['omitimages']) && $thread['omitimages']): ?>
                                        <?= sprintf(S_ABBRIMG, $thread['omit'], $thread['omitimages']) ?>
                                    <?php else: ?>
                                        <?= sprintf(S_ABBR, $thread['omit']) ?>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>

                        <?php else: // This is a reply post ?>
                            <div class="replyContainer" id="replyContainer<?= $post['num'] ?>">
                                <div class="doubledash">&gt;&gt;</div>
                                <div class="reply post" id="reply<?= $post['num'] ?>">
                                    <a id="p<?= $post['num'] ?>"></a>
                                    <div class="replyPostInfo">
                                        <input type="checkbox" name="delete[]" value="<?= $post['num'] ?>">
                                        <div class="leftblock">
                                            <?php if (!empty($post['subject'])): ?><span class="replytitle"><?= htmlspecialchars($post['subject']) ?></span><?php endif; ?>
                                            <span class="postername">
                                                <?php if (!empty($post['email'])): ?><a href="<?= htmlspecialchars($post['email']) ?>"><?= htmlspecialchars($post['name']) ?></a><?php else: ?><?= htmlspecialchars($post['name']) ?><?php endif; ?>
                                            </span>
                                            <?php if (!empty($post['trip'])): ?><span class="postertrip"><?php if (!empty($post['email'])): ?><a href="<?= htmlspecialchars($post['email']) ?>"><?= $post['trip'] ?></a><?php else: ?><?= $post['trip'] ?><?php endif; ?></span><?php endif; ?>
                                            <?php if (!empty($post['id'])): ?><span class="posterid">(ID: <span class="posteridnum"><?= htmlspecialchars($post['id']) ?></span>)</span><?php endif; ?>
                                        </div>
                                        <div class="rightblock">
                                            <span class="date"><?= htmlspecialchars($post['date'] ?? '') ?></span>
                                            <span class="reflink">
                                                <a class="refLinkInner" href="<?= get_reply_link($post['num'], $post['parent']) ?>#p<?= $post['num'] ?>">No.<?= $post['num'] ?></a>
                                            </span>
                                            <a href="javascript:void(0)" onclick="togglePostMenu(this);"  class="postMenuButton" id="postMenuButton<?= $post['num'] ?>">[<span></span>]</a>
                                            <?php /* Placeholder for post menu */ ?>
                                        </div>
                                        <div style="clear:both"></div>
                                    </div>
                                     <?php if (!empty($post['image'])): ?>
                                        <div class="fileinfo">
                                            <span class="filesize"><?= S_PICNAME ?>
                                            <a target="_blank" href="<?= expand_filename($post['image']) ?>" title="<?= htmlspecialchars($post['filename'] ?? '') ?>" class="filename">
                                                <?= htmlspecialchars(!empty($post['filename']) ? truncate_line($post['filename']) : get_filename($post['image'])) ?></a>
                                            - (<?= (int)(($post['size'] ?? 0)/1024) ?> KB, <?= $post['width'] ?? 0 ?>x<?= $post['height'] ?? 0 ?>)
                                            </span>
                                        </div>
                                        <?php if (!empty($post['thumbnail'])): ?>
                                            <a class="thumbLink" target="_blank" href="<?= expand_filename($post['image']) ?>">
                                                <?php if (empty($post['tnmask'])): ?>
                                                <img src="<?= expand_filename($post['thumbnail']) ?>" alt="<?= $post['size'] ?? 0 ?>" class="thumb replyThumb" data-md5="<?= htmlspecialchars($post['md5'] ?? '') ?>" style="width: <?= ($post['tn_width'] ?? 0)*0.504 ?>px; height: <?= ($post['tn_height'] ?? 0)*0.504 ?>px;">
                                                <?php else: ?>
                                                <img src="//<?= DOMAIN ?>/img/spoiler.png" alt="<?= $post['size'] ?? 0 ?>" class="thumb replyThumb" data-md5="<?= htmlspecialchars($post['md5'] ?? '') ?>">
                                                <?php endif; ?>
                                            </a>
                                        <?php elseif (defined('DELETED_THUMBNAIL') && DELETED_THUMBNAIL): ?>
                                            <a target="_blank" class="thumbLink" href="<?= defined('DELETED_IMAGE') ? expand_filename(DELETED_IMAGE) : '#' ?>">
                                                <img src="<?= expand_filename(DELETED_THUMBNAIL) ?>" width="<?= ($post['tn_width'] ?? 0) ?>" height="<?= ($post['tn_height'] ?? 0) ?>" alt="Deleted" class="thumb replyThumb">
                                            </a>
                                        <?php else: ?>
                                            <div class="thumb replyThumb nothumb">
                                                <a class="thumbLink" target="_blank" href="<?= expand_filename($post['image']) ?>"><?= S_NOTHUMB ?></a>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <blockquote class="<?= strpos($post['email'] ?? '', 'aa') !== false ? 'aa' : '' ?>">
                                        <?= $post['comment'] /* This should be pre-sanitized HTML */ ?>
                                        <?php if (!empty($post['abbrev'])): ?>
                                            <div class="abbrev"><?= sprintf(S_ABBRTEXT, get_reply_link($post['num'], $post['parent'])) ?></div>
                                        <?php endif; ?>
                                    </blockquote>
                                </div>
                                <?php /* Placeholder for mobile post menu for replies */ ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; // posts loop ?>
                </div> <?php // .thread ?>
                <hr>
            <?php endforeach; // threads_data loop ?>
        <?php endif; ?>

        <div class="denguses"><?php @include WAKABA_BASE_DIR . '/templates/parts/bottomad.php'; ?></div>

        <?php if (isset($thread_id) && $thread_id): ?>
            <div class="desktop threadlinks">
                [<a href="//<?= DOMAIN ?>/<?= BOARD_DIR ?>"><?= S_RETURN ?></a>]
                [<a href="javascript:void(0)" class="gallerylink">Gallery Mode</a>]
                <?php if (defined('ENABLE_CATALOG') && ENABLE_CATALOG): ?>[<a href="//<?= DOMAIN ?>/<?= BOARD_DIR ?>/catalog<?= defined('REWRITTEN_URLS') && !REWRITTEN_URLS ? '.html' : '' ?>">Catalog</a>]<?php endif; ?>
                <?php if (defined('ENABLE_LIST') && ENABLE_LIST): ?>[<a href="//<?= DOMAIN ?>/<?= BOARD_DIR ?>/subback<?= defined('REWRITTEN_URLS') && !REWRITTEN_URLS ? '.html' : '' ?>">Thread List</a>]<?php endif; ?>
                [<a href="#top">Top</a>]
            </div>
            <div class="mobile threadlinks">
                <a href="//<?= DOMAIN ?>/<?= BOARD_DIR ?>" class="button"><?= S_RETURN ?></a>
                <a href="javascript:void(0)" class="gallerylink button">Gallery</a>
                <?php if (defined('ENABLE_CATALOG') && ENABLE_CATALOG): ?><a class="button" href="//<?= DOMAIN ?>/<?= BOARD_DIR ?>/catalog<?= defined('REWRITTEN_URLS') && !REWRITTEN_URLS ? '.html' : '' ?>">Catalog</a><?php endif; ?>
                <?php if (defined('ENABLE_LIST') && ENABLE_LIST): ?><a class="button" href="//<?= DOMAIN ?>/<?= BOARD_DIR ?>/subback<?= defined('REWRITTEN_URLS') && !REWRITTEN_URLS ? '.html' : '' ?>">Thread List</a><?php endif; ?>
                <a class="button" href="#top">Top</a>
                <hr>
            </div>
            <a id="bottom"></a>
        <?php endif; ?>

        <div id="deleteForm">
            Delete Post
            <label>[<input type="checkbox" name="fileonly" value="on"> <?= S_DELPICONLY ?>]</label>
            <?= S_DELKEY ?><input type="password" name="password" id="delPass" class="postInput pass-field"/>
            <input value="<?= S_DELETE ?>" type="submit" class="formButtom">
            <script type="text/javascript">setDelPass();</script> <?php // JS function needed ?>
            <div class="styleChanger">
                Style
                <select id="styleSelector" onchange="set_stylesheet(value)">
                    <?php if (isset($stylesheets) && is_array($stylesheets)): foreach ($stylesheets as $style): ?>
                        <option value="<?= htmlspecialchars($style['title']) ?>"><?= htmlspecialchars($style['title']) ?></option>
                    <?php endforeach; endif; ?>
                </select>
            </div>
        </div>
    </form> <?php // delform ?>

    <?php if (!$thread_id && $indexpage): ?>
        <div class="pageNumber">
            <?php if (isset($prevpage) && $prevpage): ?><button onclick="location.href='<?= htmlspecialchars($prevpage) ?>'"><?= S_PREV ?></button><?php else: ?><?= S_FIRSTPG ?><?php endif; ?>
            <?php if (isset($pages) && is_array($pages)): foreach ($pages as $page_item): ?>
                <?php if (!$page_item['current']): ?>[<a href="<?= htmlspecialchars($page_item['filename']) ?>"><?= $page_item['page'] ?></a>]<?php else: ?>[<?= $page_item['page'] ?>]<?php endif; ?>
            <?php endforeach; endif; ?>
            <?php if (isset($nextpage) && $nextpage): ?><button onclick="location.href='<?= htmlspecialchars($nextpage) ?>'"><?= S_NEXT ?></button><?php else: ?><?= S_LASTPG ?><?php endif; ?>

            <?php if (defined('ENABLE_CATALOG') && ENABLE_CATALOG): ?>
                <div class="catalogLink">
                    <a href="//<?= DOMAIN ?>/<?= BOARD_DIR ?>/catalog<?= defined('REWRITTEN_URLS') && !REWRITTEN_URLS ? '.html' : '' ?>">Catalog</a>
                </div>
            <?php endif; ?>
            <?php if (defined('ENABLE_LIST') && ENABLE_LIST): ?>
                <div class="catalogLink">
                    <a href="//<?= DOMAIN ?>/<?= BOARD_DIR ?>/subback<?= defined('REWRITTEN_URLS') && !REWRITTEN_URLS ? '.html' : '' ?>">Thread List</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div id="bottomNavStatic" class="staticNav">
        [<?php if (defined('BOARDS') && is_array(BOARDS)): $is_first_board = true; foreach (BOARDS as $board_item): ?>
            <?php if (!$is_first_board): ?> / <?php endif; ?><a href="//<?= DOMAIN ?>/<?= htmlspecialchars($board_item['dir']) ?>/"><?= htmlspecialchars($board_item['dir']) ?></a>
        <?php $is_first_board = false; endforeach; endif; ?>]
        <div style="float:right">
            [<a href="javascript:void(0)" onclick="toggleNavMenu(this,0);">Settings</a>]
            [<a href="//<?= DOMAIN ?>" title="">Home</a>]
        </div>
    </div>

</div> <?php // #content ?>

<?php include WAKABA_BASE_DIR . '/templates/parts/footer.php'; ?>
</body>
</html>
