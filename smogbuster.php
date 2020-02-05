<?php
/**
* Plugin Name: Smogbuster
* Plugin URI: https://www.oxyshop.pl/
* Description: Plugin for synchronization air quality data from api.gios.gov.pl
* Version: 1.0
* Author: Marcin Kosmala
* Author URI: https://www.oxyshop.pl/.
**/
require_once dirname(__FILE__).'/src/SmogBusterKernel.php';
require_once dirname(__FILE__).'/src/SmogBusterFetcher.php';
require_once dirname(__FILE__).'/src/SmogBusterApi.php';

global $wpdb;
$smogBusterKernel = new SmogBusterKernel($wpdb);

register_activation_hook(__FILE__, function () use ($smogBusterKernel) {
    $smogBusterKernel->install();
});

register_deactivation_hook(__FILE__, function () use ($smogBusterKernel) {
    $smogBusterKernel->uninstall();
});

register_activation_hook(__FILE__, function () {
    if (!wp_next_scheduled('smogbuster_sync_event')) {
        wp_schedule_event(time(), 'hourly', 'smogbuster_sync_event');
    }
});
add_action('smogbuster_sync_event', function () use ($smogBusterKernel) {
    $smogBusterKernel->fetcher->fetch();
});

add_action('rest_api_init', function () use ($smogBusterKernel) {
    register_rest_route('smogbuster', '/stations', [
        'methods' => 'GET',
        'callback' => function () use ($smogBusterKernel) {
            return $smogBusterKernel->api->getAirQuality();
        },
    ]);
});

add_action('rest_api_init', function () use ($smogBusterKernel) {
    register_rest_route('smogbuster', '/sync', [
        'methods' => 'GET',
        'callback' => function () use ($smogBusterKernel) {
            return $smogBusterKernel->fetcher->fetch();
        },
    ]);
});

add_shortcode('smogbuster', function () {
    return file_get_contents(dirname(__FILE__).'/templates/map.html');
});

add_action('wp_enqueue_scripts', function () {
    global $post;

    if (has_shortcode( $post->post_content, 'smogbuster')) {
        wp_enqueue_style('smogbuster-style', plugin_dir_url(__FILE__).'assets/smogbuster.css');
        wp_enqueue_style('smogbuster-mapbox-style', 'https://api.mapbox.com/mapbox.js/v3.1.1/mapbox.css');
    }
});

add_action('wp_footer', function () {
    global $post;

    if (has_shortcode( $post->post_content, 'smogbuster')) {
        wp_enqueue_script('smogbuster-mapbox-script', 'https://api.mapbox.com/mapbox.js/v3.1.1/mapbox.js');
        wp_enqueue_script('smogbuster-script', plugin_dir_url(__FILE__).'assets/smogbuster.js');
    }
});
