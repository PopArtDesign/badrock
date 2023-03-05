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

set('build_dir', dirname(__DIR__) . '/var/build');

set('tools_path', '{{release_or_current_path}}/tools');

set('bin/wp', '{{bin/php}} {{tools_path}}/wp');

set('wordpress_installed', function () {
    return test('{{bin/wp}} core is-installed');
});

// Tasks
desc('Checkout repo');
task('badrock:checkout', function () {
    runLocally('rm -rf "{{build_dir}}"');
    runLocally('mkdir -p "{{build_dir}}"');
    runLocally('git --work-tree="{{build_dir}}" checkout -f {{target}}');
})->once();

desc('Install tools (wp-cli)');
task('badrock:tools', function () {
    runLocally('phive install --copy wp', [
        'cwd' => get('build_dir'),
    ]);
})->once();

desc('Upload code to remote server');
task('badrock:rsync', function () {
    $rsyncSrcPrev = get('rsync_src');
    $rsyncDstPrev = get('rsync_dest');

    set('rsync_src', '{{build_dir}}');
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

    if (test('{{bin/wp}} plugin is-installed woocommerce')) {
        run('{{bin/wp}} wc update');
    }
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
