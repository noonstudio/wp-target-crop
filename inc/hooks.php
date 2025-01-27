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