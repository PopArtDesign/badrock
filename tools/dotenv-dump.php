<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__.'/../vendor/autoload.php';

$app = $argv[0];
$root = dirname(__DIR__);

$restIndex = null;
$options = getopt('h', ['help'], $restIndex);
$args = array_slice($argv, $restIndex);

if (isset($options['h']) || isset($options['help'])) {
    echo <<<HELP
Dumps all .env.* files into one PHP file.

Usage:

  php {$app} [options] <env> [<file>]

Arguments:

  env   Environment (WP_ENV e.g. 'production', 'staging')
  file  PHP file (e.g. '.env.local.php')

Options:

  -h, --help  Show this help message

Examples:

  php {$app} production ./.env.local.php

HELP;
    exit();
}

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
        if (is_file($secrets)) {
            $dotenv->load($secrets);
        }

        unset($_ENV['SYMFONY_DOTENV_VARS']);

        return $_ENV;
    } finally {
        [$_SERVER, $_ENV] = $globalsBackup;
    }
}

if (!($env = $args[0] ?? null)) {
    fwrite(STDERR, 'Environment required. Try --help' . PHP_EOL);
    exit(1);
}

$file = $args[1] ?? null;

$defaultEnv = $env;
$dotenvPath = $root . '/.env';

$vars = loadEnv($dotenvPath, $env, $defaultEnv);

$env = $vars['WP_ENV'];

if (!$file) {
    foreach ($vars as $key => $value) {
        printf("%s='%s'".PHP_EOL, $key, $value);
    }

    return 0;
}

$vars = var_export($vars, true);
$vars = <<<EOF
<?php

// This file was generated by running "php tools/dotenv-dump.php {$env}"

return {$vars};
EOF;

if (false === file_put_contents($file, $vars, \LOCK_EX)) {
    fwrite(STDERR, 'Failed to wirte file: ' . $file . PHP_EOL);
    exit(1);
}

printf('Successfully dumped .env files in "%s" for the "%s" environment.'.PHP_EOL, $file, $env);

return 0;
