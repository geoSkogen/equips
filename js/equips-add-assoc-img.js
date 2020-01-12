console.log('equips-add-assoc-img.js');

var divs = document.querySelectorAll('.eq_assoc_images');
var buts = document.querySelectorAll('.eq-add-assoc-button');
for (let i = 0; i < buts.length; i++) {
  assignClick(buts[i],divs[i],i+1);

  function assignClick(button, div, index) {
    var elms = {};
    button.addEventListener('click', function () {
      elms = eq_do_assoc_input(index);
      div.appendChild(elms.txt);
      div.appendChild(elms.file);
    });

  }
}

function eq_do_assoc_input(settings_field_index) {
  var mediaUploader;
  var result = { txt: {}, file: {} };
  var img_assoc_index = assoc_count(settings_field_index, true);
  var img_assoc_file_field = "equips_images[img_assoc_file_" +
    settings_field_index + "_" + img_assoc_index + "]";
  var img_assoc_path_field = "equips_images[img_assoc_path_" +
    settings_field_index + "_" + img_assoc_index +  "]";
  var txt_in = document.createElement('input');
  var file_in = document.createElement('input');
  txt_in.setAttribute('type','text');
  file_in.setAttribute('type','file');
  txt_in.setAttribute('name',img_assoc_path_field);
  file_in.setAttribute('name',img_assoc_file_field);
  txt_in.className = 'equips-img-select-path';
  file_in.className = 'equips-img-select';
  file_in.addEventListener('click', function (e) {
    clickedButton = this;
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
      var place = clickedButton.parentElement.querySelector('.equips-img-select-path');
      place.value = attachment.url;
      delete clickedButton;
    });
    // Open the uploader dialog
    mediaUploader.open();
  });
  result.txt = txt_in;
  result.file = file_in;
  return result;
}

function assoc_count(index, returnNext) {
  var assoc = document.querySelector('#img_assoc_id_' + index);
  var imgs = assoc.querySelectorAll('.eq_assoc');
  var int = (imgs.length) ? imgs.length : 0;
  return (returnNext)? int+1 : int;
}
