<?php
/**
 * Plugin Name: App
 * Plugin URI:  https://github.com/PopArtDesign/bedrock
 * Description: App Plugin
 * Author:      Oleg Voronkovich <oleg-voronkovich@yandex.ru>
 * Author URI:  https://github.com/voronkovich
 * License:     MIT License
 */

namespace App;

defined('ABSPATH') || exit;

if (is_blog_installed() && class_exists(App::class)) {
    $app = new App();

    add_theme_support('soil', array_merge_recursive(
        $app->getSoilDefaults(),
        defined('SOIL') ? SOIL : [],
    ));

    $app->run();
}
