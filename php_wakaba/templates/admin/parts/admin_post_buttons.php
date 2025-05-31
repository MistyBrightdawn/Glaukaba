<?php
// templates/admin/parts/admin_post_buttons.php
// Translates ADMIN_POST_BUTTONS_TEMPLATE from admin_style.pl

// Expected variables:
// $post (array, the current post data, must include 'num', 'parent', 'ip', 'reported', 'sticky', 'locked', 'permasage')
// $admin_session (array, from $_SESSION, must include 'admin_class')
// $self_url (string, path to index.php)
// $admin_link_param (string, e.g., "&amp;admin=TOKEN_IF_NEEDED_FOR_URLS")

$post_num = $post['num'] ?? 0;
$post_ip = $post['ip'] ?? '0.0.0.0'; // Should be numeric from DB, converted by long_to_ip_php for display
$post_parent = $post['parent'] ?? 0;

$is_op = ($post_parent == 0);
$admin_class = $admin_session['admin_class'] ?? 'unknown';

// Convert numeric IP to dot notation if it's not already
// (assuming $post['ip'] might store the numeric version)
if (is_numeric($post_ip) && function_exists('long_to_ip_php')) {
    $display_ip = long_to_ip_php((int)$post_ip);
} else {
    $display_ip = $post_ip; // Assume it's already formatted or a placeholder
}

?>
<?php if ($admin_class !== 'janitor'): ?>
    [<a href="<?= $self_url ?>?task=ippage&amp;ip=<?= urlencode($display_ip) ?><?= $admin_link_param ?>" title="IP Page"><?= htmlspecialchars($display_ip) ?></a>]
    [<a href="<?= $self_url ?>?task=bantemplate&amp;ip=<?= urlencode($display_ip) ?>&amp;num=<?= $post_num ?><?= $admin_link_param ?>" title="Ban User">B</a>]
<?php endif; ?>
[<a href="<?= $self_url ?>?task=delete&amp;delete[]=<?= $post_num ?><?= $admin_link_param ?>" title="Delete Post" onclick="return confirm('Delete post <?= $post_num ?>?');">D</a>]
[<a href="<?= $self_url ?>?task=delete&amp;delete[]=<?= $post_num ?>&amp;fileonly=1<?= $admin_link_param ?>" title="Delete File" onclick="return confirm('Delete file from post <?= $post_num ?>?');">F</a>]

<?php if ($admin_class === 'admin'): ?>
    [<a href="<?= $self_url ?>?task=admin_edit_post_form&amp;num=<?= $post_num ?><?= $admin_link_param ?>" title="Edit Post">E</a>]
<?php endif; ?>

<?php if ($is_op): ?>
    <?php if ($admin_class !== 'janitor'): ?>
        <?php if (!empty($post['sticky'])): ?>
            [<a href="<?= $self_url ?>?task=toggle_sticky&amp;num=<?= $post_num ?>&amp;jimmies=rustled<?= $admin_link_param ?>" title="Unsticky Thread">-S</a>]
        <?php else: ?>
            [<a href="<?= $self_url ?>?task=toggle_sticky&amp;num=<?= $post_num ?>&amp;jimmies=unrustled<?= $admin_link_param ?>" title="Sticky Thread">S</a>]
        <?php endif; ?>
        <?php if (!empty($post['locked'])): ?>
            [<a href="<?= $self_url ?>?task=toggle_lock&amp;num=<?= $post_num ?>&amp;jimmies=rustled<?= $admin_link_param ?>" title="Unlock Thread">-L</a>]
        <?php else: ?>
            [<a href="<?= $self_url ?>?task=toggle_lock&amp;num=<?= $post_num ?>&amp;jimmies=unrustled<?= $admin_link_param ?>" title="Lock Thread">L</a>]
        <?php endif; ?>
    <?php endif; ?>
    <?php if (!empty($post['permasage'])): ?>
         [<a href="<?= $self_url ?>?task=toggle_permasage&amp;num=<?= $post_num ?>&amp;jimmies=rustled<?= $admin_link_param ?>" title="Un-Permasage Thread">-PS</a>]
    <?php else: ?>
         [<a href="<?= $self_url ?>?task=toggle_permasage&amp;num=<?= $post_num ?>&amp;jimmies=unrustled<?= $admin_link_param ?>" title="Permasage Thread">PS</a>]
    <?php endif; ?>
<?php endif; ?>

<?php if (!empty($post['reported'])): ?>
    [<a href="<?= $self_url ?>?task=viewreport&amp;num=<?= $post_num ?><?= $admin_link_param ?>" title="View detailed report information for this post">View Report</a>]
<?php endif; ?>
