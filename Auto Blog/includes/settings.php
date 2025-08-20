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

// Render halaman pengaturan
function abft_settings_page() {
    if (isset($_POST['abft_rss_url'])) {
        check_admin_referer('abft_save_settings');
        update_option('abft_rss_url', esc_url_raw($_POST['abft_rss_url']));
        echo '<div class="updated"><p>RSS feed diupdate!</p></div>';
    }
    $rss_url = esc_url(get_option('abft_rss_url', 'https://detik.com/rss'));
    ?>
    <div class="wrap">
        <h1>Autoblog Fulltext Settings</h1>
        <form method="post">
            <?php wp_nonce_field('abft_save_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="abft_rss_url">RSS Feed URL</label></th>
                    <td><input type="url" id="abft_rss_url" name="abft_rss_url" value="<?php echo $rss_url; ?>" style="width:350px;" required></td>
                </tr>
            </table>
            <?php submit_button('Simpan'); ?>
        </form>
    </div>
    <?php
}