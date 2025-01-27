<?php
/** 
 * 
 * 
 *  Functions 
 * 
 * 
 */
if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'wp_target_crop_rewrite_rules');
if (!function_exists('wp_target_crop_rewrite_rules')):

    function wp_target_crop_rewrite_rules()
    {

        add_rewrite_rule(
            '^image/([0-9]{4})/([0-9]{2})/([^/]+)$', // Regex pattern for matching the URL without considering .png
            'index.php?image_year=$matches[1]&image_month=$matches[2]&image_name=$matches[3]', // Rewrite to query variables
            'top'
        );

    }


endif;

add_filter('query_vars', 'wp_target_crop_query_vars');
if (!function_exists('wp_target_crop_query_vars')):

    function wp_target_crop_query_vars($query_vars)
    {

        $query_vars[] = 'image_year';  // Add 'image_year' to query vars
        $query_vars[] = 'image_month'; // Add 'image_month' to query vars
        $query_vars[] = 'image_name';  // Add 'image_name' to query vars
        return $query_vars;

    }

endif;


//add_action('template_redirect', 'wp_target_template_redirect');

if (!function_exists('wp_target_template_redirect')):

    function wp_target_template_redirect()
    {

        if (get_query_var('image_year') && get_query_var('image_month') && get_query_var('image_name')) {
            // Capture the query vars
            $year = get_query_var('image_year');
            $month = get_query_var('image_month');
            $name = get_query_var('image_name');

            $url = "/wp-content/uploads/$year/$month/$name";

            // Handle the request (e.g., load a specific template or return the image)
            // For now, we'll just print the values
            wp_target_crop_generate_image($url);
            exit;
        }

    }

endif;


function wp_target_crop_delete_cache($attachment_id)
{

    // cache to be on the wordpress server – to be moved
    $server = League\Glide\ServerFactory::create([
        'source' => WP_CONTENT_DIR . '/uploads',
        'cache' => WP_CONTENT_DIR . '/cache/wp-target-crop',
    ]);

    $file_path = get_post_meta($attachment_id, '_wp_attached_file', true);

    if ($file_path) {

        $server->deleteCache($file_path);

    }

}
function wp_target_crop_build_cache($attachment_id, $focal_point)
{

    // cache to be on the wordpress server – to be moved
    $server = League\Glide\ServerFactory::create([
        'source' => WP_CONTENT_DIR . '/uploads',
        'cache' => WP_CONTENT_DIR . '/cache/wp-target-crop',
    ]);

    $file_path = get_post_meta($attachment_id, '_wp_attached_file', true);
    $sizes = wp_get_additional_image_sizes();


    foreach ($sizes as $size) {

        error_log(print_r($size, true));

        // Get the width and height
        $width = $size['width'];
        $height = $size['height'];
        $crop = $size['crop'];

        // Build the cache
        $defaults = array(
            'q' => 80,
            'fm' => 'png'
        );

        $fit = 'fill-max';

        if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
            $defaults['fm'] = 'webp';
        }

        if ($crop) {

            if ($focal_point) {
                $focus_x = $focal_point['x'];
                $focus_y = $focal_point['y'];
                $x = round($focus_x * 100);
                $y = round($focus_y * 100);
                $fit = 'crop-' . $x . '-' . $y;
            } else {
                $fit = 'crop';
            }

        }

        $query_args = array_merge($defaults, array('w' => $width, 'h' => $height, 'fit' => $fit));






        // Make the image
        $url = $server->makeImage($file_path, $query_args);

        error_log(PHP_VERSION . print_r($query_args, true));


    }


}




add_filter('attachment_fields_to_edit', 'wp_target_crop_attachment_fields_to_edit', 10, 2);
if (!function_exists('wp_target_crop_attachment_fields_to_edit')):

    function wp_target_crop_attachment_fields_to_edit($fields, $post)
    {

        // echo 'WP Target Crop Attachment Fields to Edit';
        $html = '<div id="wp_target_crop" class="wp_target_crop"><button class="button wp_target_crop_focal_point" imageId="' . $post->ID . '">Focal Point</button></div>';


        $fields = array(
            'wp_target_crop' => array(
                'input' => 'html',
                'label' => __('Focal Point Picker'),
                'html' => $html
            )
        ) + $fields;


        return $fields;

    }


