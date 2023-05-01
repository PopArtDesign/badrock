<?php

namespace Deployer;

require_once 'recipe/common.php';
require_once 'contrib/rsync.php';
require_once 'contrib/crontab.php';
require_once __DIR__.'/wp-cli.php';
require_once __DIR__.'/wordpress.php';

add('recipes', ['badrock']);

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

set('tools_path', '{{release_or_current_path}}/tools');

set('rsync_src', '{{build_path}}');

add('rsync', [
    'exclude' => [
        '.ddev',
        '.git',
        '.gitattributes',
        '.gitignore',
        'Makefile',
        'config/secrets/*',
        'deploy.php',
        'deployer',
        'tests',
    ],
    'include' => [
        'config/secrets/{{environment}}',
    ],
]);

set('dictator_file', '{{release_or_current_path}}/config/site-state.yaml');

set('database_backup', '{{release_or_current_path}}/var/backup-' . date('c') . '.sql');

set('public_symlink', 'public_html');

set('htpasswd_admin', '{{release_or_current_path}}/config/htpasswd-{{environment}}');

add('crontab:jobs', [
    '{{wordpress_cron_job}}',
]);

set('siteurl', function () {
    $config = wpGetConstants();

    $siteUrl = $config['WP_SITEURL'] ?? null;
    $homeUrl = $config['WP_HOME'] ?? null;

    return $siteUrl ?: $homeUrl;
});

desc('Badrock: detect environment');
task('badrock:environment:detect', function () {
    if (!get('environment')) {
        set('environment', remoteEnv()['WP_ENV'] ?? 'production');
    }

    info('WP_ENV={{environment}}');
});

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

desc('Badrock: dump .env files');
task('badrock:dotenv:dump', function () {
    run('{{bin/php}} {{tools_path}}/dotenv-dump.php {{environment}} {{release_or_current_path}}/.env.local.php');
});

desc('Badrock: dictator');
task('badrock:dictator:impose', function () {
    if (wordpressSkipIfNotInstalled()) {
        return;
    }

    if (!get('dictator_file') || !test('[ -f {{dictator_file}} ]')) {
        return;
    }

    wp('dictator impose {{dictator_file}}');
});

desc('Badrock: symlink public');
task('badrock:symlink:public', function () {
    if (!get('public_symlink')) {
        return;
    }

    run('cd {{deploy_path}} && {{bin/symlink}} {{current_path}}/public {{deploy_path}}/{{public_symlink}}');
});

desc('Badrock: protect /wp-admin');
task('badrock:htpasswd:admin', function () {
    if (!get('htpasswd_admin')) {
        return;
    }

    if (!test('[ -f "{{htpasswd_admin}}" ]')) {
        warning('Skip: .htpasswd file not found.');
        return;
    }

    run('{{bin/php}} {{tools_path}}/htpasswd-admin.php "{{htpasswd_admin}}"');
});

task('deploy:success', function () {
    info('Successfully deployed!');

    if (get('siteurl')) {
        writeln('');
        writeln('{{siteurl}}');
    }
});

after('deploy:symlink', 'badrock:symlink:public');

task('deploy', [
    'deploy:info',
    'badrock:environment:detect',
    'badrock:checkout',
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'rsync',
    'deploy:shared',
    'deploy:vendors',
    'badrock:dotenv:dump',
    'wordpress:core:download',
    'badrock:db:backup',
    'wordpress:db:migrate',
    'wordpress:language:install',
    'badrock:dictator:impose',
    'wordpress:rewrite:flush',
    'wordpress:cache:flush',
    'deploy:writable',
    'crontab:sync',
    'badrock:htpasswd:admin',
    'deploy:publish',
]);
