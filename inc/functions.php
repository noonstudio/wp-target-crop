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

function wp_target_crop_generate_image($url)
{
    try {


        $query_args = array();
        $wp_upload_dir = wp_upload_dir();

        $file_path = str_replace('/wp-content/uploads/', '', $url);


        // Get the query args
        $parse_url = parse_url($_SERVER['REQUEST_URI']);

        // Parse the String
        parse_str($parse_url['query'], $query_args);

        // Get the image_id from the URL
        $full_url = get_site_url() . $url;
        $image_id = attachment_url_to_postid(esc_url($full_url));

        // Get the focal point
        $focal_point = false;
        $fit = 'crop';
        if ($image_id) {

            // Get the focal point
            $focal_point = get_post_meta($image_id, 'focal_point', true);

            if ($focal_point) {

                // Get the x and y
                $focus_x = $focal_point['x'];
                $focus_y = $focal_point['y'];


                $x = round($focus_x * 100);
                $y = round($focus_y * 100);


                // Define the desired dimensions for the image size this needs to be the offset percentage
                $fit = 'crop-' . $x . '-' . $y;

            }


        }


        // cache to be on the wordpress server – to be moved
        $server = League\Glide\ServerFactory::create([
            'source' => 'wp-content/uploads',
            'cache' => 'wp-content/cache/wp-target-crop',
        ]);


        // Set the defaukts
        $defaults = array(
            'q' => 80,
            'fm' => 'png',
            'fit' => $fit,

        );

        if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
            if (strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false) {
                $defaults['fm'] = 'webp';
            }
        }

        $query_args = array_merge($defaults, $query_args);

        $server->outputImage($file_path, $query_args);

        exit();

    } catch (SignatureException $e) {


        print_r($e->getMessage());

    }





}


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
if (!function_exists(function: 'wp_target_crop_image_url')):
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