endif;


// Enqueue the scripts
add_action('admin_enqueue_scripts', 'wp_target_crop_enqueue_scripts');

if (!function_exists('wp_target_crop_enqueue_scripts')):
    function wp_target_crop_enqueue_scripts()
    {

        // Get the dependencies
        $asset_file = include(plugin_dir_path(__DIR__) . 'dist/js/media.asset.php');
        $dependencies = $asset_file['dependencies'];
        $version = $asset_file['version'];

        // Media
        wp_enqueue_script(
            'wp_target_crop_media', // Handle.
            plugins_url('/dist/js/media.js', dirname(__FILE__)), // Block.build.js: We register the block here. Built with Webpack.
            $dependencies, // Dependencies, defined above.
            $version // Version: File modification time.
        );

        // Styles.
        wp_enqueue_style(
            'wp_target_crop_media', // Handle.
            plugins_url('/dist/css/media.css', dirname(__FILE__)), // Block editor CSS.
            array('wp-jquery-ui-dialog'), // Dependency to include the CSS after it.
            $version // Version: File modification time.
        );


    }

endif;

add_filter('wp_get_attachment_image_attributes', 'wp_target_crop_image_attributes', 10, 3);
if (!function_exists('wp_target_crop_image_attributes')):

    function wp_target_crop_image_attributes($attributes, $attachment, $size)
    {


        // Check if the image has a focal point stored in metadata
        $focal_point = get_post_meta($attachment->ID, 'focal_point', true);

        if ($focal_point) {


            // Define the desired dimensions for the image size
            $dimensions = get_image_size_dimensions($size); // Replace this with your size mapping

            if ($dimensions) {
                [$width, $height] = $dimensions;
                $standard_url = $attachment->guid;


                // Generate a new image URL using Glide
                $crop_url = wp_target_crop_image_url($standard_url, $width, $height);

                // Replace the src attribute with the new Glide URL
                $attributes['src'] = $crop_url;


                // Also sort the srcset attributes
                if (isset($attributes['srcset'])) {
                    $srcset = explode(', ', $attributes['srcset']);
                    $new_srcset = array_map(function ($srcset_item) use ($width, $height, $standard_url) {
                        $srcset_parts = explode(' ', $srcset_item);
                        $src = $srcset_parts[0];
                        $size = $srcset_parts[1];

                        $crop_url = wp_target_crop_image_url($standard_url, $size, $height);

                        return $crop_url . ' ' . $size;
                    }, $srcset);

                    $attributes['srcset'] = implode(', ', $new_srcset);

                }
            }
        }

        return $attributes;

    }


endif;


add_filter('mod_rewrite_rules', 'wp_target_crop_image_htaccess_contents');

if (!function_exists('wp_target_crop_image_htaccess_contents')):
    function wp_target_crop_image_htaccess_contents($rules)
    {

        $newRule = "<IfModule mod_rewrite.c>" . PHP_EOL;
        $newRule .= "RewriteEngine On" . PHP_EOL;
        $newRule .= "RewriteCond %{QUERY_STRING} (^|&)w=[0-9]+(&|$)" . PHP_EOL;
        $newRule .= "RewriteCond %{QUERY_STRING} (^|&)h=[0-9]+(&|$)" . PHP_EOL;
        $newRule .= "RewriteRule images/([0-9]+)/([0-9]+)/([A-Za-z0-9_@.\/&+-]+)+\.([A-Za-z0-9_@.\/&+-]+)?$ wp-content/plugins/wp-target-crop/media.php?wp_upload=$3&type=$4" . PHP_EOL;
        $newRule .= "</IfModule>" . PHP_EOL;

        return $newRule . PHP_EOL . $rules . PHP_EOL;

    }

endif;