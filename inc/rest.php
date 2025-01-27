<?php
/** 
 * 
 * 
 *  WP Target Crop REST API
 * 
 * 
 */
if (!defined('ABSPATH')) {
    exit;
}


// Register the REST API route
add_action('rest_api_init', 'wp_target_crop_register_rest_route');
if (!function_exists('wp_target_crop_register_rest_route')):

    function wp_target_crop_register_rest_route()
    {

        register_rest_route('wp-target-crop/v1', '/focal-point/(?P<id>\d+)', array(
            'methods' => 'POST',
            'callback' => 'wp_target_crop_save_focal_point',
            'args' => array(
                'id' => array(
                    'validate_callback' => function ($param, $request, $key) {
                        return is_numeric($param);
                    }
                )
            ),
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            }

        ));

        register_rest_route('wp-target-crop/v1', '/focal-point', array(
            'methods' => 'GET',
            'callback' => 'wp_target_crop_get_focal_point',
            'params' => array(
                'url' => array(
                    'validate_callback' => function ($param, $request, $key) {
                        return is_string($param);
                    }
                )
            ),

        ));


    }


endif;

if (!function_exists('wp_target_crop_save_focal_point')):

    function wp_target_crop_save_focal_point($request)
    {

        // Get the image ID
        $image_id = $request->get_param('id');
        $focalPoint = $request->get_param('focalPoint');

        // Save the focal point
        $success = update_post_meta($image_id, 'focal_point', (object) $focalPoint);

        if ($success) {

            // Hook into the delete cache function
            wp_target_crop_delete_cache($image_id);

            wp_target_crop_build_cache($image_id, $focalPoint);

        }

        return $success;

    }

endif;
if (!function_exists('wp_target_crop_get_focal_point')) {

    /**
     * Retrieves the focal point for an image based on a URL parameter.
     *
     * @param WP_REST_Request $request The REST API request object.
     * @return WP_REST_Response|WP_Error The focal point or WP_Error on failure.
     */
    function wp_target_crop_get_focal_point($request)
    {
        // Get the image URL parameter from the request.
        $url = $request->get_param('url');

        if (empty($url)) {
            return new WP_Error(
                'missing_url',
                __('Missing URL parameter. Please provide a valid URL.', 'text-domain'),
                array('status' => 400)
            );
        }

        // Sanitize and validate the URL.
        $full_url = get_site_url() . '/wp-content/uploads/' . $url;

        if (!filter_var($full_url, FILTER_VALIDATE_URL)) {
            return new WP_Error(
                'invalid_url',
                __('The provided URL is invalid.', 'text-domain'),
                array('status' => 400)
            );
        }

        // Retrieve the attachment ID from the URL.
        $image_id = attachment_url_to_postid($full_url);

        if (!$image_id) {
            return new WP_Error(
                'invalid_image_id',
                sprintf(
                    __('No attachment found for the provided URL: %s', 'text-domain'),
                    esc_url($full_url)
                ),
                array('status' => 400)
            );
        }

        // Check if the post is an attachment and has the required metadata.
        $post_type = get_post_type($image_id);
        if ($post_type !== 'attachment') {
            return new WP_Error(
                'invalid_attachment',
                __('The provided URL does not correspond to a valid attachment.', 'text-domain'),
                array('status' => 400)
            );
        }

        // Get the focal point metadata for the image.
        $focal_point = get_post_meta($image_id, 'focal_point', true);

        // Convert the focal point to an array if it is an object.
        if (is_object($focal_point)) {
            $focal_point = (array) $focal_point;
        }

        // Prepare the default focal point.
        $default_focal_point = array(
            'x' => 0.5,
            'y' => 0.5,
        );

        // Use the focal point metadata if it exists and is valid.
        if (is_array($focal_point) && isset($focal_point['x'], $focal_point['y'])) {
            $response_data = array(
                'success' => true,
                'focal_point' => $focal_point,
                'image_id' => $image_id,
            );
        } else {
            $response_data = array(
                'success' => true,
                'focal_point' => $default_focal_point,
                'image_id' => $image_id,

            );
        }

        return rest_ensure_response($response_data);
    }

    // Register the function as a REST route callback if needed.
}






// Register the meta data
add_action('rest_api_init', 'wp_target_crop_register_meta_data');
if (!function_exists('wp_target_crop_register_meta_data')):

    function wp_target_crop_register_meta_data()
    {

        register_post_meta('attachment', 'focal_point', array(
            'single' => true,
            'type' => 'object',
            'default' => (object) array(
                'x' => 0.5,
                'y' => 0.5,
            ),
            'show_in_rest' => array(
                'schema' => array(
                    'type' => 'object',
                    'properties' => array(
                        'x' => array(
                            'type' => 'number',
                            'default' => 0.5,
                        ),
                        'y' => array(
                            'type' => 'number',
                            'default' => 0.5,
                        ),
                    ),
                    'required' => array('x', 'y'), // Ensure both properties are required
                ),
            ),
        ));
    }

endif;