<?php

namespace Deployer;

require_once 'recipe/common.php';
require_once 'contrib/rsync.php';
require_once 'contrib/crontab.php';
require_once __DIR__.'/wp-cli.php';
require_once __DIR__.'/wordpress.php';

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

set('database_backup', '{{release_or_current_path}}/var/backup-' . date('c') . '.sql');

add('crontab:jobs', [
    '{{wordpress_cron_job}}',
]);

// Tasks
desc('Checkout repo');
task('badrock:checkout', function () {
    runLocally('rm -rf "{{build_path}}"');
    runLocally('mkdir -p "{{build_path}}"');
    runLocally('git --work-tree="{{build_path}}" checkout -f {{target}}');
})->once();

desc('Backup database');
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
    'wordpress:core:download',
    'wordpress:plugin:activate',
    'badrock:db:backup',
    'wordpress:db:migrate',
    'wordpress:language:install',
    'wordpress:rewrite:flush',
    'wordpress:cache:flush',
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
