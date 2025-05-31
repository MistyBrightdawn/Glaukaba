<?php
// templates/admin/ban_panel.php
// Based on BAN_PANEL_TEMPLATE from admin_style.pl

// Expected variables:
// $page_title (string)
// $admin_session (array from $_SESSION)
// $self_url (string, path to index.php)
// $admin_link_param (string, e.g. &admin=TOKEN)
// $bans (array of ban objects/arrays from SQL_TABLE_ADMIN)
// $stylesheets (array)
// Constants: S_MANABANS, S_BANIPLABEL, etc.

$page_title = $page_title ?? 'Ban Management';
$admin_class = $admin_session['admin_class'] ?? 'unknown';

// Include manager head
include WAKABA_BASE_DIR . '/templates/admin/parts/manager_head.php';
?>

<div class="dellist"><?= S_MANABANS ?? 'Ban Management' ?></div>

<div class="postarea">
    <table style="margin: 0 auto;">
        <tbody>
            <tr>
                <td valign="bottom" style="padding-right: 20px;">
                    <form action="<?= htmlspecialchars($self_url) ?>" method="post">
                        <input type="hidden" name="task" value="admin_add_ip_ban" />
                        <input type="hidden" name="type" value="ipban" /> <?php // This might be selected by user later ?>
                        <input type="hidden" name="admin_token" value="<?= htmlspecialchars($admin_session['admin_token_for_url'] ?? '') ?>" />
                        <table>
                            <tbody>
                                <tr><td colspan="2"><strong>Add IP Ban</strong></td></tr>
                                <tr>
                                    <td class="postBlock"><?= S_BANIPLABEL ?? 'IP' ?></td>
                                    <td><input type="text" name="ip" size="24" /></td>
                                </tr>
                                <tr>
                                    <td class="postBlock"><?= S_BANMASKLABEL ?? 'Mask' ?></td>
                                    <td><input type="text" name="mask" size="24" placeholder="e.g., 255.255.255.255 or /32" /></td>
                                </tr>
                                <tr>
                                    <td class="postBlock"><?= S_BANCOMMENTLABEL ?? 'Reason' ?></td>
                                    <td><input type="text" name="comment" size="24" /></td>
                                </tr>
                                <tr>
                                    <td class="postBlock">Duration (days)</td>
                                    <td><input type="text" name="duration_days" size="5" /> (0 or empty for permanent)</td>
                                </tr>
                                <tr>
                                    <td class="postBlock">Permanent</td>
                                    <td><input type="checkbox" name="permanent" value="1" /></td>
                                </tr>
                                <tr>
                                    <td colspan="2"><input type="submit" value="<?= S_BANIP ?? 'Add IP Ban' ?>" /></td>
                                </tr>
                            </tbody>
                        </table>
                    </form>
                </td>
                <?php /* Placeholder for other ban types like word/string bans, whitelist, trust */ ?>
                <?php if ($admin_class === 'admin'): // Example: Word bans only for top admin ?>
                <td valign="bottom" style="padding-left: 20px;">
                     <form action="<?= htmlspecialchars($self_url) ?>" method="post">
                        <input type="hidden" name="task" value="admin_add_string_ban" />
                        <input type="hidden" name="type" value="wordban" />
                        <input type="hidden" name="admin_token" value="<?= htmlspecialchars($admin_session['admin_token_for_url'] ?? '') ?>" />
                        <table>
                            <tbody>
                                <tr><td colspan="2"><strong>Add Word Ban (Placeholder)</strong></td></tr>
                                <tr>
                                    <td class="postBlock"><?= S_BANWORDLABEL ?? 'Word/Phrase' ?></td>
                                    <td><input type="text" name="string" size="24" /></td>
                                </tr>
                                <tr>
                                    <td class="postBlock"><?= S_BANCOMMENTLABEL ?? 'Reason' ?></td>
                                    <td><input type="text" name="comment" size="24" /></td>
                                </tr>
                                 <tr>
                                    <td colspan="2"><input type="submit" value="<?= S_BANWORD ?? 'Add Word Ban' ?>" /></td>
                                </tr>
                            </tbody>
                        </table>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
        </tbody>
    </table>
