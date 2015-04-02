jQuery(document).ready(function ($) {

    $(document).on('click', '.gallery_meta_upload', function() {

        var send_attachment_bkp = wp.media.editor.send.attachment;
        var obj = $(this);

        wp.media.editor.send.attachment = function(props, attachment) {

            obj.closest('tr')
                .find('td:first-child')
                    .find('img').attr('src', attachment.url).show()
                    .end()
                .next('td')
                    .find('.gallery_meta_url').val(attachment.id);


            wp.media.editor.send.attachment = send_attachment_bkp;
        }

        wp.media.editor.open();

        return false;
    });



    $(document).on('click', '.add_new_image', function (e) {
        e.preventDefault();

        var large = '<tr><td><img src="" class="gallery_meta_img" style="display: none; padding: 5px; border: none;" width="200"></td><td><input type="text" name="gallery_meta_url[]" class="gallery_meta_url wide" value="" style="display: none;"><label>Enter Image Title</label><input type="text" name="gallery_meta_caption[]" class="gallery_meta_caption wide" value=""></td><td><input type="button" name="gallery_meta_upload" class="gallery_meta_upload wide" value="Upload"><input type="button" name="gallery_meta_remove" class="gallery_meta_remove" value="Remove" style="display: none;"></td></tr>'
        $('.gallery_meta_table').append(large);
    });

    $(document).on('click', '.gallery_meta_remove', function () {
        $(this).parent().parent().fadeTo(400, 0, function () {
            $(this).remove();
        });
        return false
    });
    
    $('.dg-color-field').wpColorPicker();
});