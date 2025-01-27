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

use League\Glide\Signatures\SignatureException;


$query_args = array();
$parse_url = parse_url($_SERVER['REQUEST_URI']);

// Parse the String
parse_str($parse_url['query'], $query_args);

// File path
$query_args['path'] = str_replace('/images/', '', $parse_url['path']);

// Get the image 
$image = wp_target_crop_generate_image($query_args);
exit();

try {

  $query_args = array();
  $parse_url = parse_url($_SERVER['REQUEST_URI']);
  $file_path = str_replace('/images/', '', $parse_url['path']);

  // Parse the String
  parse_str(
    $parse_url['query'],
    $query_args
  );
  // cache to be on the wordpress server – to be moved
  $server = League\Glide\ServerFactory::create([
    'source' => '../../../wp-content/uploads',
    'cache' => '../../../wp-content/cache/wp-target-crop',
  ]);



  // Get the focal_point via a rest API call to the wordpress server
  $curl = curl_init();

  curl_setopt_array($curl, array(
    CURLOPT_URL => 'http://localhost:10155/wp-json/wp-target-crop/v1/focal-point?url=' . $file_path,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET'
  ));

  $response = curl_exec($curl);
  $data = json_decode($response, true);

  curl_close($curl);

  if (!empty($data) && isset($data['focal_point'])) {
    $focal_point = $data['focal_point'];


    // Validate that focal_point contains valid 'x' and 'y' values.
    if (is_array($focal_point) && isset($focal_point['x'], $focal_point['y'])) {
      $focus_x = floatval($focal_point['x']);
      $focus_y = floatval($focal_point['y']);

      // Ensure the values are within the valid range [0, 1].
      if ($focus_x >= 0 && $focus_x <= 1 && $focus_y >= 0 && $focus_y <= 1) {
        $x = round($focus_x * 100);
        $y = round($focus_y * 100);

        // Define the desired dimensions for the image size as an offset percentage.
        $fit = 'crop-' . $x . '-' . $y;

        $query_args['fit'] = $fit;
      } else {
        // Optional: Log or handle invalid focal point values.
        error_log('Invalid focal point values: x=' . $focus_x . ', y=' . $focus_y);
      }
    } else {
      // Optional: Log or handle missing or invalid focal point data.
      error_log('Focal point data is not valid or is missing.');
    }
  } else {
    // Optional: Log or handle missing response or focal point key.
    error_log('Response or focal point key is missing.');
  }


  // Set the defaukts
  $defaults = array(
    'q' => 80,
    'fm' => 'png'
  );

  if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
    if (strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false) {
      $defaults['fm'] = 'webp';
    }
  }

  $query_args = array_merge($defaults, $query_args);

  error_log('media.php' . print_r($query_args, true));


  $server->outputImage($file_path, $query_args);

  exit();

} catch (SignatureException $e) {

  // Always bring out the full size image otherwise it will end up with weird consequences. 
  $parse_url = parse_url($_SERVER['REQUEST_URI']);
  $file_path = str_replace('/images/', '', $parse_url['path']);

  $url = $_SERVER['DOCUMENT_ROOT'] . $parse_url['path'];

  if (file_exists($url)) {

    $server = League\Glide\ServerFactory::create([
      'source' => '../../../wp-content/uploads',
      'cache' => '../../../wp-content/cache/wp-target-crop'
    ]);

    // Set the defaukts
    $query_args = array(
      'w' => 1920,
      'h' => 1080,
      'crop' => 'fit',
      'q' => 60,
      'fm' => 'jpg',
    );

    if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
      if (strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false) {
        $query_args['fm'] = 'webp';
      }
    }

    $server->outputImage($file_path, $query_args);

  } else {

    header("HTTP/1.0 404 Not Found");

  }

}