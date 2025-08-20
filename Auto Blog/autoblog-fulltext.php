<?php
/*
Plugin Name: Auto Blog Nyx
Description: Autoblog WordPress auto fetch RSS + fulltext (Readability), RSS bisa diatur lewat menu Settings.
Version: 1.0
Author: Nyx
*/
add_filter('cron_schedules', function($schedules){
    $schedules['minute'] = ['interval'=>60, 'display'=>'Setiap Menit'];
    $schedules['3hour'] = ['interval'=>3*60*60, 'display'=>'Setiap 3 Jam'];
    $schedules['6hour'] = ['interval'=>6*60*60, 'display'=>'Setiap 6 Jam'];
    $schedules['monthly'] = ['interval'=>30*24*60*60, 'display'=>'Setiap Bulan'];
    $schedules['yearly'] = ['interval'=>365*24*60*60, 'display'=>'Setiap Tahun'];
    return $schedules;
});

// Autoload PSR/Log agar trait/interface PSR selalu ready
//require_once __DIR__ . '/readability/autoload-psrlog.php';

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