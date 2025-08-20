<?php
add_action('admin_menu', function() {
    add_menu_page('Autoblog Settings', 'Autoblog', 'manage_options', 'autoblog-settings', 'autoblog_settings_page');
});

// ENQUEUE ADMIN JS
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'toplevel_page_autoblog-settings') return;
    wp_enqueue_script('abft-admin-js', plugins_url('admin-abft.js', __FILE__), ['jquery'], null, true);
    wp_localize_script('abft-admin-js', 'abft_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('abft_check_rss_url')
    ]);
});

function autoblog_settings_page() {
    $options = get_option('autoblog_settings', [
        'feeds' => [],
        'interval' => 'hourly',
        'limit_per_fetch' => 5,
    ]);
    if (!isset($options['feeds'])) $options['feeds'] = [];
    $categories = get_categories(['hide_empty'=>0]);

    // Submit handler
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('autoblog_save_settings')) {
        $newfeeds = [];
        if (!empty($_POST['feeds']) && is_array($_POST['feeds'])) {
            foreach ($_POST['feeds'] as $f) {
                if (empty($f['url'])) continue;
                $newfeeds[] = [
                    'url' => esc_url_raw($f['url']),
                    'active' => !empty($f['active']),
                    'category_map' => intval($f['category_map']),
                ];
            }
        }
        $options['feeds'] = $newfeeds;
        $intervals = ['minute','hourly','3hour','6hour','daily','monthly','yearly'];
        $options['interval'] = in_array($_POST['interval'], $intervals) ? $_POST['interval'] : 'hourly';
        $options['limit_per_fetch'] = max(1, intval($_POST['limit_per_fetch']));
        update_option('autoblog_settings', $options);
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved!</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Autoblog Settings</h1>
        <form method="post" id="abft-form">
            <?php wp_nonce_field('autoblog_save_settings'); ?>
            <table class="widefat fixed" style="max-width:900px;" id="abft-feed-table">
                <thead>
                    <tr>
                        <th>Aktif</th>
                        <th>Feed URL</th>
                        <th>Kategori Map</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $feeds = $options['feeds'];
                $count = count($feeds);
                if ($count === 0) $feeds[] = ['url'=>'','active'=>0,'category_map'=>''];
                foreach ($feeds as $i => $feed): ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="feeds[<?= $i ?>][active]" <?= !empty($feed['active']) ? 'checked' : '' ?> />
                        </td>
                        <td>
                            <input type="text" class="feed-url-input" name="feeds[<?= $i ?>][url]" value="<?= esc_attr($feed['url']) ?>" style="width:100%;" />
                            <button type="button" class="cek-feed-url button">Cek Validitas</button>
                            <span class="feed-url-status"></span>
                        </td>
                        <td>
                            <select name="feeds[<?= $i ?>][category_map]">
                                <option value="">-</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat->term_id ?>" <?= $cat->term_id == $feed['category_map'] ? 'selected' : '' ?>>
                                    <?= esc_html($cat->name) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <?php if($i == count($feeds)-1): ?>
                                <button type="button" class="add-feed-row button">Tambah</button>
                            <?php else: ?>
                                <button type="button" class="delete-feed-row button" title="Hapus baris ini">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <h2 style="margin-top:30px;">Interval Cron</h2>
            <select name="interval">
                <option value="minute" <?= $options['interval']=='minute'?'selected':''; ?>>Setiap Menit</option>
                <option value="hourly" <?= $options['interval']=='hourly'?'selected':''; ?>>Setiap Jam</option>
                <option value="3hour" <?= $options['interval']=='3hour'?'selected':''; ?>>Setiap 3 Jam</option>
                <option value="6hour" <?= $options['interval']=='6hour'?'selected':''; ?>>Setiap 6 Jam</option>
                <option value="daily" <?= $options['interval']=='daily'?'selected':''; ?>>Setiap Hari</option>
                <option value="monthly" <?= $options['interval']=='monthly'?'selected':''; ?>>Setiap Bulan</option>
                <option value="yearly" <?= $options['interval']=='yearly'?'selected':''; ?>>Setiap Tahun</option>
            </select>

            <h2 style="margin-top:30px;">Limit Post per Fetch</h2>
            <input type="number" name="limit_per_fetch" value="<?= intval($options['limit_per_fetch']) ?>" min="1" />

            <br><br>
            <button type="submit" class="button button-primary">Simpan</button>
            <button type="button" id="abft-run-test" class="button button-secondary">Run Testing</button>
            <span id="abft-run-test-result" style="margin-left:20px;"></span>
        </form>
    </div>
    <style>
    .dashicons-trash { font-size:18px; vertical-align:middle; }
    </style>
    <script>
    // Tambah baris feed baru
    jQuery(document).on('click', '.add-feed-row', function() {
        var $tr = jQuery(this).closest('tr');
        var $clone = $tr.clone();
        var idx = $tr.index() + 1;
        var total = jQuery('#abft-feed-table tbody tr').length;
        // Update name indeks
        $clone.find('input,select').each(function(){
            var name = jQuery(this).attr('name');
            if(name) {
                name = name.replace(/\[\d+\]/g, '['+total+']');
                jQuery(this).attr('name', name);
                if(jQuery(this).attr('type') == 'checkbox') jQuery(this).prop('checked', false);
                else jQuery(this).val('');
            }
        });
        $clone.find('.feed-url-status').text('');
        // Action: jadikan baris lama jadi tombol hapus (tong sampah)
        $tr.find('.add-feed-row').replaceWith(
            '<button type="button" class="delete-feed-row button" title="Hapus baris ini"><span class="dashicons dashicons-trash"></span></button>'
        );
        // Baris baru tombolnya tetap tambah
        $clone.find('.delete-feed-row').replaceWith(
            '<button type="button" class="add-feed-row button">Tambah</button>'
        );
        jQuery('#abft-feed-table tbody').append($clone);
    });
    // Hapus baris feed (kecuali jika hanya sisa satu baris)
    jQuery(document).on('click', '.delete-feed-row', function(){
        var $tbody = jQuery('#abft-feed-table tbody');
        if($tbody.find('tr').length > 1){
            jQuery(this).closest('tr').remove();
            // Pastikan baris paling bawah selalu tombol tambah, baris lain tombol hapus
            $tbody.find('tr').each(function(i){
                var $aksi = jQuery(this).find('td:last');
                if(i == $tbody.find('tr').length-1){
                    $aksi.find('.delete-feed-row').replaceWith(
                        '<button type="button" class="add-feed-row button">Tambah</button>'
                    );
                }else{
                    $aksi.find('.add-feed-row').replaceWith(
                        '<button type="button" class="delete-feed-row button" title="Hapus baris ini"><span class="dashicons dashicons-trash"></span></button>'
                    );
                }
            });
        }
    });
    </script>
    <?php
}

// AJAX: Validasi RSS Feed (pakai CURL, User Agent dan support Atom)
add_action('wp_ajax_abft_validate_rss_url', function() {
    check_ajax_referer('abft_check_rss_url', 'nonce');
    $url = esc_url_raw($_POST['url']);
    if (!$url) wp_send_json_error();

    // CURL dengan User Agent
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    $xml = curl_exec($ch);
    curl_close($ch);

    if ($xml) {
        libxml_use_internal_errors(true);
        $feed = simplexml_load_string($xml);
        if ($feed && (isset($feed->channel) || isset($feed->entry))) {
            wp_send_json_success();
        }
    }
    wp_send_json_error();
});

// AJAX: Run Testing
add_action('wp_ajax_abft_run_test', function() {
    check_ajax_referer('abft_check_rss_url', 'nonce');
    if(function_exists('abft_fetch_rss')) {
        $out = abft_fetch_rss(true); // true = testing mode
        wp_send_json_success($out ? "Berhasil test posting" : "Tidak ada posting baru");
    }
    wp_send_json_error();
});