<?php
/**
 * Your base production configuration goes in this file. Environment-specific
 * overrides go in their respective config/environments/{{WP_ENV}}.php file.
 *
 * A good default policy is to deviate from the production config as little as
 * possible. Try to define as much of your configuration in this file as you
 * can.
 *
 * Examples:
 *
 * Config::define('WPLANG', env('WPLANG') ?? 'ru_RU');
 * Config::define('WP_DEFAULT_THEME', 'app');
 *
 * @see https://developer.wordpress.org/apis/wp-config-php/
 */

use Roots\WPConfig\Config;
use function Env\env;
