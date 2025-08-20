jQuery(document).ready(function($){
    $('.feed-url-input').on('blur', function(){
        var $row = $(this).closest('tr');
        var url = $(this).val();
        var $status = $row.find('.rss-check-status');
        if (!url) {
            $status.text('');
            return;
        }
        $status.text('Memeriksa...');
        $.post(ajaxurl, { action: 'abft_check_rss', rss_url: url }, function(res){
            if (res.valid) {
                $status.html('<span style="color:green;">✔️</span>');
            } else {
                $status.html('<span style="color:red;" title="'+res.message+'">❌</span>');
            }
        });
    });
});
