<?php
// Tambahkan menu di admin
add_action('admin_menu', function(){
    add_options_page(
        'Autoblog Fulltext Settings',
        'Autoblog Fulltext',
        'manage_options',
        'abft-settings',
        'abft_settings_page'
    );
});

// AJAX RSS checker
add_action('wp_ajax_abft_check_rss', function() {
    $rss_url = esc_url_raw($_POST['rss_url'] ?? '');
    require_once __DIR__ . '/rss-checker.php';
    $result = abft_check_rss_url($rss_url);
    wp_send_json($result);
});

function abft_settings_page() {
    // Simpan options
    if (isset($_POST['abft_rss_url'])) {
        check_admin_referer('abft_save_settings');
        update_option('abft_rss_url', esc_url_raw($_POST['abft_rss_url']));
        update_option('abft_fetch_limit', intval($_POST['abft_fetch_limit']));
        update_option('abft_cron_interval_num', intval($_POST['abft_cron_interval_num']));
        update_option('abft_cron_interval_unit', sanitize_text_field($_POST['abft_cron_interval_unit']));
        echo '<div class="updated"><p>Pengaturan disimpan!</p></div>';
    }
    $rss_url = esc_url(get_option('abft_rss_url', 'https://detik.com/rss'));
    $fetch_limit = intval(get_option('abft_fetch_limit', 5));
    $interval_num = intval(get_option('abft_cron_interval_num', 1));
    $interval_unit = get_option('abft_cron_interval_unit', 'jam');

    // Satuan waktu yang diizinkan
    $units = [
        'menit' => 'Menit',
        'jam'   => 'Jam',
        'hari'  => 'Hari',
        'bulan' => 'Bulan',
        'tahun' => 'Tahun'
    ];
    ?>
    <div class="wrap">
        <h1>Autoblog Fulltext Settings</h1>
        <form method="post">
            <?php wp_nonce_field('abft_save_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="abft_rss_url">RSS Feed URL</label></th>
                    <td>
                        <input type="url" id="abft_rss_url" name="abft_rss_url" value="<?php echo $rss_url; ?>" style="width:350px;" required>
                        <button type="button" id="abft_check_rss_btn" class="button">Cek RSS</button>
                        <span id="abft_rss_status"></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="abft_fetch_limit">Limit Post per Fetch</label></th>
                    <td>
                        <input type="number" id="abft_fetch_limit" name="abft_fetch_limit" value="<?php echo $fetch_limit; ?>" min="1" style="width:80px;"> 
                        <span class="description">Berapa banyak post diambil setiap fetch.</span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Interval Cron</th>
                    <td>
                        <input type="number" min="1" id="abft_cron_interval_num" name="abft_cron_interval_num" value="<?php echo $interval_num; ?>" style="width:60px;" required>
                        <select id="abft_cron_interval_unit" name="abft_cron_interval_unit">
                            <?php foreach ($units as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php selected($interval_unit, $key); ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="description">Jadwalkan fetch setiap [angka] [satuan].</span>
                    </td>
                </tr>
            </table>
            <?php submit_button('Simpan'); ?>
        </form>
    </div>
    <script>
    (function($){
        $('#abft_check_rss_btn').on('click', function(){
            var btn = $(this);
            var status = $('#abft_rss_status');
            status.text('Mengecek...');
            $.post(ajaxurl, {
                action: 'abft_check_rss',
                rss_url: $('#abft_rss_url').val()
            }, function(res){
                if(res.valid){
                    status.html('<span style="color:green">RSS valid ✔</span>');
                } else {
                    status.html('<span style="color:red">RSS tidak valid ✖</span>');
                }
            });
        });
    })(jQuery);
    </script>
    <?php
}
