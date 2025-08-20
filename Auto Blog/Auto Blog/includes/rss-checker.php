<?php
// Fungsi untuk validasi RSS feed (cek response dan struktur XML)
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
