<?php

declare(strict_types=1);

$app = $argv[0];
$root = dirname(__DIR__);

$restIndex = null;
$options = getopt('h', ['help', 'no-uppercase', 'no-numbers', 'no-specials'], $restIndex);
$args = array_slice($argv, $restIndex);

if (isset($options['h']) || isset($options['help'])) {
    echo <<<HELP
Generates random strings: passwords, salts and etc.

Usage:

  php {$app} [options] <action> [<length>]

Arguments:

  action  Action (e.g. 'salt', 'db-prefix', 'password')
  length  Length

Options:

  -h, --help          Show this help message
      --no-specials   Don't use special symbols
      --no-uppercase  Don't use uppercase symbols
      --no-numbers    Don't use numbers

Examples:

  php {$app} salt

  php {$app} db-prefix

  php {$app} --no-specials password 20

HELP;
    exit();
}

function generateRandomString(int $length, array $options = []): string
{
    $chars = 'abcdefghijklmnopqrstuvwxyz';
    if ($options['uppercase'] ?? true) {
        $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    }
    if ($options['numbers'] ?? true) {
        $chars .= '0123456789';
    }
    if ($options['specials'] ?? true) {
        $chars .= '!@#$%^&*()-_[]{}<>~`+=,.;:/?|';
    }

    $result = '';

    for ($i = 0; $i < $length; $i++) {
        $k = random_int(0, strlen($chars) - 1);

        $result .= $chars[$k];
    }

    return $result;
}

function generateSalt(int $length, array $options): void
{
    $keys = [
        'AUTH_KEY',
        'SECURE_AUTH_KEY',
        'LOGGED_IN_KEY',
        'NONCE_KEY',
        'AUTH_SALT',
        'SECURE_AUTH_SALT',
        'LOGGED_IN_SALT',
        'NONCE_SALT',
    ];

    foreach ($keys as $key) {
        printf("%s='%s'\n", $key, generateRandomString($length, $options));
    }
}

function generateDbPrefix(int $length, array $options = [])
{
    printf("DB_PREFIX='wp_%s_'\n", generateRandomString($length, [
        'uppercase' => false,
        'specials' => false,
    ] + $options));
}

function generatePassword(int $length, array $options)
{
    printf("%s\n", generateRandomString($length, $options));
}

if (!($action = $args[0] ?? null)) {
    fwrite(STDERR, 'Action required. Try --help' . PHP_EOL);
    exit(1);
}

$length = isset($args[1]) ? (int) $args[1] : null;

$actionOptions = [
    'numbers' => !isset($options['no-numbers']),
    'specials' => !isset($options['no-specials']),
    'uppercase' => !isset($options['no-uppercase']),
];

switch ($action) {
    case 'salt':
        generateSalt($length ?? 64, $actionOptions);
        break;
    case 'db-prefix':
        generateDbPrefix($length ?? 5, $actionOptions);
        break;
    case 'password':
        generatePassword($length ?? 16, $actionOptions);
        break;
    default:
        fwrite(STDERR, sprintf(
            "Invalid action: \"%s\". Allowed values: %s.\n",
            $action,
            '"salt", "db-prefix", "password"',
        ));
        exit(1);
}
