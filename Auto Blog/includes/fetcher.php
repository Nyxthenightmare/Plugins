<?php
error_log("DEBUG: Run Testing dijalankan");
require_once __DIR__ . '/fulltext.php';

// Fungsi ekstrak minimal 5 tag dari isi/judul berita (stopwords Indonesia)
function abft_extract_tags($title, $content, $min_tags = 5) {
    $text = strtolower(strip_tags($title . ' ' . $content));
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
    $stopwords = [
        'yang','dan','dengan','untuk','pada','dari','ini','akan','oleh','atau','juga','tidak','karena','ada','dalam',
        'para','bagi','sudah','saja','agar','bisa','adalah','itu','mereka','kami','kita','kamu','saya','ke','di',
        'sebagai','lebih','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','oleh','akan','kami','kalian','anda','ia','dia','pun','masih','hanya','setelah','sebelum','begitu','pula','saat','sejak','hingga','antara','selama','tanpa','karena','jadi','sebab','misal','contoh','bahwa','yaitu','yakni','punya','untuk','dalam','pada','jika','agar','supaya','adalah','merupakan','dapat','telah','sudah','akan','oleh','dengan','hingga','dari','ke','di','pada','sebagai','oleh','antara','terhadap','kepada','bagi','tanpa','selain','menjadi','setiap','semua','sama','lebih','kurang','besar','kecil','banyak','sedikit','baru','lama','selalu','sering','kadang','jarang','tidak','bukan','hanya','saja','bahkan','malah','namun','tetapi','atau','dan','juga','lalu','kemudian','setelah','sebelum','sambil','serta','sehingga','supaya','agar','karena','sebab','akibat','yang','apa','siapa','mengapa','bagaimana','dimana','kapan'
    ];
    $words = explode(' ', $text);
    $freq = [];
    foreach ($words as $w) {
        if (strlen($w) < 4) continue;
        if (in_array($w, $stopwords)) continue;
        if (!isset($freq[$w])) $freq[$w] = 0;
        $freq[$w]++;
    }
    arsort($freq);
    $tags = array_keys(array_slice($freq, 0, $min_tags));
    if (count($tags) < $min_tags) {
        $judul_kata = explode(' ', strtolower(strip_tags($title)));
        foreach ($judul_kata as $w) {
            if (in_array($w, $tags)) continue;
            if (strlen($w) < 4) continue;
            if (in_array($w, $stopwords)) continue;
            $tags[] = $w;
            if (count($tags) >= $min_tags) break;
        }
    }
    return $tags;
}

// Ganti awalan jadi BalkoNews -
function abft_replace_opening($content) {
    $pattern = '/^(<p>)?\s*(Jakarta|Surabaya|Bandung|Detik\s*News|Kompas\s*News|CNN\s*Indonesia|Tempo|VIVA|Okezone|Antara)[\s-:]+/i';
    return preg_replace($pattern, '$1BalkoNews - ', $content, 1);
}

// Set featured image dari gambar pertama di konten
function abft_set_featured_image_from_content($post_id, $content) {
    if(preg_match('/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', $content, $image)) {
        $image_url = $image['src'];
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        $attach_id = media_sideload_image($image_url, $post_id, null, 'id');
        if(!is_wp_error($attach_id)){
            set_post_thumbnail($post_id, $attach_id);
        }
    }
}

// Fungsi utama fetch RSS & posting
function abft_fetch_rss() {
    $rss_urls = get_option('abft_rss_urls', []);
    error_log("DEBUG: abft_fetch_rss() dipanggil, rss_urls: " . print_r($rss_urls, true));
    if (!is_array($rss_urls)) {
        $rss_urls = [$rss_urls];
    }
    include_once(ABSPATH . WPINC . '/feed.php');
    foreach ($rss_urls as $rss_url) {
        $rss = fetch_feed($rss_url);
        if (is_wp_error($rss)) continue;

        foreach ($rss->get_items(0, 5) as $item) {
            $title = $item->get_title();
            $link = $item->get_permalink();
            $guid = $item->get_id() ?: md5($link);

            // Cek duplikat
            $exists = new WP_Query([
                'post_type'  => 'post',
                'meta_key'   => '_rss_guid',
                'meta_value' => $guid,
                'fields'     => 'ids',
            ]);
            if ($exists->have_posts()) continue;

            // Ambil fulltext
            $content = abft_fetch_fulltext($link);
            if (!$content) $content = $item->get_content();

            // Awalan diganti
            $content = abft_replace_opening($content);

            // Posting
            $post_id = wp_insert_post([
                'post_title'   => $title,
                'post_content' => $content,
                'post_status'  => 'publish',
            ]);
            error_log('DEBUG: Hasil wp_insert_post: ' . print_r($post_id, true));
            update_post_meta($post_id, '_rss_guid', $guid);

            // Auto tag minimal 5 dari isi berita
            $tags = abft_extract_tags($title, $content, 5);
            wp_set_post_tags($post_id, $tags);

            // Featured image otomatis
            abft_set_featured_image_from_content($post_id, $content);
        }
    }
}
add_action('init', 'abft_fetch_rss');