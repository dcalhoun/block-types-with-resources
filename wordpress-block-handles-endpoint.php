<?php
/*
 * Plugin Name: WordPress Block Handles Endpoint
 */

require_once plugin_dir_path(__FILE__) . "includes/controller.php";

function wbhe_register_rest_routes() {
    $controller = new WP_REST_Block_Handles_Controller();
    $controller->register_routes();
}

add_action("rest_api_init", "wbhe_register_rest_routes");
