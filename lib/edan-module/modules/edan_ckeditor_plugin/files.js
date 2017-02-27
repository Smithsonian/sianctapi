jQuery(window).load(function(){
  jQuery(window).focus();
});

jQuery(document).ready(function(){

  jQuery('#edit-main-image-upload-button').addClass('btn btn-default');
  jQuery('#edit-main-image-remove-button').addClass('btn btn-default clearfix');

  jQuery('a.insert-file').click(function(ev) {

    this_element = jQuery(this);
    ev.preventDefault();

    var uri = jQuery(this).attr('href');
    var local_url = jQuery(this_element).hasClass('local-file');

    // Track our response through a return path
    var returnPath = false;

    /* There's probably a better way... */
    try {
      var query = window.location + '';
      if (query.indexOf('localPicker=1') > -1) {
        returnPath = 'local';
      } else if (window.opener.ckeditor_edanSetFile) {
        returnPath = 'ckeditor';
      } else {
        returnPath = false;
      }
    } catch (err) {
      returnPath = false;
    }

    //console.log(window.opener.CKEDITOR.instances['edit-content-value']);
    try {
      /*var img = window.opener.CKEDITOR.instances['edit-content-value'].document.createElement('img');
      img.setAttribute('src', uri);
      img.setAttribute('alt', '');
      window.opener.CKEDITOR.instances['edit-content-value'].insertElement(img);
      */

      //var width = jQuery('input#width').val();
      //if (!width) {
        width = '1600';
      //}

      // TODO: How to deal with max_w vs. max_h vs. max in URIs with IDS?
      if(!local_url) {
        if (uri.indexOf('ids.si.edu') < 0) {
          uri = 'http://ids.si.edu/ids/deliveryService?max=' + width + '&id=' + uri;
        } else {
          uri = uri.replace('deliveryService?id=', 'deliveryService?max=' + width + '&id=');
          uri = uri.replace('dynamic?id=', 'deliveryService?max=' + width + '&id=');
        }
      }

      // Load into local var
      window.opener.__edanSelectedFile = {
        'url': uri,
        'name': uri
      };

      if (returnPath == 'ckeditor') {
        window.opener.ckeditor_edanSetFile(window.opener.__edanSelectedFile, window);
      }
//      else if (returnPath == 'local') {
//        window.opener.edan_ckeditor_plugin_edanSetFile(window.opener.__edanSelectedFile, window);
//      }
      //window.close();
    } catch (err) {
      console.log(err);
    }

  });
});
