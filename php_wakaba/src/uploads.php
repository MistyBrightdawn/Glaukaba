<?php

// File Processing / Upload Handling

/**
 * Processes an uploaded file.
 * Placeholder for porting process_file from wakaba.pl
 *
 * @param array|null $uploaded_file A single entry from $_FILES.
 * @param string|null $upload_name Original filename from form.
 * @param int $timestamp Current timestamp.
 * @param bool $is_nsfw NSFW flag.
 * @param Database $db Database instance for duplicate checks.
 * @return array Associative array with file details or an error.
 *               Expected keys on success: 'image_path', 'md5', 'width', 'height',
 *                                      'filesize', 'thumb_path', 'tn_width', 'tn_height',
 *                                      'original_filename'.
 *               Expected key on error: 'error' (string message).
 */
function process_file_php(?array $uploaded_file, ?string $upload_name, int $timestamp, bool $is_nsfw, Database $db): array {
    if (!$uploaded_file || $uploaded_file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'File upload error: ' . ($uploaded_file['error'] ?? 'Unknown error')];
    }

    $temp_file_path = $uploaded_file['tmp_name'];
    $original_filename = $upload_name ?: $uploaded_file['name'];
    $file_size = $uploaded_file['size'];

    // Rudimentary MAX_KB check (from config)
    if (defined('MAX_KB') && $file_size > (MAX_KB * 1024)) {
        return ['error' => S_TOOBIG ?? 'File too large.'];
    }
    if ($file_size == 0) {
        return ['error' => S_TOOBIGORNONE ?? 'File is empty or too large (0 bytes).'];
    }

    // --- Analyze image (get extension, width, height) ---
    // $analysis = analyze_image_php($temp_file_path, $original_filename);
    // $ext = $analysis['ext']; $width = $analysis['width']; $height = $analysis['height'];
    // For placeholder:
    $image_info = getimagesize($temp_file_path);
    if (!$image_info) {
        // Maybe it's not an image, or not a recognized type by getimagesize
        // Try to get extension from filename
        $path_info = pathinfo($original_filename);
        $ext = strtolower($path_info['extension'] ?? '');
        if (! (defined('ALLOW_UNKNOWN') && ALLOW_UNKNOWN) && !array_key_exists($ext, defined('FILETYPES') ? FILETYPES : [])){
             return ['error' => S_BADFORMAT ?? 'Unsupported file type (not recognized by getimagesize and not in FILETYPES).'];
        }
        // For unknown but allowed files, width/height are 0
        $width = 0; $height = 0;
    } else {
        $ext = strtolower(image_type_to_extension($image_info[2], false)); // e.g. 'jpeg', 'png', 'gif'
        // In wakaba.pl, it uses 'jpg', not 'jpeg'.
        if ($ext === 'jpeg') $ext = 'jpg';
        $width = $image_info[0];
        $height = $image_info[1];
    }

    $known_type = ($width > 0); // Simplification: getimagesize found width/height

    // --- Security and Policy Checks ---
    // if (! (defined('ALLOW_UNKNOWN') && ALLOW_UNKNOWN) && !$known_type) {
    //     return ['error' => S_BADFORMAT ?? 'File type not recognized or not allowed.'];
    // }
    if (defined('FORBIDDEN_EXTENSIONS') && in_array($ext, FORBIDDEN_EXTENSIONS)) {
        return ['error' => S_BADFORMAT ?? 'File type is forbidden.'];
    }
    // check_resolution_php($width, $height); -> make_generic_error() inside if fails

    // --- Generate Filename & Paths ---
    $filebase = $timestamp . sprintf("%03d", mt_rand(0, 999));
    $image_dir_path = WAKABA_BASE_DIR . '/' . (defined('IMG_DIR') ? IMG_DIR : 'uploads/src/'); // Relative to project root
    $thumb_dir_path = WAKABA_BASE_DIR . '/' . (defined('THUMB_DIR') ? THUMB_DIR : 'uploads/thumb/');

    if (!is_dir($image_dir_path) && !mkdir($image_dir_path, 0755, true)) return ['error' => 'Image directory does not exist and could not be created.'];
    if (!is_dir($thumb_dir_path) && !mkdir($thumb_dir_path, 0755, true)) return ['error' => 'Thumbnail directory does not exist and could not be created.'];

    $image_filename_fs = $filebase . '.' . $ext;
    $image_path_fs = $image_dir_path . $image_filename_fs;
    // Web path would be relative to public/
    $image_path_web = str_replace(WAKABA_BASE_DIR . '/public/', '', $image_path_fs);


    // --- MD5 Check & Duplicate File Check ---
    $md5_hash = md5_file($temp_file_path);
    if ($md5_hash) {
        $existing_post = $db->fetch("SELECT num, parent FROM " . SQL_TABLE_POSTS . " WHERE md5 = ?", [$md5_hash]);
        if ($existing_post) {
            $dupe_link = get_reply_link($existing_post['num'], $existing_post['parent']);
            return ['error' => sprintf(S_DUPE ?? 'Duplicate file found: %s', $dupe_link)];
        }
    }

    // --- Move Uploaded File ---
    if (!move_uploaded_file($temp_file_path, $image_path_fs)) {
        return ['error' => 'Failed to move uploaded file.'];
    }
    chmod($image_path_fs, 0644);


    // --- Thumbnail Generation (Placeholder) ---
    $thumb_path_fs = null; $thumb_path_web = null; $tn_width = 0; $tn_height = 0;
    if ($known_type && ($width > (MAX_W ?? 250) || $height > (MAX_H ?? 250) || (defined('THUMBNAIL_SMALL') && THUMBNAIL_SMALL))) {
        if (defined('STUPID_THUMBNAILING') && STUPID_THUMBNAILING) {
            $thumb_path_fs = $image_path_fs;
            $thumb_path_web = $image_path_web;
            $tn_width = $width; $tn_height = $height; // Or scaled in HTML
        } else {
            // list($tn_width, $tn_height) = get_thumbnail_dimensions_php($width, $height);
            // $thumb_filename_fs = $filebase . 's.jpg'; // Wakaba defaults to jpg for thumbs
            // $thumb_path_fs = $thumb_dir_path . $thumb_filename_fs;
            // $thumb_path_web = str_replace(WAKABA_BASE_DIR . '/public/', '', $thumb_path_fs);
            // if (!make_thumbnail_php($image_path_fs, $thumb_path_fs, $tn_width, $tn_height, defined('THUMBNAIL_QUALITY') ? THUMBNAIL_QUALITY : 75)) {
            //    $thumb_path_fs = null; $thumb_path_web = null; // Thumbnail creation failed
            // }
            // Placeholder:
            $tn_width = ($width > MAX_W) ? MAX_W : $width;
            $tn_height = ($height > MAX_H) ? MAX_H : $height;
            $thumb_path_web = 'uploads/thumb/' . $filebase . 's.jpg'; // Dummy path
             echo "<p>Thumbnail generation placeholder for {$image_filename_fs}.</p>";
        }
    } else if ($known_type) { // Small image, use original as thumb
        $thumb_path_fs = $image_path_fs;
        $thumb_path_web = $image_path_web;
        $tn_width = $width; $tn_height = $height;
    }


    return [
        'image_path' => $image_path_web, // Web path for DB
        'md5' => $md5_hash,
        'width' => $width,
        'height' => $height,
        'filesize' => $file_size,
        'thumb_path' => $thumb_path_web, // Web path for DB
        'tn_width' => $tn_width,
        'tn_height' => $tn_height,
        'original_filename' => $original_filename
    ];
}


