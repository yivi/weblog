<?php

namespace Deployer;

require 'recipe/common.php';

// Config

set('repository', '');

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

task('hugo:rebuild', function () {
    run("cd {{release_path}} && hugo --minify");
});

// Hosts

host('ludoteca')
    ->set('remote_user', 'deployer')
    ->set('deploy_path', '/var/www/weblog.yivoff.com');

// Hooks

task('deploy', [
    'deploy:prepare',
    'hugo:rebuild',
    'deploy:publish',
])->desc('Deploy your project');
