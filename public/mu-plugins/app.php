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

defined('ABSPATH') || exit;

if (is_blog_installed() && class_exists(App::class)) {
    $app = new App(WP_ENV, $root_dir);

    $app->init();

    $app->run();
}

class BaseApp
{
    private $env;
    private $rootDir;
    private $config;

    public function __construct(string $env, string $rootDir)
    {
        $this->env = $env;
        $this->rootDir = $rootDir;
    }

    public function getEnv(): string
    {
        return $this->env;
    }

    public function isEnv($env): bool
    {
        return $this->env === $env;
    }

    public function getRootDir(): string
    {
        return $this->rootDir;
    }

    public function getConfig(string $key = null)
    {
        if (null === $this->config) {
            $file = $this->rootDir . '/config/config.php';

            $this->config = file_exists($file) ? include($file) : [];
        }

        return $key ? ($this->config[$key] ?? null) : $this->config;
    }

    public function init(): void
    {
        add_action('after_setup_theme', function () {
            $soilConfig = $this->getConfig('soil');
            add_theme_support('soil', $soilConfig);
        });

        define('WP_UNHOOKED_CONFIG', $this->getConfig('unhooked'));

        $wonologConfig = $this->getConfig('wonolog');

        if ($wonologConfig['stream'] ?? false) {
            $logStream = $wonologConfig['stream'];
        } else {
            $logStream = sprintf('%s/var/log/%s.log', $this->getRootDir(), $this->getEnv());
        }

        $logHandler = new StreamHandler($logStream, Logger::DEBUG);
        if ($this->isEnv('production')) {
            $logHadler = new FingersCrossedHandler($logHandler, Logger::ERROR);
        }

        Wonolog\bootstrap($logHandler);
    }
}
