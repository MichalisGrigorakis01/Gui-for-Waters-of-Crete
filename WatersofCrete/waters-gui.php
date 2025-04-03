<?php
/*
Plugin Name: Waters of Crete
Description: A custom plugin to create a search form for the Results of monitoring the quality of the waters of Crete and printing the results in a table. 
Version: 1.0 
Author: Michalis Grigorakis csd4335
*/

// Define plugin directory
define('DM_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Include necessary files
require_once DM_PLUGIN_DIR . 'includes/waters-form.php';
require_once DM_PLUGIN_DIR . 'includes/watersform.php';
require_once DM_PLUGIN_DIR . 'includes/Waters_TableView.php';
require_once DM_PLUGIN_DIR . 'includes/Utility-functions.php';



// Enqueue plugin assets
function dm_enqueue_assets() {
    // Enqueue the main CSS first
    wp_enqueue_style(
        'dm-form-css-main', 
        plugin_dir_url(__FILE__) . 'public/css/form.css', 
        array(), 
        '1.0' 
    );

    wp_enqueue_style(
        'dm-loading-css',
        plugin_dir_url(__FILE__) . 'public/css/loading.css',
        array(), '1.0'
    );

    // Enqueue the table view CSS last to ensure it can override previous styles
    wp_enqueue_style(
        'dm-form-css-tableview', 
        plugin_dir_url(__FILE__) . 'public/css/tableview.css', 
        array(), 
        '1.0' 
    );

    // Enqueue JavaScript
    wp_enqueue_script(
        'dm-form-js', 
        plugin_dir_url(__FILE__) . 'public/js/form.js', 
        array('jquery'),  // Dependencies
        null, 
        true  // Load in footer
    );

    wp_enqueue_script(
        'dm-loading-js', 
        plugin_dir_url(__FILE__) . 'public/js/loading.js', 
        array('jquery'),  // Dependencies
        null, 
        true  // Load in footer
    );

    // Pass AJAX URL to JavaScript
    wp_localize_script('dm-form-js', 'dm_ajax_obj', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}
add_action('wp_enqueue_scripts', 'dm_enqueue_assets');

