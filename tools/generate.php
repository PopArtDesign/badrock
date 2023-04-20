<?php

declare(strict_types=1);

function generateRandomString(int $length, $options = []): string
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

function generateSalt(int $length): void
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
        printf("%s='%s'\n", $key, generateRandomString($length));
    }
}

function generateDbPrefix(int $length)
{
    printf("DB_PREFIX='wp_%s_'\n", generateRandomString($length, [
        'uppercase' => false,
        'specials' => false,
    ]));
}

function generatePassword(int $length)
{
    printf("%s\n", generateRandomString($length));
}

function help()
{
    global $argv;

    echo <<<HELP
Generates random strings: passwords, salts and etc.

Usage:

  php {$argv[0]} <action> [<length>]

Allowed actions: "salt", "db-prefix", "password"

Examples:

  php {$argv[0]} salt

  php {$argv[0]} password 18

HELP;
}

$action = $argv[1] ?? 'help';
$length = isset($argv[2]) ? (int) $argv[2] : null;

switch ($action) {
    case '-h':
    case '--help':
    case 'help':
        help();
        break;
    case 'salt':
        generateSalt($length ?? 64);
        break;
    case 'db-prefix':
        generateDbPrefix($length ?? 5);
        break;
    case 'password':
        generatePassword($length ?? 16);
        break;
    default:
        fwrite(STDERR, sprintf(
            "Invalid action: \"%s\". Allowed values: %s.\n",
            $action,
            '"salt", "db-prefix", "password"',
        ));
        exit(1);
}
