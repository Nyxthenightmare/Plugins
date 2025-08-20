jQuery(document).on('click', '.cek-feed-url', function() {
    var $btn = jQuery(this);
    var $input = $btn.siblings('.feed-url-input');
    var url = $input.val();
    var $status = $btn.siblings('.feed-url-status');
    $status.text('Cek...');
    if (!url) { $status.text('URL kosong'); $input.css('border-color', ''); return; }
    jQuery.post(abft_ajax.ajax_url, {
        action: 'abft_validate_rss_url',
        url: url,
        nonce: abft_ajax.nonce
    }, function(resp) {
        if(resp.success) {
            $input.css('border-color', '#46b450');
            $status.text('Valid').css('color', 'green');
        } else {
            $input.css('border-color', '#dc3232');
            $status.text('Tidak valid').css('color', 'red');
        }
    });
});