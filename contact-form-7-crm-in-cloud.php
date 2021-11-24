<?php
/**
 * Contact Form 7 CRM in Cloud
 *
 *
 * @link
 * @since             1.0.0
 * @package           qs_cf7_crm_in_cloud
 *
 * @wordpress-plugin
 * Plugin Name:       Contact Form 7 CRM in Cloud
 * Plugin URI:        https://github.com/CRM-in-Cloud/
 * Description:       Connect contact Frms 7 to CRM in Cloud.
 * Version:           1.0.2
 * Author:            Stefano Straus base on hard work of Kenny Meyer (https://www.kennymeyer.net)
 * Author URI: 		  https://www.crmincloud.it
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       qs-cf7-crm-in-cloud
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'QS_CF7_CRM_IN_CLOUD_PLUGIN_PATH' , plugin_dir_path( __FILE__ ) );
define( 'QS_CF7_CRM_IN_CLOUD_INCLUDES_PATH' , plugin_dir_path( __FILE__ ). 'includes/' );
define( 'QS_CF7_CRM_IN_CLOUD_TEMPLATE_PATH' , get_template_directory() );
define( 'QS_CF7_CRM_IN_CLOUD_ADMIN_JS_URL' , plugin_dir_url( __FILE__ ). 'assets/js/' );
define( 'QS_CF7_CRM_IN_CLOUD_ADMIN_CSS_URL' , plugin_dir_url( __FILE__ ). 'assets/css/' );
define( 'QS_CF7_CRM_IN_CLOUD_FRONTEND_JS_URL' , plugin_dir_url( __FILE__ ). 'assets/js/' );
define( 'QS_CF7_CRM_IN_CLOUD_FRONTEND_CSS_URL' , plugin_dir_url( __FILE__ ). 'assets/css/' );
define( 'QS_CF7_CRM_IN_CLOUD_IMAGES_URL' , plugin_dir_url( __FILE__ ). 'assets/css/' );

add_action( 'plugins_loaded', 'qs_cf7_textdomain' );
/**
 * Load plugin textdomain.
 *
 * @since 1.0.0
 */
function qs_cf7_textdomain() {
    load_plugin_textdomain( 'qs-cf7-crm-in-cloud', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
/**
 * The core plugin class
 */
require_once QS_CF7_CRM_IN_CLOUD_INCLUDES_PATH . 'class.cf7-crm-in-cloud.php';

/**
 * Activation and deactivation hooks
 *
 */
register_activation_hook( __FILE__ ,  'cf7_crm_in_cloud_activation_handler'  );
register_deactivation_hook( __FILE__ , 'cf7_crm_in_cloud_deactivation_handler' );


function cf7_crm_in_cloud_activation_handler(){
    do_action( 'cf7_crm_in_cloud_activated' );
}

function cf7_crm_in_cloud_deactivation_handler(){
    do_action( 'cf7_crm_in_cloud_deactivated' );
}
/**
 * Begins execution of the plugin.
 *
 * Init the plugin process
 *
 * @since    1.0.0
 */
function qs_init_cf7_crm_in_cloud() {
    global $qs_cf7_crm_in_cloud;

	$qs_cf7_crm_in_cloud = new QS_CF7_atp_integration();
	$qs_cf7_crm_in_cloud->version = '1.0.2';
	$qs_cf7_crm_in_cloud->plugin_basename = plugin_basename( __FILE__ );

	$qs_cf7_crm_in_cloud->init();

}

qs_init_cf7_crm_in_cloud();
