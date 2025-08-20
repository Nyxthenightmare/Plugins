<?php
// ... kode lain tetap

require_once __DIR__ . '/rss-checker.php';

// Ambil data RSS feed list dari options (array)
$rss_feeds = get_option('abft_rss_feeds', [
    [
        'active' => false,
        'url' => '',
        'category' => '',
        'keyword' => '',
    ],
]);

// Simpan pengaturan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['abft_settings_nonce']) && wp_verify_nonce($_POST['abft_settings_nonce'], 'abft_save_settings')) {
    // Save RSS feeds
    $feeds = [];
    if (!empty($_POST['feed_url'])) {
        foreach ($_POST['feed_url'] as $i => $url) {
            $feeds[] = [
                'active' => !empty($_POST['feed_active'][$i]),
                'url' => esc_url_raw($url),
                'category' => sanitize_text_field($_POST['feed_category'][$i] ?? ''),
                'keyword' => sanitize_text_field($_POST['feed_keyword'][$i] ?? ''),
            ];
        }
    }
    update_option('abft_rss_feeds', $feeds);

    // Save cron interval
    update_option('abft_cron_interval_num', intval($_POST['abft_cron_interval_num']));
    update_option('abft_cron_interval_unit', sanitize_text_field($_POST['abft_cron_interval_unit']));
    // Save limit
    update_option('abft_fetch_limit', intval($_POST['abft_fetch_limit']));
    echo '<div class="updated"><p>Pengaturan disimpan!</p></div>';
}

// Load options
$fetch_limit = intval(get_option('abft_fetch_limit', 5));
$interval_num = intval(get_option('abft_cron_interval_num', 1));
$interval_unit = get_option('abft_cron_interval_unit', 'jam');
$units = [
    'menit' => 'Menit',
    'jam'   => 'Jam',
    'hari'  => 'Hari',
    'bulan' => 'Bulan',
    'tahun' => 'Tahun'
];

// Form
?>
<div class="wrap">
    <h1>Daftar RSS Feed</h1>
    <form method="post">
        <?php wp_nonce_field('abft_save_settings', 'abft_settings_nonce'); ?>
        <table class="form-table" style="width:auto;">
            <tr>
                <th>Aktif</th>
                <th>Feed URL</th>
                <th>Kategori Map</th>
                <th>Filter Keyword</th>
                <th>Check</th>
                <th>Hapus</th>
            </tr>
            <?php foreach ($rss_feeds as $i => $feed): ?>
            <tr>
                <td>
                    <input type="checkbox" name="feed_active[<?php echo $i; ?>]" <?php checked($feed['active']); ?>>
                </td>
                <td>
                    <input type="url" class="feed-url-input" name="feed_url[<?php echo $i; ?>]" value="<?php echo esc_attr($feed['url']); ?>" style="width:230px;" required>
                </td>
                <td>
                    <select name="feed_category[<?php echo $i; ?>]">
                        <option value="Hots Terbaru" <?php selected($feed['category'], 'Hots Terbaru'); ?>>Hots Terbaru</option>
                        <!-- Tambahkan kategori lain jika perlu -->
                    </select>
                </td>
                <td>
                    <input type="text" name="feed_keyword[<?php echo $i; ?>]" value="<?php echo esc_attr($feed['keyword']); ?>" style="width:120px;">
                </td>
                <td class="rss-status" id="rss_status_<?php echo $i; ?>"></td>
                <td>
                    <?php if ($i > 0): ?>
                        <button type="button" class="button remove-feed" data-row="<?php echo $i; ?>">Hapus</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <button type="button" class="button" id="add-feed">+ Feed</button>
        <h2>Interval Cron</h2>
        <input type="number" min="1" id="abft_cron_interval_num" name="abft_cron_interval_num" value="<?php echo $interval_num; ?>" style="width:60px;">
        <select id="abft_cron_interval_unit" name="abft_cron_interval_unit">
            <?php foreach ($units as $key => $label): ?>
                <option value="<?php echo $key; ?>" <?php selected($interval_unit, $key); ?>><?php echo $label; ?></option>
            <?php endforeach; ?>
        </select>
        <h2>Limit Post per Fetch</h2>
        <input type="number" id="abft_fetch_limit" name="abft_fetch_limit" value="<?php echo $fetch_limit; ?>" min="1" style="width:80px;">
        <br><br>
        <?php submit_button('Simpan'); ?>
    </form>
</div>
<script>
jQuery(document).ready(function($){
    // Cek RSS saat field url berubah
    $('.feed-url-input').on('change blur', function(){
        let url = $(this).val();
        let row = $(this).closest('tr');
        let statusTd = row.find('.rss-status');
        if(!url) { statusTd.text(''); return; }
        statusTd.text('Mengecek...');
        $.post(ajaxurl, {action: 'abft_check_rss', rss_url: url}, function(res){
            if(res.valid) statusTd.html('<span style="color:green">Aktif/Valid</span>');
            else statusTd.html('<span style="color:red">Tidak Aktif</span>');
        });
    });

    // Add new feed
    $('#add-feed').on('click', function(){
        let idx = $('table.form-table tr').length - 1;
        let html = `<tr>
            <td><input type="checkbox" name="feed_active[`+idx+`]"></td>
            <td><input type="url" class="feed-url-input" name="feed_url[`+idx+`]" value="" style="width:230px;" required></td>
            <td>
                <select name="feed_category[`+idx+`]">
                    <option value="Hots Terbaru">Hots Terbaru</option>
                </select>
            </td>
            <td><input type="text" name="feed_keyword[`+idx+`]" value="" style="width:120px;"></td>
            <td class="rss-status" id="rss_status_`+idx+`"></td>
            <td><button type="button" class="button remove-feed" data-row="`+idx+`">Hapus</button></td>
        </tr>`;
        $(this).closest('form').find('table.form-table').append(html);
    });

    // Remove feed row
    $(document).on('click', '.remove-feed', function(){
        $(this).closest('tr').remove();
    });
});
</script>
