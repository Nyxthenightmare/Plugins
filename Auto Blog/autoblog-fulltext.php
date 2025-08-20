<?php
/*
Plugin Name: Auto Blog Nyx
Description: Autoblog WordPress auto fetch RSS + fulltext (Readability), RSS bisa diatur lewat menu Settings.
Version: 1.0
Author: Nyx
*/

// Autoload PSR/Log agar trait/interface PSR selalu ready
require_once __DIR__ . '/readability/autoload-psrlog.php';

require_once __DIR__ . '/includes/fetcher.php';
require_once __DIR__ . '/includes/settings.php';

// Jadwalkan agar fetch otomatis setiap jam
register_activation_hook(__FILE__, function() {
    if (!wp_next_scheduled('abft_cron_fetch')) {
        wp_schedule_event(time(), 'hourly', 'abft_cron_fetch');
    }
});
register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('abft_cron_fetch');
});

add_action('abft_cron_fetch', 'abft_fetch_rss');