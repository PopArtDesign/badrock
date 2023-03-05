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
    'public/uploads',
    'var',
    'var/log',
]);

set('build_path', dirname(__DIR__) . '/var/build');

set('secrets_path', '{{build_path}}/config/secrets');

set('tools_path', '{{release_or_current_path}}/tools');

set('bin/wp', 'cd "{{release_or_current_path}}" && {{bin/php}} "{{tools_path}}/wp"');

set('wordpress_installed', function () {
    return test('{{bin/wp}} core is-installed');
});

// Tasks
desc('Checkout repo');
task('badrock:checkout', function () {
    runLocally('rm -rf "{{build_path}}"');
    runLocally('mkdir -p "{{build_path}}"');
    runLocally('git --work-tree="{{build_path}}" checkout -f {{target}}');
})->once();

desc('Install tools (wp-cli)');
task('badrock:tools', function () {
    runLocally('phive install --copy wp', [
        'cwd' => get('build_path'),
    ]);
})->once();

desc('Upload code to remote server');
task('badrock:rsync', function () {
    $rsyncSrcPrev = get('rsync_src');
    $rsyncDstPrev = get('rsync_dest');

    set('rsync_src', '{{build_path}}');
    set('rsync_dest', '{{release_path}}');

    invoke('rsync');

    set('rsync_src', $rsyncSrcPrev);
    set('rsync_dest', $rsyncDstPrev);
});

task('deploy:update_code', function () {
    invoke('badrock:rsync');
});

desc('WordPress: install languages');
task('badrock:languages', function () {
    if (!get('wordpress_installed')) {
        warning('WordPress is not installed.');
        return;
    }

    $languages = get('languages');

    if (empty($languages)) {
        return;
    }

    if (is_array($languages)) {
        $languages = implode(' ', $languages);
    }

    run('{{bin/wp}} language core install ' . $languages);
    run('{{bin/wp}} language plugin install --all '. $languages);
    run('{{bin/wp}} language theme install --all ' . $languages);
    run('{{bin/wp}} language core update');
    run('{{bin/wp}} language plugin update --all');
    run('{{bin/wp}} language theme update --all');
});

desc('WordPress: migrate database');
task('badrock:migrate-db', function () {
    if (!get('wordpress_installed')) {
        warning('WordPress is not installed.');
        return;
    }

    run('{{bin/wp}} core update-db');

    if (test('{{bin/wp}} plugin is-active woocommerce')) {
        run('{{bin/wp}} wc update');
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
        warning('WordPress is not installed.');
        return;
    }

    run('{{bin/wp}} cache flush');
});

task('badrock:build', [
    'badrock:checkout',
    'badrock:tools',
]);

task('badrock:deploy', [
    'badrock:secrets',
    'badrock:dump-dotenv',
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

// Hooks
after('deploy:failed', 'deploy:unlock');
