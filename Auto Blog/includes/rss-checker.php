<?php
// AJAX: Pengecekan RSS
add_action('wp_ajax_abft_check_rss', function() {
    $rss_url = esc_url_raw($_POST['rss_url'] ?? '');
    $result = abft_check_rss_url($rss_url);
    wp_send_json($result);
});

function abft_check_rss_url($url) {
    if (!$url) return ['valid' => false, 'message' => 'URL kosong'];
    $response = wp_remote_get($url, ['timeout' => 10]);
    if (is_wp_error($response)) return ['valid' => false, 'message' => 'Gagal request'];
    $body = wp_remote_retrieve_body($response);
    if (!$body) return ['valid' => false, 'message' => 'Tidak ada isi'];
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($body);
    if ($xml && ($xml->channel || $xml->entry)) {
        return ['valid' => true, 'message' => 'Valid'];
    }
    return ['valid' => false, 'message' => 'Format RSS tidak valid'];
}
