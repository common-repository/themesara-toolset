<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;
/*
Plugin Name: Themesara Toolset
Description: Import Themesara official Themes Demo Content, widgets and theme settings with just one click. 
Version:     1.1.0
Author:      Themsara
Author URI:  http://www.themesara.com
License:     GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: themesara-toolset
*/

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
define( 'THEMESARA_TOOLSET_PATH', plugin_dir_path( __FILE__ ) );
define( 'THEMESARA_TOOLSET_PLUGIN_NAME', 'themesara-toolset' );
define( 'THEMESARA_TOOLSET_VERSION', '1.0.9' );
define( 'THEMESARA_TOOLSET_URL', plugin_dir_url( __FILE__ ) );
define( 'THEMESARA_TOOLSET_TEMPLATE_URL', THEMESARA_TOOLSET_URL.'inc/demo/' );

require THEMESARA_TOOLSET_PATH . 'inc/init.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
if( !function_exists( 'run_themesara_toolset')){

    function run_themesara_toolset() {

        return Themesara_Toolset::instance();
    }
    run_themesara_toolset()->run();
}


