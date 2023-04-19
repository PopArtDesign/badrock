<?php
/**
 * Plugin Name: App
 * Plugin URI:  https://github.com/PopArtDesign/badrock
 * Description: App Plugin
 * Author:      PopArtDesign <info@popartdesign.ru>
 * Author URI:  https://popartdesign.ru
 * License:     MIT License
 */

namespace App;

use Inpsyde\Wonolog;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

defined('ABSPATH') || exit;

/**
 * Bootstrap Wonolog
 *
 * @see https://github.com/inpsyde/Wonolog
 */
if (defined('LOG_STREAM')) {
    $logHandler = new StreamHandler(LOG_STREAM, Logger::DEBUG);
    if ('production' === wp_get_environment_type()) {
        $logHadler = new FingersCrossedHandler($logHandler, Logger::ERROR);
    }

    Wonolog\bootstrap($logHandler);
    unset($logHandler);
}

if (!is_blog_installed()) {
    return;
}

if (defined('SOIL')) {
    add_action('after_setup_theme', function () {
        add_theme_support('soil', SOIL);
    });
}

if (class_exists(App::class)) {
    (new App())->run();
}
