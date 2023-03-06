<?php

namespace Deployer;

require 'recipe/common.php';
require 'contrib/rsync.php';

add('recipes', ['badrock']);

// Config
set('environment', 'production');

add('shared_files', []);

add('shared_dirs', [
    'public/uploads',
    'var/log',
]);

add('writable_dirs', [
    'public/cache',
    'public/uploads',
    'var',
    'var/log',
]);

set('build_path', dirname(__DIR__) . '/var/build');

set('secrets_path', '{{build_path}}/config/secrets');

set('tools_path', '{{release_or_current_path}}/tools');

set('rsync_src', '{{build_path}}');

set('bin/wp', '{{release_or_current_path}}/vendor/bin/wp');

set('wordpress_installed', function () {
    return testWP('core is-installed');
});

set('woocommerce_installed', function () {
    return testWP('plugin is-active woocommerce');
});

function runWP($command)
{
    cd('{{release_or_current_path}}');

    return run('WP_CLI_PHP="{{bin/php}}" "{{bin/wp}}" '. $command);
}

function testWP($command)
{
    cd('{{release_or_current_path}}');

    return test('WP_CLI_PHP="{{bin/php}}" "{{bin/wp}}" '. $command);
}

// Tasks
desc('Checkout repo');
task('badrock:checkout', function () {
    runLocally('rm -rf "{{build_path}}"');
    runLocally('mkdir -p "{{build_path}}"');
    runLocally('git --work-tree="{{build_path}}" checkout -f {{target}}');
})->once();

desc('Upload code to remote server');
task('badrock:rsync', function () {
    invoke('rsync');
});

task('deploy:update_code', function () {
    invoke('badrock:rsync');
});

desc('WordPress: download core');
task('badrock:wordpress', function () {
    runWP('core download');
});

desc('WordPress: install languages');
task('badrock:languages', function () {
    if (!get('wordpress_installed')) {
        warning('Skip: WordPress is not installed.');
        return;
    }

    $languages = get('languages');

    if (empty($languages)) {
        return;
    }

    if (is_array($languages)) {
        $languages = implode(' ', $languages);
    }

    runWP('language core install ' . $languages);
    runWP('language plugin install --all '. $languages);
    runWP('language theme install --all ' . $languages);
    runWP('language core update');
    runWP('language plugin update --all');
    runWP('language theme update --all');
});

desc('WordPress: migrate database');
task('badrock:migrate-db', function () {
    if (!get('wordpress_installed')) {
        warning('Skip: WordPress is not installed.');
        return;
    }

    runWP('core update-db');

    if (get('woocommerce_installed')) {
        runWP('wc update');
    }
});

desc('Deploy secrets');
task('badrock:secrets', function () {
    $secrets = parse('{{secrets_path}}/{{environment}}');

    if (!file_exists($secrets)) {
        return;
    }

    upload($secrets, '{{release_or_current_path}}/.env.{{environment}}.local');
});

desc('Dump .env files to .env.local.php');
task('badrock:dump-dotenv', function () {
    run('"{{bin/php}}" "{{tools_path}}/dotenv-dump.php" {{environment}}');
});

desc('WordPress: clear cache');
task('badrock:clear-cache', function () {
    if (!get('wordpress_installed')) {
        warning('Skip: WordPress is not installed.');
        return;
    }

    runWP('cache flush');
});

task('badrock:build', [
    'badrock:checkout',
]);

task('badrock:deploy', [
    'badrock:secrets',
    'badrock:dump-dotenv',
    'badrock:wordpress',
    'badrock:migrate-db',
    'badrock:languages',
    'badrock:clear-cache',
]);

task('deploy', [
    'badrock:build',
    'deploy:prepare',
    'deploy:vendors',
    'badrock:deploy',
    'deploy:publish',
]);
