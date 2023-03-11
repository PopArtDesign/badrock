<?php

namespace Deployer;

require 'recipe/common.php';
require 'contrib/rsync.php';
require __DIR__.'/wp-cli.php';

add('recipes', ['badrock']);

// Config
set('environment', 'production');

add('shared_dirs', [
    'public/wp-content/uploads',
    'var/log',
]);

add('writable_dirs', [
    'public/wp-content/cache',
    'public/wp-content/uploads',
    'var',
    'var/log',
]);

set('build_path', dirname(__DIR__) . '/var/build');

set('secrets_path', '{{build_path}}/config/secrets');

set('tools_path', '{{release_or_current_path}}/tools');

set('database_backup', '{{release_or_current_path}}/var/backup-' . date('c') . '.sql');

set('rsync_src', '{{build_path}}');
add('rsync', [
    'exclude' => [
        '.ddev',
        '.git',
        '.gitattributes',
        '.gitignore',
        'Makefile',
        'config/secrets',
        'deploy.php',
        'deployer',
        'tests',
    ],
]);

set('wordpress_installed', function () {
    return wpTest('core is-installed');
});

set('wordpress_installed_plugins', function () {
    return wpFetchPluginsList();
});

set('wordpress_active_plugins', []);

// Functions
function wp($command)
{
    cd('{{release_or_current_path}}');

    return run('{{bin/wp}} '. $command);
}

function wpTest($command)
{
    cd('{{release_or_current_path}}');

    return test('{{bin/wp}} '. $command);
}

function wpFetchPluginsList()
{
    $list = \json_decode(
        wp('plugin list --json'),
        \JSON_OBJECT_AS_ARRAY
    );

    $plugins = [];
    foreach ($list as $plugin) {
        $plugins[$plugin['name']] = $plugin;
    }

    return $plugins;
}

function wpRefreshPluginsList()
{
    set('wordpress_installed_plugins', wpFetchPluginsList());
}

function wpGetPluginsList($refresh = false)
{
    if ($refresh) {
        wpRefreshPluginsList();
    }

    return get('wordpress_installed_plugins');
}

function wpGetPluginStatus($plugin, $refresh = false)
{
    $plugins = wpGetPluginsList($refresh);

    return $plugins[$plugin]['status'] ?? 'not-installed';
}

function wpIsPluginActive($plugin, $refresh = false)
{
    return 'active' === wpGetPluginStatus($plugin, $refresh);
}

// Tasks
desc('Checkout repo');
task('badrock:checkout', function () {
    runLocally('rm -rf "{{build_path}}"');
    runLocally('mkdir -p "{{build_path}}"');
    runLocally('git --work-tree="{{build_path}}" checkout -f {{target}}');
})->once();

desc('WordPress: download core');
task('badrock:wordpress', function () {
    wp('core download');
});

desc('WordPress: install languages');
task('badrock:languages', function () {
    if (!get('wordpress_installed')) {
        warning('Skip: WordPress is not installed.');
        return;
    }

    wp('language core install');
    wp('language plugin install --all');
    wp('language theme install --all');
});

desc('WordPress: activate/deactivate plugins');
task('badrock:activate-plugins', function () {
    if (!get('wordpress_installed')) {
        warning('Skip: WordPress is not installed.');
        return;
    }

    $plugins = get('wordpress_active_plugins', []);
    if (empty($plugins)) {
        return;
    }

    foreach ($plugins as $plugin => $active) {
        if ($active) {
            wp('plugin activate '. $plugin);
        } else {
            wp('plugin deactivate '. $plugin);
        }
    }
});

desc('WordPress: backup database');
task('badrock:db:backup', function () {
    if (!get('wordpress_installed')) {
        warning('Skip: WordPress is not installed.');
        return;
    }

    if (!get('database_backup')) {
        return;
    }

    wp('db export {{database_backup}}');
});

desc('WordPress: migrate database');
task('badrock:db:migrate', function () {
    if (!get('wordpress_installed')) {
        warning('Skip: WordPress is not installed.');
        return;
    }

    wp('core update-db');

    if (wpIsPluginActive('woocommerce')) {
        wp('wc update');
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
    run('{{bin/php}} {{tools_path}}/dotenv-dump.php {{environment}}');
});

desc('WordPress: clear cache');
task('badrock:clear-cache', function () {
    if (!get('wordpress_installed')) {
        warning('Skip: WordPress is not installed.');
        return;
    }

    wp('cache flush');
});

task('badrock:build', [
    'badrock:checkout',
]);

task('badrock:deploy', [
    'badrock:secrets',
    'badrock:dump-dotenv',
    'badrock:wordpress',
    'badrock:activate-plugins',
    'badrock:db:backup',
    'badrock:db:migrate',
    'badrock:languages',
    'badrock:clear-cache',
]);

task('deploy', [
    'deploy:info',
    'badrock:build',
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'rsync',
    'deploy:shared',
    'deploy:vendors',
    'badrock:deploy',
    'deploy:writable',
    'deploy:publish',
]);
