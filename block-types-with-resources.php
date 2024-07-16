<?php
/*
 * Plugin Name: Block Types with Resources
 */

function btr_map_block_type_with_resources($response, $block_type, $request) {
    global $wp_styles;
    global $wp_scripts;

    foreach ($block_type as $key => $value) {
        if (
            strpos($key, "script") !== false &&
            substr($key, -8) === "_handles"
        ) {
            $resource_key = substr($key, 0, -8);
            $resource_paths = [];
            foreach ($value as $handle) {
                if (isset($wp_styles->registered[$handle])) {
                    $resource_paths[] = $wp_scripts->registered[$handle]->src;
                }
            }
            $response->data[$resource_key . "_paths"] = $resource_paths;
        } elseif (
            strpos($key, "style") !== false &&
            substr($key, -8) === "_handles"
        ) {
            $resource_key = substr($key, 0, -8);
            $resource_paths = [];
            foreach ($value as $handle) {
                if (isset($wp_styles->registered[$handle])) {
                    $resource_paths[] = $wp_styles->registered[$handle]->src;
                }
            }
            $response->data[$resource_key . "_paths"] = $resource_paths;
        }
    }

    return $response;
}

add_action(
    "rest_prepare_block_type",
    "btr_map_block_type_with_resources",
    10,
    3,
);
