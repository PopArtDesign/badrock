<?php
/**
 * Your base production configuration goes in this file. Environment-specific
 * overrides go in their respective config/environments/{{WP_ENV}}.php file.
 *
 * A good default policy is to deviate from the production config as little as
 * possible. Try to define as much of your configuration in this file as you
 * can.
 */

use function Env\env;
use Roots\WPConfig\Config;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Directory containing all of the site's files
 *
 * @var string
 */
$root_dir = dirname(__DIR__);

/**
 * Document Root
 *
 * @var string
 */
$webroot_dir = $root_dir . '/public';

/**
 * Use Dotenv to set required environment variables and load .env file in root
 * .env.local will override .env if it exists
 */
if (file_exists($root_dir . '/.env.local.php') || file_exists($root_dir . '/.env')) {
    $dotenv = new Dotenv('WP_ENV', 'WP_DEBUG');
    $dotenv->setProdEnvs(['production']);
    $dotenv->usePutenv();

    $dotenv->bootEnv($root_dir . '/.env', 'production');
}

/**
 * Set up our global environment constant and load its config first
 * Default: production
 */
define('WP_ENV', env('WP_ENV') ?: 'production');

/**
 * Infer WP_ENVIRONMENT_TYPE based on WP_ENV
 */
if (!env('WP_ENVIRONMENT_TYPE') && in_array(WP_ENV, ['production', 'staging', 'development'])) {
    Config::define('WP_ENVIRONMENT_TYPE', WP_ENV);
}

/**
 * URLs
 */
Config::define('WP_HOME', env('WP_HOME'));
Config::define('WP_SITEURL', env('WP_SITEURL'));

/**
 * Custom Content Directory
 */
Config::define('WP_CONTENT_DIR', $webroot_dir);
Config::define('WP_CONTENT_URL', Config::get('WP_HOME'));

/**
 * DB settings
 */
if (env('DB_SSL')) {
    Config::define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL);
}

Config::define('DB_NAME', env('DB_NAME'));
Config::define('DB_USER', env('DB_USER'));
Config::define('DB_PASSWORD', env('DB_PASSWORD'));
Config::define('DB_HOST', env('DB_HOST') ?: 'localhost');
Config::define('DB_CHARSET', 'utf8mb4');
Config::define('DB_COLLATE', '');
$table_prefix = env('DB_PREFIX') ?: 'wp_';

if (env('DATABASE_URL')) {
    $dsn = (object) parse_url(env('DATABASE_URL'));

    Config::define('DB_NAME', substr($dsn->path, 1));
    Config::define('DB_USER', $dsn->user);
    Config::define('DB_PASSWORD', isset($dsn->pass) ? $dsn->pass : null);
    Config::define('DB_HOST', isset($dsn->port) ? "{$dsn->host}:{$dsn->port}" : $dsn->host);
}

/**
 * Authentication Unique Keys and Salts
 */
Config::define('AUTH_KEY', env('AUTH_KEY'));
Config::define('SECURE_AUTH_KEY', env('SECURE_AUTH_KEY'));
Config::define('LOGGED_IN_KEY', env('LOGGED_IN_KEY'));
Config::define('NONCE_KEY', env('NONCE_KEY'));
Config::define('AUTH_SALT', env('AUTH_SALT'));
Config::define('SECURE_AUTH_SALT', env('SECURE_AUTH_SALT'));
Config::define('LOGGED_IN_SALT', env('LOGGED_IN_SALT'));
Config::define('NONCE_SALT', env('NONCE_SALT'));

/**
 * Custom Settings
 */
Config::define('AUTOMATIC_UPDATER_DISABLED', true);
Config::define('DISABLE_WP_CRON', env('DISABLE_WP_CRON') ?: false);
// Disable the plugin and theme file editor in the admin
Config::define('DISALLOW_FILE_EDIT', true);
// Disable plugin and theme updates and installation from the admin
Config::define('DISALLOW_FILE_MODS', true);
// Limit the number of post revisions that Wordpress stores (true (default WP): store every revision)
Config::define('WP_POST_REVISIONS', env('WP_POST_REVISIONS') ?? 5);
Config::define('FS_METHOD', 'direct');

/**
 * Debugging Settings
 */
Config::define('WP_DEBUG_DISPLAY', false);
Config::define('SCRIPT_DEBUG', false);
ini_set('display_errors', '0');

/**
 * Logging Settings
 */
$log_dir = $root_dir . '/var/log';
// It is possible to use PHP streams e.g. 'php://stderr', 'php://stdout'
Config::define('LOG_STREAM', env('LOG_STREAM') ?? sprintf('%s/%s.log', $log_dir, WP_ENV));
Config::define('WP_DEBUG_LOG', env('WP_DEBUG_LOG') ?? sprintf('%s/wordress_%s.log', $log_dir, WP_ENV));
// WooCommerce
Config::define('WC_LOG_DIR', $log_dir . '/');

/**
 * Allow WordPress to detect HTTPS when used behind a reverse proxy or a load balancer
 * See https://codex.wordpress.org/Function_Reference/is_ssl#Notes
 */
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

// https://github.com/roots/soil#modules
Config::define('SOIL', [
    'clean-up',
    'disable-asset-versioning',
    'disable-trackbacks',
    'js-to-footer',
    'nav-walker',
    'nice-search',
    'relative-urls',
]);

// https://github.com/globalis-ms/wp-unhooked
Config::define('WP_UNHOOKED_CONFIG', [
    'disable-comments' => true,
    'disable-admin-dashboard-welcome-panel' => true,
    'disable-admin-dashboard-widget-primary' => true,
]);

$env_config = __DIR__ . '/environments/' . WP_ENV . '.php';

if (file_exists($env_config)) {
    require_once $env_config;
}

Config::apply();

/**
 * Bootstrap WordPress
 */
if (!defined('ABSPATH')) {
    define('ABSPATH', $webroot_dir);
}
