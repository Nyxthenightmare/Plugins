<?php
/*
Plugin Name: Auto Blog Nyx
Description: Autoblog WordPress auto fetch RSS + fulltext (Readability), RSS bisa diatur lewat menu Settings.
Version: 1.1
Author: Nyx
*/

require_once __DIR__ . '/includes/fetcher.php';
require_once __DIR__ . '/includes/settings.php';

// Fungsi untuk konversi interval custom ke detik
function abft_get_cron_seconds() {
    $num = intval(get_option('abft_cron_interval_num', 1));
    $unit = get_option('abft_cron_interval_unit', 'jam');
    switch($unit) {
        case 'menit': $sec = 60; break;
        case 'jam':   $sec = 3600; break;
        case 'hari':  $sec = 86400; break;
        case 'bulan': $sec = 2592000; break; // 30 hari
        case 'tahun': $sec = 31536000; break; // 365 hari
        default:      $sec = 3600;
    }
    return max(60, $num * $sec); // minimal 1 menit
}

// Buat interval cron custom
add_filter('cron_schedules', function($schedules){
    $interval = abft_get_cron_seconds();
    $schedules['abft_custom'] = [
        'interval' => $interval,
        'display' => 'Autoblog Custom Interval'
    ];
    return $schedules;
});

// Daftar cron event
register_activation_hook(__FILE__, function() {
    if (!wp_next_scheduled('abft_cron_fetch')) {
        wp_schedule_event(time(), 'abft_custom', 'abft_cron_fetch');
    }
});
register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('abft_cron_fetch');
});

// Update cron jika setting berubah (interval)
add_action('update_option_abft_cron_interval_num', 'abft_reset_cron_schedule', 10, 0);
add_action('update_option_abft_cron_interval_unit', 'abft_reset_cron_schedule', 10, 0);
function abft_reset_cron_schedule() {
    wp_clear_scheduled_hook('abft_cron_fetch');
    if (!wp_next_scheduled('abft_cron_fetch')) {
        wp_schedule_event(time(), 'abft_custom', 'abft_cron_fetch');
    }
}

// Trigger fetch
add_action('abft_cron_fetch', 'abft_fetch_rss');

// Ubah abft_fetch_rss supaya pakai limit dari setting
function abft_fetch_rss() {
    $rss_url = get_option('abft_rss_url');
    $limit = intval(get_option('abft_fetch_limit', 5));
    if (!$rss_url) return;
    require_once __DIR__ . '/includes/fetcher.php';
    abft_do_fetch($rss_url, $limit);
}
