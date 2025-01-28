<?php
/** 
 * 
 * 
 *  WP Target Crop Functions
 * 
 * 
 */
if (!defined('ABSPATH')) {
    exit;
}

function wp_target_crop_generate_image($args, $output = true)
{

    $defaults = array(
        'crop' => 'contain',
        'shouldCrop' => true,
        'isDefaultSize' => false,
        'q' => 80,
    );

    // Merge the defaults with the args
    $args = wp_parse_args($args, $defaults);

    // If no width or height is set then return false
    if (!isset($args['w']) && !isset($args['h'])) {

        $args['w'] = 1920;
        $args['h'] = 1080;
        $args['isDefaultSize'] = true;

    }


    // Check if the browser supports webp
    if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
        if (strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false) {
            $args['fm'] = 'webp';
        }
    }


    // If there is no path then return false
    if (!isset($args['path'])) {
        return false;
    }

    // Get the path and unset it from the args
    $path = $args['path'];
    unset($args['path']);

    $uploads_url = wp_get_upload_dir();

    // Get the image_id from the URL
    $full_url = $uploads_url['baseurl'] . '/' . $path;
    $image_id = attachment_url_to_postid(esc_url($full_url));



    if (!$image_id) {

        if ($output) {

            header("HTTP/1.0 404 Not Found");
            exit();
        }


        return false;
    }


    $focal_point = false;
    if ($image_id) {

        // Get the focal point
        $focal_point = get_post_meta($image_id, 'focal_point', true);

        if ($focal_point) {

            // Get the x and y
            $focus_x = $focal_point->x;
            $focus_y = $focal_point->y;


            $x = round($focus_x * 100);
            $y = round($focus_y * 100);


            if ($args['shouldCrop']) {

                // Define the desired dimensions for the image size this needs to be the offset percentage
                $args['fit'] = 'crop-' . $x . '-' . $y;

            }

        }


    }

    try {


        // cache to be on the wordpress server – to be moved
        $server = League\Glide\ServerFactory::create([
            'source' => WP_TARGET_CROP_UPLOADS_DIR,
            'cache' => WP_TARGET_CROP_CACHE_DIR,
        ]);


        // If we are outputting then go for it
        if ($output) {

            try {

                $server->outputImage($path, $args);


            } catch (Exception $e) {


                // If we have an error then output the error
                $size = 'full';
                if (!($args['isDefaultSize'])) {

                    $size = array($args['w'], $args['h']);

                }

                $imageUrl = wp_get_attachment_image_url($image_id, $size, false);


                if ($imageUrl) {

                    // Set the correct headers for an image response
                    $image_info = getimagesize($imageUrl);
                    header('Content-Type: ' . $image_info['mime']);
                    header('Content-Length: ' . filesize($imageUrl));


                    // Output the image
                    $response = wp_remote_get($imageUrl);
                    echo wp_remote_retrieve_body($response);

                    exit();


                } else {

                    // If the image doesn't exist, load WordPress 404 page
                    status_header(404); // Set the 404 status
                    get_template_part('404'); // This will load your theme's 404.php template

                    exit(); // Stop the script after 404 is triggered

                }


            }


        } else {


            try {

                $server->makeImage($path, $args);


            } catch (Exception $e) {

                return false;

            }

        }


    } catch (SignatureException $e) {


        $message = $e->getMessage();
        echo esc_html($message);

        exit();


    }


}

if (!function_exists('wp_target_crop_delete_cache')):

    function wp_target_crop_delete_cache($attachment_id)
    {

        // cache to be on the wordpress server – to be moved
        $server = League\Glide\ServerFactory::create([
            'source' => WP_TARGET_CROP_UPLOADS_DIR,
            'cache' => WP_TARGET_CROP_CACHE_DIR,
        ]);

        $file_path = get_post_meta($attachment_id, '_wp_attached_file', true);

        if ($file_path) {

            $server->deleteCache($file_path);

        }

    }

endif;

if (!function_exists('wp_target_crop_build_cache')):

    function wp_target_crop_build_cache($attachment_id, $focal_point)
    {

        $file_path = get_post_meta($attachment_id, '_wp_attached_file', true);
        $sizes = wp_get_additional_image_sizes();


        foreach ($sizes as $size) {

            // Get the width and height
            $width = $size['width'];
            $height = $size['height'];
            $crop = $size['crop'];

            wp_target_crop_generate_image(
                array(
                    'w' => $width,
                    'h' => $height,
                    'path' => $file_path,
                    'shouldCrop' => $crop,
                ),
                $output = false
            );


        }


    }

endif;


/**
 * Generate a image URL with cropping and focal point.
 *
 * @param string $src Original image URL.
 * @param int $width Desired width.
 * @param int $height Desired height.
 * @param int $focus_x Focal point X (0-100).
 * @param int $focus_y Focal point Y (0-100).
 * @return string image URL.
 */
if (!function_exists( 'wp_target_crop_image_url')):
    function wp_target_crop_image_url($src, $width, $height)
    {

        $width = str_replace('w', '', $width);

        $params = [
            'w' => $width,
            'h' => $height,
        ];

        $url = $src . '?' . http_build_query($params);

        // Now string replace 
        $url = str_replace('wp-content/uploads', 'images', $url);

        return $url;

    }
endif;


if (!function_exists('get_image_size_dimensions')):

    /**
     * Get dimensions for a given image size.
     *
     * @param string|array $size Image size name or custom dimensions array.
     * @return array|false Array with width and height, or false if not found.
     */
    function get_image_size_dimensions($size)
    {
        if (is_array($size)) {
            return $size; // Custom size provided as [width, height]
        }

        $sizes = wp_get_additional_image_sizes();

        if (isset($sizes[$size])) {
            return [$sizes[$size]['width'], $sizes[$size]['height']];
        }

        return false;
    }

endif;