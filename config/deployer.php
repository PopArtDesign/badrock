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
desc('Prepare build directory');
task('badrock:build:prepare', function () {
    runLocally('rm -rf "{{build_dir}}"');
    runLocally('mkdir -p "{{build_dir}}"');
});

desc('Checkout repo');
task('badrock:build:checkout', function () {
    runLocally('git --work-tree="{{build_dir}}" checkout -f {{target}}');
});

desc('Install Phive tools');
task('badrock:build:tools', function () {
    runLocally('phive install --copy wp', [
        'cwd' => get('build_dir'),
    ]);
});

task('badrock:build', [
    'badrock:build:prepare',
    'badrock:build:checkout',
    'badrock:build:tools',
]);

task('deploy:update_code', function () {
    set('rsync_src', '{{build_dir}}');

    invoke('rsync');
});

task('deploy', [
    'badrock:build',
    'deploy:prepare',
    'deploy:vendors',
    'deploy:publish',
]);

// Hooks
after('deploy:failed', 'deploy:unlock');
