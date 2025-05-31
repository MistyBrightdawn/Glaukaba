<?php

// security.php - Security related functions like ban checks, etc.

/**
 * Checks if a given IP address is banned.
 * Deactivates expired bans.
 *
 * @param Database $db The database instance.
 * @param int $ip_long The user's IP address as a long integer.
 * @return array|null Ban details if an active ban is found, otherwise null.
 */
function check_ip_ban(Database $db, int $ip_long): ?array {
    if (!defined('SQL_TABLE_ADMIN')) {
        // This should be defined in config.php
        error_log("SQL_TABLE_ADMIN constant is not defined.");
        return null;
    }

    // Fetch potentially matching active IP bans
    // The condition `($ip_long & ival2) = (ival1 & ival2)` checks if $ip_long is in the subnet ival1 with netmask ival2
    // However, ival1 is stored as TEXT in the schema, which is problematic for bitwise ops if it's an IP string.
    // Assuming ival1 (IP) and ival2 (mask) are stored as long integers (or numeric strings convertible to int)
    // For simplicity, this query might need adjustment if ival1/ival2 are IP strings.
    // The original Perl code uses `? & ival2 = ival1 & ival2`.
    // If ival1 and ival2 are stored as text IP/mask, this query needs to change or they need to be numbers.
    // Let's assume for now they are stored as numbers (long IPs).

    // Fetch all active IP bans first, then filter in PHP for robust IP range matching.
    // This is less efficient than a direct SQL query if ranges are complex, but safer given current schema.
    $potential_bans = $db->fetchAll("SELECT * FROM " . SQL_TABLE_ADMIN . " WHERE type = 'ipban' AND active = 1");

    $current_time = time();

    foreach ($potential_bans as $ban) {
        // Ensure ival1 and ival2 are treated as integers for bitwise operations
        $ban_ip_long = intval($ban['ival1']); // Banned IP/Network address
        $ban_mask_long = intval($ban['ival2']); // Netmask

        // Check if the user's IP falls within the ban range
        if (($ip_long & $ban_mask_long) === ($ban_ip_long & $ban_mask_long)) {
            // IP matches this ban rule. Now check if it's still valid.
            if ($ban['perm'] == 0 && $ban['duration'] < $current_time) {
                // Ban has expired, deactivate it.
                $db->execute("UPDATE " . SQL_TABLE_ADMIN . " SET active = 0 WHERE num = ?", [$ban['num']]);
                // Log expired ban deactivation (optional)
                // log_action_php('ban_expired', $ban['num']);
                continue; // Check if other bans might still apply
            }

            // Active, non-expired ban found for this IP.
            return $ban;
        }
    }

    return null; // No active, non-expired ban found for this IP.
}

/**
 * Placeholder for checking word bans.
 *
 * @param Database $db
 * @param string $name
 * @param string $subject
 * @param string $comment
 * @return bool True if a banned word is found, false otherwise.
 */
function check_word_bans(Database $db, string $name, string $subject, string $comment): bool {
    // In wakaba.pl: fetches all 'wordban' from SQL_ADMIN_TABLE and regex matches.
    // $sth=$dbh->prepare("SELECT sval1 FROM ".SQL_ADMIN_TABLE." WHERE type='wordban';");
    // while($row=$sth->fetchrow_arrayref()){
    //     my $regexp=quotemeta $$row[0];
    //     make_error(S_STRREF) if($comment=~/$regexp/);
    //     make_error(S_STRREF) if($name=~/$regexp/);
    //     make_error(S_STRREF) if($subject=~/$regexp/);
    // }
    // For PHP, this would involve fetching all sval1 for type='wordban'
    // and then looping through them, using preg_match with preg_quote for each word.
    return false; // Placeholder
}


/**
 * Logs an administrative or system action.
 * Placeholder for log_action from wakaba.pl
 *
 * @param string $action The action performed (e.g., 'deletepost', 'ipban').
 * @param string $object The object of the action (e.g., post ID, IP address).
 * @param string|null $admin_user The admin username performing the action, or null for system.
 * @param Database|null $db Database instance, if logging to DB.
 */
function log_action_php(string $action, string $object, ?string $admin_user = null, ?Database $db = null): void {
    // In wakaba.pl, this logs to SQL_LOG_TABLE.
    // Columns: num, user, action, object, board, time, ip (of admin)
    // For now, this is a placeholder.
    $log_message = sprintf(
        "Time: %s | Action: %s | Object: %s | Admin: %s | IP: %s\n",
        date('Y-m-d H:i:s'),
        $action,
        $object,
        $admin_user ?? 'System',
        get_user_ip()
    );
    // error_log($log_message); // Example: log to PHP error log

    // If $db is provided and SQL_TABLE_LOG is defined:
    // $db->execute("INSERT INTO ".SQL_TABLE_LOG." (user, action, object, board, time, ip) VALUES (?, ?, ?, ?, ?, ?)", [
    //     $admin_user ?? (get_user_ip()), // If no admin, log IP as user
    //     $action,
    //     $object,
    //     defined('BOARD_DIR') ? BOARD_DIR : '',
    //     time(),
    //     ip_to_long_php(get_user_ip())
    // ]);
}

?>
