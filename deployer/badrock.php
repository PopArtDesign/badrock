<?php

namespace Deployer;

require_once 'recipe/common.php';
require_once 'contrib/rsync.php';
require_once 'contrib/crontab.php';
require_once __DIR__.'/wp-cli.php';
require_once __DIR__.'/wordpress.php';

add('recipes', ['badrock']);

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

set('public_symlink', 'public_html');

add('crontab:jobs', [
    '{{wordpress_cron_job}}',
]);

desc('Badrock: checkout repo');
task('badrock:checkout', function () {
    runLocally('rm -rf "{{build_path}}"');
    runLocally('mkdir -p "{{build_path}}"');
    runLocally('git --work-tree="{{build_path}}" checkout -f {{target}}');
})->once();

desc('Badrock: backup database');
task('badrock:db:backup', function () {
    if (wordpressSkipIfNotInstalled()) {
        return;
    }

    if (!get('database_backup')) {
        return;
    }

    wp('db export {{database_backup}}');
});

desc('Badrock: deploy secrets');
task('badrock:secrets', function () {
    $secrets = parse('{{secrets_path}}/{{environment}}');

    if (!file_exists($secrets)) {
        return;
    }

    upload($secrets, '{{release_or_current_path}}/.env.{{environment}}.local');
});

desc('Badrock: dump .env files');
task('badrock:dotenv:dump', function () {
    run('{{bin/php}} {{tools_path}}/dotenv-dump.php {{environment}}');
});

desc('Badrock: symlink public');
task('badrock:symlink:public', function () {
    if (!get('public_symlink')) {
        return;
    }

    run('cd {{deploy_path}} && {{bin/symlink}} {{current_path}}/public {{deploy_path}}/{{public_symlink}}');
});

after('deploy:symlink', 'badrock:symlink:public');

task('deploy', [
    'deploy:info',
    'badrock:checkout',
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'rsync',
    'deploy:shared',
    'deploy:vendors',
    'badrock:secrets',
    'badrock:dotenv:dump',
    'wordpress:core:download',
    'badrock:db:backup',
    'wordpress:db:migrate',
    'wordpress:language:install',
    'wordpress:rewrite:flush',
    'wordpress:cache:flush',
    'deploy:writable',
    'crontab:sync',
    'deploy:publish',
]);
