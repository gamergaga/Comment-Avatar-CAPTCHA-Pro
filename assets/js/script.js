jQuery(document).ready(function ($) {
    'use strict';

    const $dropzone = $('.cacp-dropzone');
    const $fileInput = $('#cacp-file-input');
    const $preview = $('.cacp-preview');
    const $img = $('#cacp-preview-img');

    // Ensure form is ready for files
    $('#commentform').attr('enctype', 'multipart/form-data');

    // Add Icon
    const icon = '<svg class="cacp-icon" viewBox="0 0 24 24"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/></svg>';
    $dropzone.prepend(icon);

    // CLICK FIX: Trigger the hidden file input when clicking the dropzone
    $dropzone.on('click', function(e) {
        e.preventDefault();
        $fileInput.trigger('click');
    });

    // File selection logic
    $fileInput.on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                $img.attr('src', e.target.result);
                $dropzone.hide();
                $preview.show().css('display', 'flex');
            };
            reader.readAsDataURL(file);
        }
    });

    // Remove selection
    $('.cacp-remove').on('click', function(e) {
        e.preventDefault();
        $fileInput.val('');
        $preview.hide();
        $dropzone.show();
    });
});
