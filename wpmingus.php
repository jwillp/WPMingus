<?php
/**
 * Plugin Name:     Wpmingus
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          YOUR NAME HERE
 * Author URI:      YOUR SITE HERE
 * Text Domain:     wpmingus
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Wpmingus
 */

// If this file is called directly, abort.
if(!defined('WPINC')){die;}





/**
 * Creates the admin menu
 */
function wpmingus_createAdminMenu() {
    add_action( 'admin_menu', function(){    
        add_menu_page( 'WPMingus Dashboard', 
                       'WPMingus Dashboard', 
                       'manage_options', 
                       'wpmingus/dashboard.php', 
                       'wpmingus_renderAdminPage', 
                       'dashicons-dashboard', 0);
    });
}



function wpmingus_renderAdminPage() {
    // Front end dependencies
    // CSS
    wp_enqueue_style('wpmingus_dashboard_style', plugins_url('/dashboard/css/style.css', __FILE__));
    wp_enqueue_style('wpmingus_dashboard_style_framy', plugins_url('wpmingus') . '/dashboard/css/framy.min.css');
    wp_enqueue_style('wpmingus_dashboard_style_fontawesome', plugins_url('wpmingus') . '/dashboard/css/font-awesome.min.css');


    // Javascript    

    wp_enqueue_script('wpmingus_dashboard_circle_progress', 
        plugins_url('/dashboard/js/circle-progress.min.js', __FILE__), array ( 'jquery' ), 1.0, true);

    wp_enqueue_script('wpmingus_dashboard', plugins_url('/dashboard/js/dashboard.js', __FILE__), array ( 'jquery' ), 1.0, true);


    // Template file
    include "dashboard/dashboard.html";
}





add_action( 'plugins_loaded', 'wpmingus_main' );

/**
 * Starts the plugin.
 *
 * @since 1.0.0
 */
function wpmingus_main() {

    // Create Menu
    wpmingus_createAdminMenu();

 
}
