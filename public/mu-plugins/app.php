<?php
/**
 * Plugin Name: App
 * Plugin URI:  https://github.com/PopArtDesign/badrock
 * Description: App Plugin
 * Author:      Oleg Voronkovich <oleg-voronkovich@yandex.ru>
 * Author URI:  https://github.com/voronkovich
 * License:     MIT License
 */

namespace App;

defined('ABSPATH') || exit;

if (is_blog_installed() && class_exists(App::class)) {
    $app = new App();

    add_action('after_setup_theme', function () {
        add_theme_support('soil', include $GLOBALS['root_dir'] . '/config/soil.php');
    });

    add_filter('sober/intervention/return', function ($path) {
        return $GLOBALS['root_dir'] . '/config/intervention.php';
    });

    $app->run();
}
