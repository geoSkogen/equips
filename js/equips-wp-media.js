console.log('equips-wp-media');

jQuery(document).ready(function($){

  var mediaUploader;

  $('.equips-img-select').click(function(e) {
    clickedButton = jQuery(this);
    e.preventDefault();
    // If the uploader object has already been created, reopen the dialog
      if (mediaUploader) {
      mediaUploader.open();
      return;
    }
    // Extend the wp.media object
    mediaUploader = wp.media.frames.file_frame = wp.media({
      title: 'Choose Image',
      button: {
      text: 'Choose Image'
    }, multiple: false });

    // When a file is selected, grab the URL and set it as the text field's value
    mediaUploader.on('select', function() {
      attachment = mediaUploader.state().get('selection').first().toJSON();
      //$('#city-coupon-business-logo').val(attachment.url);
      var place = $(clickedButton).siblings('input');
      place.val(attachment.url);
      delete clickedButton;
    });
    // Open the uploader dialog
    mediaUploader.open();

  });

});
