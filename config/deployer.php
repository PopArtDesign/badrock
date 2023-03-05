<?php

namespace Deployer;

require 'recipe/common.php';
require 'contrib/rsync.php';

// Config
add('shared_files', []);

add('shared_dirs', [
    'public/uploads',
    'var/log',
]);

add('writable_dirs', [
    'public/uploads'
]);

set('build_dir', dirname(__DIR__) . '/var/build');

// Tasks
desc('Checkout repo');
task('badrock:checkout', function () {
    runLocally('rm -rf "{{build_dir}}"');
    runLocally('mkdir -p "{{build_dir}}"');
    runLocally('git --work-tree="{{build_dir}}" checkout -f {{target}}');
});

desc('Install tools (wp-cli)');
task('badrock:tools', function () {
    runLocally('phive install --copy wp', [
        'cwd' => get('build_dir'),
    ]);
});

task('deploy:update_code', function () {
    set('rsync_src', '{{build_dir}}');

    invoke('rsync');
});

desc('Install WordPress languages');
task('badrock:languages', function () {
    $languages = get('languages');

    if (empty($languages)) {
        return;
    }

    if (is_array($languages)) {
        $languages = implode(' ', $languages);
    }

    cd('{{release_or_current_path}}');

    if (!test('tools/wp core is-installed')) {
        return;
    }

    run('tools/wp language core install ' . $languages);
    run('tools/wp language plugin install --all '. $languages);
    run('tools/wp language theme install --all ' . $languages);
    run('tools/wp language core update');
    run('tools/wp language plugin update --all');
    run('tools/wp language theme update --all');
});

task('badrock:db-migrations', function () {
    cd('{{release_or_current_path}}');

    if (!test('tools/wp core is-installed')) {
        return;
    }

    run('tools/wp core update-db');

    if (!test('tools/wp plugin is-installed woocommerce')) {
        run('tools/wp wc update');
    }
});

task('badrock:build', [
    'badrock:checkout',
    'badrock:tools',
]);

task('badrock:deploy', [
    'badrock:languages',
    'badrock:db-migrations',
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
