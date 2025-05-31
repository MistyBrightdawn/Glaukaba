<?php
// templates/admin/edit_post_form.php
// Based on EDIT_POST_TEMPLATE from admin_style.pl

// Expected variables:
// $page_title (string)
// $admin_session (array from $_SESSION)
// $self_url (string, path to index.php)
// $post (array, the post data to be edited)
// $error_message (string, optional)
// Constants: CHARSET, SITE_NAME, etc.

$current_admin_token = $admin_session['admin_token_for_url'] ?? ''; // Or however token for URL is managed

// Default values from post if available
$post_num = $post['num'] ?? 0;
$post_name = $post['name'] ?? '';
$post_trip = $post['trip'] ?? '';
$post_email = $post['email'] ?? ''; // 'link' field in form
$post_subject = $post['subject'] ?? '';
// For comments, we want to edit the original, unformatted version if available
$post_comment_to_edit = $post['originalcomment'] ?? $post['comment'] ?? '';
// decode_string might be needed if it contains entities from DB storage that shouldn't be double-encoded by htmlspecialchars in value attribute
// For simplicity, assuming raw text is stored or decode_string is applied before passing to template.
// $post_comment_to_edit = isset($post['originalcomment']) ? decode_string($post['originalcomment']) : decode_string($post['comment'] ?? '');


$form_action_url = $self_url . '?task=admin_update_post';

// Include manager head
include WAKABA_BASE_DIR . '/templates/admin/parts/manager_head.php';
?>

<div class="postarea">
    <div style="text-align:center; font-weight: bold; margin-bottom:1em;">Editing Post No. <?= htmlspecialchars($post_num) ?></div>

    <?php if (isset($error_message) && $error_message): ?>
        <p class="error" style="color:red; text-align:center;"><?= htmlspecialchars($error_message) ?></p>
    <?php endif; ?>

    <form action="<?= htmlspecialchars($form_action_url) ?>" method="post" enctype="multipart/form-data">
        <input type="hidden" name="admin" value="<?= htmlspecialchars($current_admin_token) ?>" /> <?php // Or rely purely on session ?>
        <input type="hidden" name="num" value="<?= htmlspecialchars($post_num) ?>" />

        <table style="margin: 0 auto;">
            <tbody>
                <tr>
                    <td class="postBlock">Name</td>
                    <td class="postField"><input type="text" class="postInput" name="name" id="field1" value="<?= htmlspecialchars($post_name) ?>" /></td>
                </tr>
                <tr>
                    <td class="postBlock">Trip</td>
                    <td class="postField"><input type="text" class="postInput" name="trip" id="field1andahalf" value="<?= htmlspecialchars($post_trip) ?>" /></td>
                </tr>
                <tr>
                    <td class="postBlock">Link (Email)</td>
                    <td class="postField"><input type="text" class="postInput" name="link" id="field2" value="<?= htmlspecialchars($post_email) ?>" /></td>
                </tr>
                <tr>
                    <td class="postBlock">Subject</td>
                    <td class="postField">
                        <input type="text" name="subject" class="postInput" value="<?= htmlspecialchars($post_subject) ?>" id="field3" />
                    </td>
                </tr>
                <tr>
                    <td class="postBlock">Comment</td>
                    <td class="postField"><textarea name="comment" class="postInput" id="field4" rows="8" cols="60"><?= htmlspecialchars($post_comment_to_edit) ?></textarea></td>
                </tr>
                <?php if (($admin_session['admin_class'] ?? 'unknown') === 'admin'): // Only highest admin can post raw HTML ?>
                <tr>
                    <td class="postBlock">Options</td>
                    <td class="postField">
                        <label>[<input type="checkbox" name="no_format" value="1" /> Raw HTML]</label>
                        <?php // In original, 'no_format' is only for admins. We can enforce this in the handler. ?>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td colspan="2" style="text-align:center; padding-top:10px;">
                         <input type="submit" id="field3s" value="Update Post" />
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</div>

<?php
// Include a generic footer
include WAKABA_BASE_DIR . '/templates/parts/footer.php';
?>
</body>
</html>
