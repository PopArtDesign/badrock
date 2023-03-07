<?php

namespace Deployer;

require __DIR__.'/config/deployer.php';

host('production')
    ->set('hostname', 'production')
    ->set('environment', 'production')
    ->set('branch', 'main')
    ->set('remote_user', 'production')
    ->set('deploy_path', '/production')
;

after('deploy:failed', 'deploy:unlock');
