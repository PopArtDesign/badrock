<?php
/**
 * Configuration overrides for WP_ENV === 'production'
 */

use Roots\WPConfig\Config;

Config::define('WP_POST_REVISIONS', env('WP_POST_REVISIONS') ?? 5);
