<?php
/*
 * Plugin Name: Block Types with Resources
 */

require_once plugin_dir_path(__FILE__) . "includes/controller.php";

function btr_register_rest_routes() {
    $controller = new BTR_REST_Block_Types_Controller();
    $controller->register_routes();
}

add_action("rest_api_init", "btr_register_rest_routes");
