jQuery(function ($) {
    'use strict';

    const $editBtn  = $('.cacp-edit-avatar-btn');
    const $dropdown = $('.cacp-avatar-dropdown');
    const $file     = $('#cacp-file-input');
    const $action   = $('#cacp_avatar_action');

    $editBtn.on('click', function (e) {
        e.preventDefault();
        $dropdown.toggle();
    });

    $('.cacp-avatar-change').on('click', function () {
        $action.val('change');
        $dropdown.hide();
        $file.trigger('click');
    });

    $('.cacp-avatar-remove').on('click', function () {
        $action.val('remove');
        $dropdown.hide();
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.cacp-edit-avatar-btn, .cacp-avatar-dropdown').length) {
            $dropdown.hide();
        }
    });
});
