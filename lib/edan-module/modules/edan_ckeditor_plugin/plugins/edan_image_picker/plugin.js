/**
 * @file Plugin for inserting files from EDAN info any content type, using a custom dialog in CKEditor
 */
( function() {

  CKEDITOR.plugins.add( 'edan_image_picker',
  {
    init: function( editor )
    {

      //adding button
      editor.ui.addButton( 'edan_image_picker',
      {
        label: 'Insert image from EDAN',
        command: 'showEDANDialog',
        icon: this.path + 'icons/edan_image_picker.png'
      });

      CKEDITOR.dialog.add( 'showEDANDialog', function ( editor )
      {
        return {
          title : 'Select Image from EDAN',
          minWidth : 400,
          minHeight : 200,

          contents :
            [
              {
                id : 'tab1',
                label : 'Image Properties',
                elements :
                  [
                    {
                      type : 'button',
                      id : 'edan_path_picker_button',
                      label : 'Choose EDAN Image',
                      onClick : function( api ) {
                        // this = CKEDITOR.ui.dialog.button
                        var width = editor.config.filebrowserWindowWidth || '80%';
                        var height = editor.config.filebrowserWindowHeight || '70%';
                        editor.popup(Drupal.settings.basePath + 'index.php?q=/edan/files/picker\x26app=ckeditor|sendto@ckeditor_edanSetFile|&CKEditorFuncNum=' + editor.filebrowserFnEDAN, width, height);
                      }
                    },
                    //{
                    //  type : 'text',
                    //  id : 'edan_path_old',
                    //  label : 'EDAN Image Path OLD'
                    //},
                    {
                      type : 'html',
                      id : 'edan_path',
                      label : 'EDAN Image Path',
                      html : '<div id="edan_path_value"></div>'
                    },
                    {
                      type : 'text',
                      id : 'image_width',
                      label : 'Image Width'
                    },
                    {
                      type : 'text',
                      id : 'image_height',
                      label : 'Image Height'
                    },
                    {
                      type : 'text',
                      id : 'image_alt',
                      label : 'Image Alt Tag'
                    }
                  ]
              }
            ],

          onOk : function()
          {
            // "this" is now a CKEDITOR.dialog object.
            var dialog = this;

            // document = CKEDITOR.dom.document
            var document = this.getElement().getDocument();

            var element = document.getById( 'edan_path_value' );
            var edan_path = '';
            if ( element )
              edan_path = element.getHtml();

            if(edan_path.length > 0) {

              var image_width = dialog.getValueOf( 'tab1', 'image_width' );
              var image_height = dialog.getValueOf( 'tab1', 'image_height' );
              var image_alt = dialog.getValueOf( 'tab1', 'image_alt' );

              var this_image = '<img src="' + edan_path + '" alt="' + image_alt + '"';

              var style = '';
              if(image_width.length > 0)
                style += ' width:' + image_width + 'px; ';

              if(image_height.length > 0)
                style += ' height:' + image_height + 'px; ';

              if(style.length > 0)
                style = 'style="' + style + '"';

              this_image += style + ' />';

              if (element)
                element.innerHTML = '';

              editor.insertHtml( this_image );

            }

          }

        };


      } );
      // CKEditor AddDialog definition

      //opening image picker window
      editor.addCommand( 'showEDANDialog', new CKEDITOR.dialogCommand( 'showEDANDialog' ) );

      //console.log( 'Editor "' + editor.name + '" is being used to try to find the dialog textbox.' );

      //add editor function
      editor.filebrowserFnEDAN = CKEDITOR.tools.addFunction( edanSetFile, editor ); // edanSetFile, editor

      //function which receives selected EDAN image
      window.ckeditor_edanSetFile = function (file, win) {
        var cfunc = win.location.href.split('&');
        for (var x in cfunc) {
          if (cfunc[x].match(/^CKEditorFuncNum=\d+$/)) {
            cfunc = cfunc[x].split('=');
            break;
          }
        }
        CKEDITOR.tools.callFunction(cfunc[1], file);
        win.close();
      };


      /*
        editor.addCommand( 'showEDANDialog', {
          exec : function (editor) {
            editor.insertHtml( 'This text is inserted when clicking on our new button from the CKEditor toolbar' );
            //var width = editor.config.filebrowserWindowWidth || '80%',
            //height = editor.config.filebrowserWindowHeight || '70%';
            //var uri_path = 'edan/images/picker';
            //@todo un-comment: editor.popup(Drupal.settings.basePath + 'index.php?q=' + uri_path + '\x26app=ckeditor|sendto@ckeditor_edanSetFile|&CKEditorFuncNum=' + editor._.filebrowserFnEDAN, width, height);
          }
        });
  */

    }
  } );

  /*
  function _edan_image() { // void
    var myField = jQuery('#edit-image');
    var myPreview = jQuery('#edit-image-preview');
    var imgSrc = jQuery(myField).val();

    // Hide the field
    //jQuery(myField).hide();

    // If the field is empty don't do anything (or clear the current image out)
    if (null == imgSrc || imgSrc == '' || imgSrc == 0) {
      jQuery(myPreview).html('');
      return;
    }

    // Resize the image
    imgSrc = imgSrc.replace('deliveryService?id=', 'deliveryService?max=200&id=');
    imgSrc = imgSrc.replace('dynamic?id=', 'deliveryService?max=200&id=');

    jQuery(myPreview).html('<img src="' + imgSrc + '" alt="" style="width: 50px;" onclick="_edan_image_toggle(this);" />');
    return;
  }

  function _edan_image_toggle(img) {
    var w = jQuery(img).width();
    jQuery(img).width( (w == 200) ? 50 : 200 );
  }

  function _edan_launchpicker(fn) {
    //ev.preventDefault();

    var uriPath = 'edan/files/picker';
    var fullUri = Drupal.settings.basePath + 'index.php?q=' + uriPath + '\x26app=ckeditor|sendto@ckeditor_edanSetFile|&CKEditorFuncNum=' + fn;
    //  + '&localPicker=1';
    //editor.popup(Drupal.settings.basePath + 'index.php?q=' + uri_path + '\x26app=ckeditor|sendto@ckeditor_edanSetFile|&CKEditorFuncNum=' + editor._.filebrowserFnEDAN, width, height);

    var winW = jQuery(window).width();
    var winH = jQuery(window).height();
    var popW = Math.round( (winW * .8) );
    var popH = Math.round( (winH * .7) );

    var newWin = window.open(fullUri, 'edanPicker', 'height=' + popH + ',left=10,top=10,width=' + popW + ',location=1,menubar=0,resizable=1,scrollbars=1,status=1,titlebar=1,toolbar=0');

  }
*/

  function edanSetFile(file) {

//    this.insertHtml('<img src="' + file.url + '" alt="' + file.name + '" />');
    var element = document.getElementById("edan_path_value");
    if (element) {
      element.innerHTML = file.url;
    }

  }

} )();
