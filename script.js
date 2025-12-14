jQuery(document).ready(function($) {
    var commentForm = $('#commentform');
    var uploadField = $('#lca-upload');
    var preview = $('#lca-preview');
    var feedback = $('#lca-feedback');
    var MAX_SIZE = 2 * 1024 * 1024; // 2MB
    var allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

    // Ensure the form can carry files even if inline script fails.
    commentForm.attr('enctype', 'multipart/form-data');

    function showFeedback(message, isError) {
        feedback.text(message);
        feedback.toggleClass('is-error', !!isError);
    }

    function clearPreview() {
        preview.empty();
    }

    uploadField.on('change', function(e) {
        var file = e.target.files[0];

        if (!file) {
            clearPreview();
            showFeedback('', false);
            return;
        }

        if (allowedTypes.indexOf(file.type) === -1) {
            showFeedback('Please choose a JPG, PNG, or GIF image.', true);
            uploadField.val('');
            clearPreview();
            return;
        }

        if (file.size > MAX_SIZE) {
            showFeedback('Your photo is too large. Please stay under 2MB.', true);
            uploadField.val('');
            clearPreview();
            return;
        }

        var reader = new FileReader();
        reader.onload = function(event) {
            var img = $('<img>', {
                src: event.target.result,
                alt: 'Selected avatar preview'
            });
            preview.html(img);
            showFeedback(file.name + ' (' + Math.round(file.size / 1024) + ' KB)', false);
        };
        reader.readAsDataURL(file);
    });
});
