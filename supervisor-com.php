<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Plugin Name:       supervisor.com
 * Plugin URI:        http://wordpress.org/plugins/supervisor-com/
 * Description:       supervisor.com load testing and monitoring plugin for WordPress.
 * Version:           __SUPERVISOR_WORDPRESS_VERSION__
 * Author:            supervisorcom
 * Author URI:        https://www.supervisor.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

function supervisorcom_views_index() {
  include_once plugin_dir_path( __FILE__ )."/views/index.php";
}

add_action('admin_menu', function() {
  add_menu_page(
    "supervisor.com",
    "supervisor.com",
    "manage_options",
    "supervisorcom",
    "supervisorcom_views_index",
    "dashicons-tag",
  );
});

function supervisorcom_api_auth() {
  return current_user_can('manage_options');
}
function supervisorcom_secret_auth(WP_REST_Request $request) {
  $secretGiven = $request->get_param('secret');
  $secretStored = get_option('supervisorcom_v1_secret');

  return $secretGiven == $secretStored;
}

function supervisorcom_cpus() {
  if (!file_exists('/proc/stat')) {
    return array();
  }

  $data = file('/proc/stat');

  return array(
    'proc_stat' => $data
  );
}

add_action('rest_api_init', function () {
  register_rest_route( 'supervisorcom/v1', '/store',
    array(
      'methods' => 'PUT',
      'callback' => function(WP_REST_Request $request) {
        $obj = $request->get_json_params();

        $store = get_option('supervisorcom_v1_store');
        if (!$store) {
          $store = array();
        }
        $store[$obj['key']] = $obj['value'];
        update_option('supervisorcom_v1_store', $store);

        return true;
      },
      'permission_callback' => 'supervisorcom_api_auth',
    )
  );

  register_rest_route( 'supervisorcom/v1', '/store',
    array(
      'methods' => 'DELETE',
      'callback' => function(WP_REST_Request $request) {
        $obj = $request->get_json_params();

        $store = get_option('supervisorcom_v1_store');
        unset($store[$obj['key']]);
        update_option('supervisorcom_v1_store', $store);

        return true;
      },
      'permission_callback' => 'supervisorcom_api_auth',
    )
  );

  register_rest_route( 'supervisorcom/v1', '/authorization',
    array(
      'methods' => 'GET',
      'callback' => function(WP_REST_Request $request) {
        $store = get_option('supervisorcom_v1_store');
        if ($store['authorization']) {
          return $store['authorization'];
        } else {
          return "";
        }
      },
      'permission_callback' => function() {
        return true;
      },
    )
  );

  register_rest_route( 'supervisorcom/v3', '/cpus',
    array(
      'methods' => 'GET',
      'callback' => function(WP_REST_Request $request) {
        return supervisorcom_cpus();
      },
      'permission_callback' => 'supervisorcom_secret_auth',
    )
  );

});

function supervisorcom_uninstall() {
  delete_option('supervisorcom_v1_store');
  delete_option('supervisorcom_v1_secret');
}

register_deactivation_hook( __FILE__, 'supervisorcom_uninstall' );
register_uninstall_hook( __FILE__, 'supervisorcom_uninstall' );
?>
