<?php

namespace Deployer;

require_once __DIR__.'/wp-cli.php';

set('wordpress_siteurl', function () {
    $config = wpGetConfig();

    $siteUrl = $config['WP_SITEURL'] ?? null;
    $homeUrl = $config['WP_HOME'] ?? null;

    return $siteUrl ?: $homeUrl;
});

set('bin/wordpress_cron', function () {
    return str_replace(
        get('release_or_current_path'),
        get('current_path'),
        parse('{{bin/wp}} cron event run --due-now'),
    );
});

set('wordpress_cron_interval', '*/5 * * * *');

set('wordpress_cron_job', '{{wordpress_cron_interval}} cd {{current_path}} && {{bin/wordpress_cron}} &>/dev/null');

function wordpressSkipIfNotInstalled()
{
    if (!wpIsCoreInstalled()) {
        info('Skip: WordPress is not installed.');

        return true;
    }

    return false;
}

desc('WordPress: download core');
task('wordpress:core:download', function () {
    wp('core download');
});

desc('WordPress: install languages');
task('wordpress:language:install', function () {
    if (wordpressSkipIfNotInstalled()) {
        return;
    }

    wp('language core install');
    wp('language plugin install --all');
    wp('language theme install --all');
});

desc('WordPress: migrate database');
task('wordpress:db:migrate', function () {
    if (wordpressSkipIfNotInstalled()) {
        return;
    }

    wp('core update-db');

    if (wpIsPluginActive('woocommerce')) {
        wp('wc update');
    }
});

desc('WordPress: flush cache');
task('wordpress:cache:flush', function () {
    if (wordpressSkipIfNotInstalled()) {
        return;
    }

    wp('cache flush');
});

desc('WordPress: flush rewrite rules');
task('wordpress:rewrite:flush', function () {
    if (wordpressSkipIfNotInstalled()) {
        return;
    }

    wp('rewrite flush');
});
