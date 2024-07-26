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
                if (isset($wp_scripts->registered[$handle])) {
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

/**
 * Collect the block editor assets that need to be loaded into the embedded mobile editor.
 *
 * @global WP_Styles  $wp_styles  The WP_Styles current instance.
 * @global WP_Scripts $wp_scripts The WP_Scripts current instance.
 *
 * @return array {
 *     The block editor assets.
 *
 *     @type string|false $styles  String containing the HTML for styles.
 *     @type string|false $scripts String containing the HTML for scripts.
 * }
 */
function _btr_get_editor_assets() {
    global $wp_styles, $wp_scripts;

    // Keep track of the styles and scripts instance to restore later.
    $current_wp_styles = $wp_styles;
    $current_wp_scripts = $wp_scripts;

    // Create new instances to collect the assets.
    $wp_styles = new WP_Styles();
    $wp_scripts = new WP_Scripts();

    /*
     * Register all currently registered styles and scripts. The actions that
     * follow enqueue assets, but don't necessarily register them.
     */
    $wp_styles->registered = $current_wp_styles->registered;
    $wp_scripts->registered = $current_wp_scripts->registered;

    /*
     * We generally do not need reset styles for the iframed editor.
     * However, if it's a classic theme, margins will be added to every block,
     * which is reset specifically for list items, so classic themes rely on
     * these reset styles.
     */
    $wp_styles->done = wp_theme_has_theme_json()
        ? ["wp-reset-editor-styles"]
        : [];

    wp_enqueue_script("wp-polyfill");
    // Enqueue the `editorStyle` handles for all core block, and dependencies.
    wp_enqueue_style("wp-edit-blocks");

    if (current_theme_supports("wp-block-styles")) {
        wp_enqueue_style("wp-block-library-theme");
    }

    /*
     * We don't want to load EDITOR scripts in the iframe, only enqueue
     * front-end assets for the content.
     */
    add_filter("should_load_block_editor_scripts_and_styles", "__return_false");
    do_action("enqueue_block_assets");
    remove_filter(
        "should_load_block_editor_scripts_and_styles",
        "__return_false",
    );

    $block_registry = WP_Block_Type_Registry::get_instance();

    /*
     * Additionally, do enqueue `editorStyle` assets for all blocks, which
     * contains editor-only styling for blocks (editor content).
     */
    foreach ($block_registry->get_all_registered() as $block_type) {
        if (
            isset($block_type->editor_style_handles) &&
            is_array($block_type->editor_style_handles)
        ) {
            foreach ($block_type->editor_style_handles as $style_handle) {
                wp_enqueue_style($style_handle);
            }
        }

        if (
            isset($block_type->editor_script_handles) &&
            is_array($block_type->editor_script_handles)
        ) {
            foreach ($block_type->editor_script_handles as $script_handle) {
                wp_enqueue_script($script_handle);
            }
        }
    }

    /**
     * Remove the deprecated `print_emoji_styles` handler.
     * It avoids breaking style generation with a deprecation message.
     */
    $has_emoji_styles = has_action("wp_print_styles", "print_emoji_styles");
    if ($has_emoji_styles) {
        remove_action("wp_print_styles", "print_emoji_styles");
    }

    ob_start();
    wp_print_styles();
    wp_print_font_faces();
    $styles = ob_get_clean();

    if ($has_emoji_styles) {
        add_action("wp_print_styles", "print_emoji_styles");
    }

    ob_start();
    wp_print_head_scripts();
    wp_print_footer_scripts();
    $scripts = ob_get_clean();

    // Restore the original instances.
    $wp_styles = $current_wp_styles;
    $wp_scripts = $current_wp_scripts;

    return [
        "styles" => $styles,
        "scripts" => $scripts,
    ];
}

function btr_get_editor_assets() {
    $editor_assets = _btr_get_editor_assets();
    return new WP_REST_Response($editor_assets);
}

add_action("rest_api_init", function () {
    register_rest_route("btr/v1", "editor-assets", [
        "methods" => "GET",
        "callback" => "btr_get_editor_assets",
        "permission_callback" => "__return_true",
    ]);
});

function register_custom_block_type() {
    $block_type = [
        "name" => "foo",
        "title" => "Foo Block",
        "script" => "custom-block-script",
        "style" => "custom-block-style",
    ];

    register_block_type("bar/foo", $block_type);
}

add_action("init", "register_custom_block_type");

function enqueue_custom_block_assets() {
    wp_enqueue_script(
        "custom-block-script",
        plugin_dir_url(__FILE__) . "custom-block.js",
    );
    wp_enqueue_style(
        "custom-block-style",
        plugin_dir_url(__FILE__) . "custom-block.css",
    );
}

add_action("enqueue_block_assets", "enqueue_custom_block_assets");
