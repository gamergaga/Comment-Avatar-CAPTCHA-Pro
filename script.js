jQuery(document).ready(function($) {
    // 1. Force the form to handle file data
    var commentForm = $('#commentform');
    commentForm.attr('enctype', 'multipart/form-data');

    // 2. Simple Image Preview
    $('#lca-upload').on('change', function(e) {
        var file = e.target.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#lca-preview').html(
                    '<img src="' + e.target.result + '" alt="Preview" />'
                );
            }
            reader.readAsDataURL(file);
        }
    });
});
