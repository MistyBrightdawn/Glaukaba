<?php
// templates/admin/admin_panel.php
// Main admin panel view, combines ADMIN_PAGE_TEMPLATE structure.

// Expected variables:
// $page_title (string)
// $admin_session (array from $_SESSION)
// $self_url (string, path to index.php)
// $admin_link_param (string, e.g. &admin=TOKEN - though session is preferred)
// $threads_data (array of threads, similar to main_page.php)
// $postform (bool, show admin post form?)
// $image_inp (bool, allow image input in admin post form?)
// $textonly_inp (bool, allow no-file in admin post form?)
// $pages (array for pagination)
// $prevpage, $nextpage (URLs for pagination)
// $stylesheets (array)
// Constants: S_RETURN, S_MANAPANEL, etc.

// Ensure helper functions are available (e.g., via autoloader or direct include in controller)
// expand_filename(), get_reply_link(), intval(), htmlspecialchars(), urlencode(), defined(), etc.

$current_admin_token = $admin_session['admin_token_for_url'] ?? ''; // Or however token is managed if not purely session

// Include manager head
include WAKABA_BASE_DIR . '/templates/admin/parts/manager_head.php';

?>

<div id="content">
    <?php if (isset($thread_id) && $thread_id): // This part is for single thread admin view, not main mpanel for now ?>
        <div class="desktop threadlinks">
            [<a href="#bottom">Bottom</a>]
            <div class="theader"><?= S_POSTING ?? 'Posting Mode' ?></div>
        </div>
    <?php endif; ?>

    <?php if (isset($postform) && $postform): ?>
        <div style="text-align:center">
            <a id="postFormToggle" class="button" onclick="togglePostForm()" href="javascript:void(0)">
                <?= (isset($thread_id) && $thread_id) ? 'Reply to Thread' : 'New Admin Post' ?>
            </a>
        </div>
        <form action="<?= htmlspecialchars($self_url) ?>" method="post" id="post_form" name="post_form" enctype="multipart/form-data">
            <input type="hidden" name="task" value="post">
            <input type="hidden" name="admin" value="<?= htmlspecialchars($current_admin_token) ?>" />
            <input type="hidden" name="no_captcha" value="1" /> <?php // Admins typically bypass captcha ?>

            <?php if (isset($thread_id) && $thread_id): ?><input type="hidden" name="parent" value="<?= (int)$thread_id ?>"><?php endif; ?>

            <div id="postForm" style="display:block;"> <?php // Admin post form usually visible by default ?>
                <div class="postrow">
                    <div class="postBlock">Name</div>
                    <div class="postField"><input type="text" class="postInput" name="field1" id="field1"></div>
                </div>
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
                <?php if (isset($image_inp) && $image_inp): ?>
                <div class="postrow" id="uploadField">
                    <div class="postBlock">File</div>
                    <div class="postField">
                        <input type="file" name="file" id="file"><br>
                        <?php if (isset($textonly_inp) && $textonly_inp): ?><label>[<input type="checkbox" name="nofile" value="on">No File]</label><?php endif; ?>
                        <?php if (defined('SPOILERIMAGE_ENABLED') && SPOILERIMAGE_ENABLED): ?><label>[<input type="checkbox" name="spoiler" value="1">Spoiler]</label><?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                <div class="postrow">
                    <div class="postBlock">Password</div>
                    <div class="postField">
                        <input type="password" class="postInput pass-field" id="password" name="password"/>
                        <span class="passDesc">(for post and file deletion - can be blank for admin)</span>
                    </div>
                </div>
                <?php if (($admin_session['admin_class'] ?? 'unknown') !== 'janitor'): ?>
                <div class="postrow">
                    <div class="postBlock">Options</div>
                    <div class="postField">
                        <?php if (($admin_session['admin_class'] ?? 'unknown') === 'admin'): ?>
                            <label>[<input type="checkbox" name="no_format" value="1" />HTML]</label>
                        <?php endif; ?>
                        <label>[<input type="checkbox" name="capcode" value="1" />Capcode]</label>
                    </div>
                </div>
                <?php if (!isset($thread_id) || !$thread_id): // OP-only flags ?>
                    <div class="postrow">
                    <div class="postBlock">Flags</div>
                    <div class="postField">
                    <label>[<input type="checkbox" name="sticky" value="1" />Sticky]</label>
                    <label>[<input type="checkbox" name="locked" value="1" />Lock]</label>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </form>
        <script type="text/javascript">if(typeof setPostInputs === 'function') setPostInputs();</script>
    <?php endif; ?>
    <hr>

    <form id="delform" action="<?= htmlspecialchars($self_url) ?>" method="post">
        <input type="hidden" name="task" value="delete">
        <input type="hidden" name="admin" value="<?= htmlspecialchars($current_admin_token) ?>" />

        <?php if (isset($threads_data) && is_array($threads_data)): ?>
            <?php foreach ($threads_data as $thread_item): ?>
                <?php // $thread_item is an associative array with 'posts' (array), 'omit', 'omitimages', etc. ?>
                <?php if (empty($thread_item['posts'])) continue; ?>

                <div class="thread">
                    <?php foreach ($thread_item['posts'] as $idx => $post): ?>
                        <?php $is_op_in_loop = ($idx == 0); ?>
                        <?php if ($is_op_in_loop): ?>
                            <div class="parentContainer">
                            <div class="parentPost post <?= !empty($post['reported']) ? 'reportedParent' : '' ?>" id="parent<?= $post['num'] ?>">
                                <?php // Mobile Parent Post Info - Simplified for admin panel ?>
                                <?php if (!empty($post['image'])): ?>
                                    <div class="fileinfo"><span class="filesize"><?= S_PICNAME ?? 'File:' ?>
                                    <a target="_blank" href="<?= expand_filename($post['image']) ?>" title="<?= htmlspecialchars($post['filename'] ?? '') ?>" class="filename">
                                        <?= htmlspecialchars(!empty($post['filename']) ? truncate_line($post['filename']) : get_filename($post['image'])) ?></a>
                                    - (<?= (int)(($post['size'] ?? 0)/1024) ?> KB, <?= $post['width'] ?? 0 ?>x<?= $post['height'] ?? 0 ?>)</span>
                                    </div>
                                    <?php if (!empty($post['thumbnail'])): ?>
                                        <a target="_blank" class="thumbLink" href="<?= expand_filename($post['image']) ?>">
                                        <img src="<?= expand_filename($post['thumbnail']) ?>" style="width:<?= $post['tn_width'] ?>px; height:<?= $post['tn_height'] ?>px;" class="thumb opThumb" alt="thumb"></a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <a id="p<?= $post['num'] ?>"></a>
                                <div class="parentPostInfo">
                                    <input type="checkbox" name="delete[]" value="<?= $post['num'] ?>">
                                    <span class="filetitle"><?= htmlspecialchars($post['subject'] ?? '') ?></span>
                                    <span class="postername"><?= htmlspecialchars($post['name'] ?? '') ?></span>
                                    <?php if (!empty($post['trip'])): ?><span class="postertrip"><?= $post['trip'] ?></span><?php endif; ?>
                                    <span class="date"><?= htmlspecialchars($post['date'] ?? '') ?></span>
                                    <span class="reflink">No.<?= $post['num'] ?></span>&nbsp;
                                    <?php include WAKABA_BASE_DIR . '/templates/admin/parts/admin_post_buttons.php'; ?>
                                    <?php if (!(isset($thread_id) && $thread_id)): // Not single thread view ?>
                                      [<a href="<?= $self_url ?>?task=viewthread&amp;num=<?= $post['num'] ?><?= $admin_link_param ?>">Reply</a>]
                                    <?php endif; ?>
                                </div>
                                <blockquote><?= $post['comment_display'] ?? $post['comment'] ?>
                                    <?php if(!empty($post['abbrev'])): ?><div class="abbrev">(Comment too long)</div><?php endif; ?>
                                </blockquote>
                            </div>
                            <?php if (isset($thread_item['omit']) && $thread_item['omit']): ?>
                                <span class="omittedposts desktop">
                                    <?= sprintf(S_ABBR ?? '%d posts omitted.', $thread_item['omit']) ?>
                                </span>
                            <?php endif; ?>
                            </div>
                        <?php else: // This is a reply post ?>
                            <div class="replyContainer" id="replyContainer<?= $post['num'] ?>">
                                <div class="doubledash">&gt;&gt;</div>
                                <div class="reply post <?= !empty($post['reported']) ? 'reportedReply' : '' ?>" id="reply<?= $post['num'] ?>">
                                    <a id="p<?= $post['num'] ?>"></a>
                                    <div class="replyPostInfo">
                                        <input type="checkbox" name="delete[]" value="<?= $post['num'] ?>">
                                        <span class="replytitle"><?= htmlspecialchars($post['subject'] ?? '') ?></span>
                                        <span class="postername"><?= htmlspecialchars($post['name'] ?? '') ?></span>
                                        <?php if (!empty($post['trip'])): ?><span class="postertrip"><?= $post['trip'] ?></span><?php endif; ?>
                                        <span class="date"><?= htmlspecialchars($post['date'] ?? '') ?></span>
                                        <span class="reflink">No.<?= $post['num'] ?></span>
                                        <?php include WAKABA_BASE_DIR . '/templates/admin/parts/admin_post_buttons.php'; ?>
                                    </div>
                                    <?php if (!empty($post['image'])): ?>
                                        <div class="fileinfo"><span class="filesize"><?= S_PICNAME ?? 'File:' ?>
                                        <a target="_blank" href="<?= expand_filename($post['image']) ?>" title="<?= htmlspecialchars($post['filename'] ?? '') ?>" class="filename">
                                            <?= htmlspecialchars(!empty($post['filename']) ? truncate_line($post['filename']) : get_filename($post['image'])) ?></a>
                                        - (<?= (int)(($post['size'] ?? 0)/1024) ?> KB, <?= $post['width'] ?? 0 ?>x<?= $post['height'] ?? 0 ?>)</span>
                                        </div>
                                        <?php if (!empty($post['thumbnail'])): ?>
                                            <a class="thumbLink" target="_blank" href="<?= expand_filename($post['image']) ?>">
                                            <img src="<?= expand_filename($post['thumbnail']) ?>" class="thumb replyThumb" alt="thumb" style="width: <?= ($post['tn_width'] ?? 0)*0.504 ?>px; height: <?= ($post['tn_height'] ?? 0)*0.504 ?>px;"></a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <blockquote><?= $post['comment_display'] ?? $post['comment'] ?>
                                        <?php if(!empty($post['abbrev'])): ?><div class="abbrev">(Comment too long)</div><?php endif; ?>
                                    </blockquote>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; // posts loop ?>
                </div> <?php // .thread ?>
                <hr>
            <?php endforeach; // $threads_data loop ?>
        <?php endif; ?>

        <div id="deleteFormBottom" class="deleteForm">
            Delete Post
            <label>[<input type="checkbox" name="fileonly" value="on"> <?= S_DELPICONLY ?? 'File Only' ?>]</label>
            <?= S_DELKEY ?? 'Password:' ?><input type="password" name="password" id="delPassAdmin" class="postInput pass-field"/>
            <input value="<?= S_DELETE ?? 'Delete' ?>" type="submit" class="formButtom">
            <script type="text/javascript">if(typeof setDelPass === 'function') setDelPass('delPassAdmin');</script>
        </div>
    </form> <?php // delform ?>

    <?php if (!(isset($thread_id) && $thread_id) && isset($pages) && count($pages) > 1): // Pagination for main admin panel view ?>
        <div class="pageNumber">
            <?php if (isset($prevpage) && $prevpage): ?><button onclick="location.href='<?= htmlspecialchars($prevpage) ?>'"><?= S_PREV ?? 'Previous' ?></button><?php else: ?><?= S_FIRSTPG ?? 'First' ?><?php endif; ?>
            <?php foreach ($pages as $page_item): ?>
                <?php if (!$page_item['current']): ?>[<a href="<?= htmlspecialchars($page_item['filename']) ?>"><?= $page_item['page'] ?></a>]<?php else: ?>[<?= $page_item['page'] ?>]<?php endif; ?>
            <?php endforeach; ?>
            <?php if (isset($nextpage) && $nextpage): ?><button onclick="location.href='<?= htmlspecialchars($nextpage) ?>'"><?= S_NEXT ?? 'Next' ?></button><?php else: ?><?= S_LASTPG ?? 'Last' ?><?php endif; ?>
        </div>
    <?php endif; ?>
</div> <?php // #content ?>

<?php
// Include a generic footer, or a specific admin footer if created
include WAKABA_BASE_DIR . '/templates/parts/footer.php';
?>
</body>
</html>
