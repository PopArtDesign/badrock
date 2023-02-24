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

    $config = include WPSTARTER_PATH . '/config.php';

    add_action('after_setup_theme', function () use ($config) {
        add_theme_support('soil', $config['soil']);
    });

    $app->run();
}