</div><br />

<table align="center" class="ban-list" style="width: 80%;">
    <thead>
        <tr class="managehead">
            <th>Type</th>
            <th>Value / IP Range</th>
            <th>Reason</th>
            <th>Expires / Status</th>
            <th>By</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (isset($bans) && !empty($bans)): ?>
            <?php $current_type = ''; ?>
            <?php foreach ($bans as $ban_item): ?>
                <?php
                if (($ban_item['type'] ?? '') !== $current_type) {
                    $current_type = $ban_item['type'] ?? '';
                    // echo '<tr class="managehead"><th colspan="7" style="text-align:left; background:#eee;">' . ucfirst(htmlspecialchars($current_type)) . ' Bans</th></tr>';
                }
                $row_type_class = (($index ?? 0) % 2 == 0) ? 'row1_admin' : 'row2_admin'; $index = ($index ?? 0) + 1;
                ?>
                <tr class="<?= $row_type_class ?>">
                    <td><?= htmlspecialchars(ucfirst($ban_item['type'] ?? 'N/A')) ?></td>
                    <td>
                        <?php if ($ban_item['type'] === 'ipban'): ?>
                            <a href="<?= $self_url ?>?task=ippage&amp;ip=<?= htmlspecialchars(long_to_ip_php((int)($ban_item['ival1'] ?? 0))) ?><?= $admin_link_param ?>">
                                <?= htmlspecialchars(long_to_ip_php((int)($ban_item['ival1'] ?? 0))) ?>/<?= htmlspecialchars(long_to_ip_php((int)($ban_item['ival2'] ?? 0))) ?>
                            </a>
                        <?php else: ?>
                            <?= htmlspecialchars($ban_item['sval1'] ?? 'N/A') ?>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($ban_item['comment'] ?? '') ?></td>
                    <td>
                        <?php if ($ban_item['active'] != 1): ?>
                            <span style="color:grey;">Inactive</span>
                        <?php elseif (isset($ban_item['perm']) && $ban_item['perm'] == 1): ?>
                            <span style="color:red;">Permanent</span>
                        <?php elseif (isset($ban_item['duration']) && (int)$ban_item['duration'] > 0): ?>
                            <?= htmlspecialchars(make_date((int)$ban_item['duration'])) ?>
                        <?php else: ?>
                            Active
                        <?php endif; ?>
                        <?= (isset($ban_item['warning']) && $ban_item['warning'] == 1) ? ' (Warning)' : '' ?>
                    </td>
                    <td><?= htmlspecialchars($ban_item['fromuser'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars(make_date((int)($ban_item['timestamp'] ?? 0))) ?></td>
                    <td>
                        <?php if ($ban_item['active'] == 1 && ($admin_class === 'admin' || $admin_class === 'mod')): ?>
                            [<a href="<?= $self_url ?>?task=admin_deactivate_ban&amp;num=<?= $ban_item['num'] ?><?= $admin_link_param ?>" onclick="return confirm('Deactivate this ban?');">Deactivate</a>]
                        <?php elseif($ban_item['active'] != 1 && ($admin_class === 'admin' || $admin_class === 'mod')): ?>
                             [<a href="<?= $self_url ?>?task=admin_activate_ban&amp;num=<?= $ban_item['num'] ?><?= $admin_link_param ?>" onclick="return confirm('Activate this ban?');">Activate</a>]
                        <?php endif; ?>
                        [<a href="<?= $self_url ?>?task=admin_remove_ban&amp;num=<?= $ban_item['num'] ?><?= $admin_link_param ?>" onclick="return confirm('Remove this ban entry?');"><?= S_BANREMOVE ?? 'Remove' ?></a>]
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="7" style="text-align:center;">No bans found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<br />

<?php
// Include a generic footer
include WAKABA_BASE_DIR . '/templates/parts/footer.php';
?>
</body>
</html>
