<?php
/**
 * Configuration overrides for WP_ENV === 'production'
 */

use Roots\WPConfig\Config;
use function Env\env;

Config::define('LOG_STREAM', 'php://stderr');
