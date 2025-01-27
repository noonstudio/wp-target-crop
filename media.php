<?php
/**
 * 
 * This file handles the media switch to utilises the glide image generator to create images on the fly.
 * 
 * @since      1.0.0
 * @package    Noon_Focal_Retina_Image_Generator
 * 
 */

// Get wp-load.php
require './config.php';

$query_args = array();
$parse_url = wp_parse_url($_SERVER['REQUEST_URI']);

// Parse the String
parse_str($parse_url['query'], $query_args);

// File path
$query_args['path'] = str_replace('/images/', '', $parse_url['path']);

// Get the image 
wp_target_crop_generate_image($query_args, $output = true);