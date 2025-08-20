<?php
// Tambahkan ini ke file utama plugin Anda, misal autoblog-plugin.php

add_action('admin_menu', function() {
    add_menu_page('Autoblog Settings', 'Autoblog', 'manage_options', 'autoblog-settings', 'autoblog_settings_page');
});

function autoblog_settings_page() {
    // Ambil data dari option
    $options = get_option('autoblog_settings', [
        'feeds' => [],
        'interval' => 'hourly',
        'limit_per_fetch' => 5,
    ]);
    if (!isset($options['feeds'])) $options['feeds'] = [];

    // Handle form submit
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('autoblog_save_settings')) {
        // Simpan feeds
        $feeds = [];
        if (!empty($_POST['feeds'])) {
            foreach ($_POST['feeds'] as $f) {
                if (empty($f['url'])) continue;
                $feeds[] = [
                    'url' => esc_url_raw($f['url']),
                    'active' => !empty($f['active']),
                    'category_map' => intval($f['category_map']),
                    'filter_keyword' => sanitize_text_field($f['filter_keyword']),
                ];
            }
        }
        // Tambah feed baru jika ada
        if (!empty($_POST['new_feed']['url'])) {
            $feeds[] = [
                'url' => esc_url_raw($_POST['new_feed']['url']),
                'active' => !empty($_POST['new_feed']['active']),
                'category_map' => intval($_POST['new_feed']['category_map']),
                'filter_keyword' => sanitize_text_field($_POST['new_feed']['filter_keyword']),
            ];
        }
        $options['feeds'] = $feeds;
        // Interval
        $options['interval'] = in_array($_POST['interval'], ['hourly','twicedaily','daily']) ? $_POST['interval'] : 'hourly';
        // Limit
        $options['limit_per_fetch'] = max(1, intval($_POST['limit_per_fetch']));

        update_option('autoblog_settings', $options);
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved!</p></div>';
    }

    // Render form
    $categories = get_categories(['hide_empty'=>0]);
    ?>
    <div class="wrap">
        <h1>Autoblog Settings</h1>
        <form method="post">
            <?php wp_nonce_field('autoblog_save_settings'); ?>

            <h2>Daftar RSS Feed</h2>
            <table class="widefat fixed" style="max-width:800px;">
                <thead>
                    <tr>
                        <th style="width:30px;">Aktif</th>
                        <th>Feed URL</th>
                        <th>Kategori Map</th>
                        <th>Filter Keyword</th>
                        <th>Hapus</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($options['feeds'] as $i => $feed): ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="feeds[<?= $i ?>][active]" <?= $feed['active'] ? 'checked' : '' ?> />
                        </td>
                        <td>
                            <input type="text" name="feeds[<?= $i ?>][url]" value="<?= esc_attr($feed['url']) ?>" style="width:100%;" />
                        </td>
                        <td>
                            <select name="feeds[<?= $i ?>][category_map]">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat->term_id ?>" <?= $feed['category_map']==$cat->term_id?'selected':'' ?>><?= esc_html($cat->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="feeds[<?= $i ?>][filter_keyword]" value="<?= esc_attr($feed['filter_keyword']) ?>" />
                        </td>
                        <td>
                            <input type="checkbox" name="feeds[<?= $i ?>][delete]" />
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td><input type="checkbox" name="new_feed[active]" /></td>
                        <td><input type="text" name="new_feed[url]" style="width:100%;" placeholder="https://feed.example.com/rss" /></td>
                        <td>
                            <select name="new_feed[category_map]">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat->term_id ?>"><?= esc_html($cat->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="text" name="new_feed[filter_keyword]" /></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
            <br>
            <h2>Interval Cron</h2>
            <select name="interval">
                <option value="hourly" <?= $options['interval']=='hourly'?'selected':'' ?>>Setiap Jam</option>
                <option value="twicedaily" <?= $options['interval']=='twicedaily'?'selected':'' ?>>Dua Kali Sehari</option>
                <option value="daily" <?= $options['interval']=='daily'?'selected':'' ?>>Harian</option>
            </select>
            <h2>Limit Post per Fetch</h2>
            <input type="number" name="limit_per_fetch" min="1" max="20" value="<?= (int)$options['limit_per_fetch'] ?>" />
            <br><br>
            <button type="submit" class="button button-primary">Simpan</button>
        </form>
    </div>
    <?php
}
