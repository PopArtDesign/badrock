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

use Inpsyde\Wonolog;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\ErrorHandler\Debug;

defined('ABSPATH') || exit;

if (!is_blog_installed()) {
    return;
}

if (class_exists(Debug::class)) {
    if (WP_DEBUG && WP_DEBUG_DISPLAY) {
        Debug::enable();
    }
}

if (defined('SOIL')) {
    add_action('after_setup_theme', function () {
        add_theme_support('soil', SOIL);
    });
}

if (defined('LOG_STREAM')) {
    $logHandler = new StreamHandler(LOG_STREAM, Logger::DEBUG);
    if (WP_ENV === 'production') {
        $logHadler = new FingersCrossedHandler($logHandler, Logger::ERROR);
    }

    Wonolog\bootstrap($logHandler);
    unset($logHandler);
}

if (class_exists(App::class)) {
    (new App())->run();
}
