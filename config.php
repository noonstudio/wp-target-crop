<?php
/** 
 * 
 * 
 *  Config file
 * 
 * 
 */


// Import wp-load.php

if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

if (!defined('WP_TARGET_CROP_UPLOADS_DIR')) {
    define('WP_TARGET_CROP_UPLOADS_DIR', WP_CONTENT_DIR . '/uploads');
}

if (!defined('WP_TARGET_CROP_CACHE_DIR')) {
    define('WP_TARGET_CROP_CACHE_DIR', WP_CONTENT_DIR . '/cache/wp-target-crop');
}

// Import the composer autoload
require_once('vendor/autoload.php');