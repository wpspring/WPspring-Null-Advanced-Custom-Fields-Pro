<?php
/**
        * Plugin Name: Null Advanced Custom Fields Pro
        * Plugin URI: https://wpspring.com/
        * Description: This plugin makes it easy to enabled the disabled features of ACF Pro without adding a license key. To activate, enter the license 12345=
        * Version: 1.0.0
        * Author: WPspring
        * Author URI: https://wpspring.com/
        * License: GPLv2 or later
        * Requires at least: 3.0
        * Tested up to: 4.9.8
        *
        * @author WPspring
        * Original concept by Sardina
        */

add_action('plugins_loaded', 'acf_pro_auto_patch', 20);

function acf_pro_auto_patch() {
    if (class_exists('ACF_Updates')) {
        class ACF_Updates_Patched extends ACF_Updates {
            public function request($endpoint = '', $body = null) {
                // Determine URL.
                $url = "https://connect.advancedcustomfields.test/$endpoint";

                // Staging environment.
                if (defined('ACF_DEV_API') && ACF_DEV_API) {
                    $url = trailingslashit(ACF_DEV_API) . $endpoint;
                    acf_log($url, $body);
                }

                $license_key = acf_pro_get_license_key();
                if (!$license_key) {
                    $license_key = '';
                }

                $site_url = acf_pro_get_home_url();
                if (!$site_url) {
                    $site_url = '';
                }

                // Simulated response.
                $raw_response = array(
                    'body' => '{"message":"<b>Licence key activation simulated</b>. Pro features are now enabled","license":"12345=","license_status":{"status":"active","lifetime":true,"name":"Personal","legacy_multisite":true,"view_licenses_url":"https://www.advancedcustomfields.test/my-account/view-licenses/"},"status":1}',
                    'response' => array(
                        'code' => 200,
                        'message' => 'OK'
                    ),
                );

                // Handle response error.
                if (is_wp_error($raw_response)) {
                    return $raw_response;
                } elseif (wp_remote_retrieve_response_code($raw_response) !== 200) {
                    return new WP_Error('server_error', wp_remote_retrieve_response_message($raw_response));
                }

                // Decode JSON response.
                $json = json_decode(wp_remote_retrieve_body($raw_response), true);
                // Allow non JSON value.
                if ($json === null) {
                    return wp_remote_retrieve_body($raw_response);
                }

                return $json;
            }
        }

        // Replace the original ACF_Updates instance with the patched one
        acf()->updates = new ACF_Updates_Patched();
    }
}
?>
