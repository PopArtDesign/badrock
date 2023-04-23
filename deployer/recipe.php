<?php

namespace Deployer;

require 'recipe/common.php';
require 'contrib/rsync.php';
require 'contrib/crontab.php';
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

set('bin/cronjob_wp', function () {
    return str_replace(
        get('release_or_current_path'),
        get('current_path'),
        parse('{{bin/wp}} cron event run --due-now'),
    );
});

set('bin/cronjob_wget', function () {
    return parse('wget --no-check-certificate -O - {{wordpress_siteurl}}/wp-cron.php?doing_wp_cron');
});

set('bin/cronjob', '{{bin/cronjob_wp}}');

set('cronjob_interval', '* * * * *');

add('crontab:jobs', [
    '{{cronjob_interval}} cd {{current_path}} && {{bin/cronjob}} &>/dev/null',
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

set('wordpress_active_plugins', []);

// Tasks
desc('Checkout repo');
task('badrock:checkout', function () {
    runLocally('rm -rf "{{build_path}}"');
    runLocally('mkdir -p "{{build_path}}"');
    runLocally('git --work-tree="{{build_path}}" checkout -f {{target}}');
})->once();

desc('WordPress: download core');
task('badrock:core:download', function () {
    wp('core download');
});

desc('WordPress: install languages');
task('badrock:language:install', function () {
    if (!get('wordpress_installed')) {
        warning('Skip: WordPress is not installed.');
        return;
    }

    wp('language core install');
    wp('language plugin install --all');
    wp('language theme install --all');
});

desc('WordPress: activate/deactivate plugins');
task('badrock:plugin:activate', function () {
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
task('badrock:dotenv:dump', function () {
    run('{{bin/php}} {{tools_path}}/dotenv-dump.php {{environment}}');
});

desc('WordPress: clear cache');
task('badrock:cache:clear', function () {
    if (!get('wordpress_installed')) {
        warning('Skip: WordPress is not installed.');
        return;
    }

    wp('cache flush');
});

desc('WordPress: flush rewrite rules');
task('badrock:rewrite:flush', function () {
    if (!get('wordpress_installed')) {
        warning('Skip: WordPress is not installed.');
        return;
    }

    wp('rewrite flush');
});

desc('WordPress: activate maintenance mode');
task('maintenance:on', function () {
    if (!get('wordpress_installed')) {
        warning('Skip: WordPress is not installed.');
        return;
    }

    wp('maintenance-mode activate');
});

desc('WordPress: deactivate maintenance mode');
task('maintenance:off', function () {
    if (!get('wordpress_installed')) {
        warning('Skip: WordPress is not installed.');
        return;
    }

    wp('maintenance-mode deactivate');
});

desc('Create "public_html" symlink');
task('badrock:public_html', function () {
    run('cd {{deploy_path}} && {{bin/symlink}} {{current_path}}/public {{deploy_path}}/public_html');
});

task('badrock:build', [
    'badrock:checkout',
]);

task('badrock:deploy', [
    'badrock:secrets',
    'badrock:dotenv:dump',
    'badrock:core:download',
    'badrock:plugin:activate',
    'badrock:db:backup',
    'badrock:db:migrate',
    'badrock:language:install',
    'badrock:rewrite:flush',
    'badrock:cache:clear',
]);

after('deploy:symlink', 'badrock:public_html');

task('deploy:success', function () {
    info("Successfully deployed!\n\n{{wordpress_siteurl}}");
});

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
    'crontab:sync',
    'deploy:publish',
]);