/**
 * Analyzes an image file to get its type, width, and height.
 * Placeholder for porting analyze_image from wakautils.pl
 */
function analyze_image_php(string $file_path, string $original_filename): array {
    // safety_check_php($file_path); // Port safety_check

    $image_info = getimagesize($file_path);
    if (!$image_info) {
        $ext = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
        return ['ext' => $ext, 'width' => 0, 'height' => 0];
    }

    $ext = image_type_to_extension($image_info[2], false);
    if ($ext === 'jpeg') $ext = 'jpg'; // Align with Wakaba practice

    return [
        'ext' => $ext,
        'width' => $image_info[0],
        'height' => $image_info[1]
    ];
}

/**
 * Creates a thumbnail for an image.
 * Placeholder for porting make_thumbnail from wakautils.pl
 */
function make_thumbnail_php(string $source_path, string $thumb_path, int $thumb_width, int $thumb_height, int $quality): bool {
    // This would use GD or Imagick
    // Example with GD:
    /*
    try {
        $source_info = getimagesize($source_path);
        $source_mime = $source_info['mime'];
        $source_image = null;
        if ($source_mime == 'image/jpeg') $source_image = imagecreatefromjpeg($source_path);
        elseif ($source_mime == 'image/png') $source_image = imagecreatefrompng($source_path);
        elseif ($source_mime == 'image/gif') $source_image = imagecreatefromgif($source_path);
        else return false;

        if (!$source_image) return false;

        $thumb_image = imagecreatetruecolor($thumb_width, $thumb_height);
        imagecopyresampled($thumb_image, $source_image, 0, 0, 0, 0, $thumb_width, $thumb_height, imagesx($source_image), imagesy($source_image));

        imagejpeg($thumb_image, $thumb_path, $quality); // Wakaba uses JPG for thumbs

        imagedestroy($source_image);
        imagedestroy($thumb_image);
        return true;
    } catch (Exception $e) {
        error_log("Thumbnail creation error: " . $e->getMessage());
        return false;
    }
    */
    return true; // Placeholder
}

/**
 * Calculates thumbnail dimensions.
 * Placeholder for porting get_thumbnail_dimensions from wakautils.pl
 */
function get_thumbnail_dimensions_php(int $width, int $height): array {
    $max_w = defined('MAX_W') ? MAX_W : 250;
    $max_h = defined('MAX_H') ? MAX_H : 250;
    $tn_width = $width;
    $tn_height = $height;

    if ($tn_width > $max_w) {
        $tn_height = (int)(($max_w / $tn_width) * $tn_height);
        $tn_width = $max_w;
    }
    if ($tn_height > $max_h) {
        $tn_width = (int)(($max_h / $tn_height) * $tn_width);
        $tn_height = $max_h;
    }
    return [$tn_width, $tn_height];
}

/**
 * Checks image resolution against configured maximums.
 * Calls make_generic_error if limits are exceeded.
 */
function check_resolution_php(int $width, int $height): bool {
    if (defined('MAX_IMAGE_WIDTH') && $width > MAX_IMAGE_WIDTH) {
        make_generic_error(S_TOOBIG . " (Image width too large)"); return false;
    }
    if (defined('MAX_IMAGE_HEIGHT') && $height > MAX_IMAGE_HEIGHT) {
        make_generic_error(S_TOOBIG . " (Image height too large)"); return false;
    }
    if (defined('MAX_IMAGE_PIXELS') && ($width * $height) > MAX_IMAGE_PIXELS) {
        make_generic_error(S_TOOBIG . " (Image dimensions (pixels) too large)"); return false;
    }
    return true;
}

?>
