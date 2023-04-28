<?php

use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__.'/../vendor/autoload.php';

function loadEnv(string $dotenvPath, string $env, string $defaultEnv): array
{
    $dotenv = new Dotenv('WP_ENV', 'WP_DEBUG');

    $globalsBackup = [$_SERVER, $_ENV];
    unset($_SERVER['WP_ENV']);
    $_ENV = ['WP_ENV' => $env];
    $_SERVER['SYMFONY_DOTENV_VARS'] = implode(',', array_keys($_SERVER));

    try {
        $dotenv->loadEnv($dotenvPath, null, $defaultEnv, ['test']);

        $secrets = dirname($dotenvPath).'/config/secrets/'.$_ENV['WP_ENV'];
        if (file_exists($secrets)) {
            $dotenv->load($secrets);
        }

        unset($_ENV['SYMFONY_DOTENV_VARS']);

        return $_ENV;
    } finally {
        [$_SERVER, $_ENV] = $globalsBackup;
    }
}

$defaultEnv = 'production';
$dotenvPath = dirname(__DIR__).'/.env';
$env = $argv[1] ?? $defaultEnv;

$vars = loadEnv($dotenvPath, $env, $defaultEnv);

$env = $vars['WP_ENV'];

$vars = var_export($vars, true);
$vars = <<<EOF
<?php

// This file was generated by running "php tools/dotenv-dump.php $env"

return $vars;
EOF;

file_put_contents($dotenvPath.'.local.php', $vars, \LOCK_EX);

printf('Successfully dumped .env files in ".env.local.php" for the "%s" environment.', $env);
echo "\n";

return 0;
