<?php

namespace Deployer;

require_once __DIR__.'/wp-cli.php';

set('wordpress_installed', function () {
    return wpTest('core is-installed');
});

set('wordpress_plugins', function () {
    return wpFetchPluginsList();
});

set('wordpress_active_plugins', []);

set('wordpress_constants', function () {
    return wpFetchConstants();
});

set('wordpress_siteurl', function () {
    return get('wordpress_constants')['WP_SITEURL'] ?? null;
});

set('bin/wordpress_cron_wp', function () {
    return str_replace(
        get('release_or_current_path'),
        get('current_path'),
        parse('{{bin/wp}} cron event run --due-now'),
    );
});

set('bin/wordpress_cron_wget', function () {
    return parse('wget --no-check-certificate -O - {{wordpress_siteurl}}/wp-cron.php?doing_wp_cron');
});

set('bin/wordpress_cron', '{{bin/wordpress_cron_wp}}');

set('wordpress_cron_interval', '*/5 * * * *');

set('wordpress_cron_job', '{{wordpress_cron_interval}} cd {{current_path}} && {{bin/wordpress_cron}} &>/dev/null');

desc('WordPress: download core');
task('wordpress:core:download', function () {
    wp('core download');
});

desc('WordPress: install languages');
task('wordpress:language:install', function () {
    if (!get('wordpress_installed')) {
        warning('Skip: WordPress is not installed.');
        return;
    }

    wp('language core install');
    wp('language plugin install --all');
    wp('language theme install --all');
});

desc('WordPress: migrate database');
task('wordpress:db:migrate', function () {
    if (!get('wordpress_installed')) {
        warning('Skip: WordPress is not installed.');
        return;
    }

    wp('core update-db');

    if (wpIsPluginActive('woocommerce')) {
        wp('wc update');
    }
});

desc('WordPress: activate/deactivate plugins');
task('wordpress:plugin:activate', function () {
    if (!get('wordpress_installed')) {
        warning('Skip: WordPress is not installed.');
        return;
    }

    $plugins = get('wordpress_active_plugins', []);
    if (empty($plugins)) {
        return;
    }

    foreach ($plugins as $plugin => $active) {
        if ($active) {
            wp('plugin activate '. $plugin);
        } else {
            wp('plugin deactivate '. $plugin);
        }
    }
});

desc('WordPress: activate maintenance mode');
task('wordpress:maintenance:activate', function () {
    if (!get('wordpress_installed')) {
        warning('Skip: WordPress is not installed.');
        return;
    }

    wp('maintenance-mode activate');
});

desc('WordPress: deactivate maintenance mode');
task('wordpress:maintenance:deactivate', function () {
    if (!get('wordpress_installed')) {
        warning('Skip: WordPress is not installed.');
        return;
    }

    wp('maintenance-mode deactivate');
});

desc('WordPress: flush cache');
task('wordpress:cache:flush', function () {
    if (!get('wordpress_installed')) {
        warning('Skip: WordPress is not installed.');
        return;
    }

    wp('cache flush');
});

desc('WordPress: flush rewrite rules');
task('wordpress:rewrite:flush', function () {
    if (!get('wordpress_installed')) {
        warning('Skip: WordPress is not installed.');
        return;
    }

    wp('rewrite flush');
});
