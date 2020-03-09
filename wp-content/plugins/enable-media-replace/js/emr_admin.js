jQuery(document).ready(function($)
{
  // interface for emr.
  var emrIf = new function ()
  {
    var source_type;
    var source_is_image;
    var target_type;
    var target_is_image;

    var is_debug = false;

    this.init = function()
    {
      if ( emr_options.is_debug)
      {
        this.is_debug = true;
        this.debug('EMR Debug is active');
      }

      $('input[name="timestamp_replace"]').on('change', $.proxy(this.checkCustomDate, this));
      $('input[name="userfile"]').on('change', $.proxy(this.handleImage, this));
      this.checkCustomDate();
      this.loadDatePicker();

      var source = $('.image_placeholder').first();
      if (typeof( $(source).data('filetype') ) !== 'undefined')
      {
        source_type = $(source).data('filetype').trim();
        this.debug('detected type - ' + source_type);
      }
      if (source.hasClass('is_image'))
      {
          source_is_image = true;
      }

      this.updateTextLayer(source, false);

    },
    this.loadDatePicker = function()
    {
      $('#emr_datepicker').datepicker({
        dateFormat: emr_options.dateFormat,
        onClose: function() {
              var date = $(this).datepicker( 'getDate' );
              if (date) {
                  var formattedDate = (date.getFullYear()) + "-" +
                            (date.getMonth()+1) + "-" +
                            date.getDate();
              $('input[name="custom_date_formatted"]').val(formattedDate);
              //$('input[name="custom_date"]').val($.datepicker.parseDate( emr_options.dateFormat, date));
              }
        },
      });
    },
    this.checkCustomDate = function()
    {
      if ($('input[name="timestamp_replace"]:checked').val() == 3)
        this.showCustomDate();
      else
        this.hideCustomDate();
    },
    this.showCustomDate = function()
    {
        $('.custom_date').css('visibility', 'visible').fadeTo(100, 1);
    },
    this.hideCustomDate = function()
    {
      $('.custom_date').fadeTo(100,0,
          function ()
          {
            $('.custom_date').css('visibility', 'hidden');
          });
    }
    this.handleImage = function(e)
    {
        this.toggleErrors(false);
        var target = e.target;
        var file = target.files[0];

        if (! target.files || target.files.length <= 0)  // FileAPI appears to be not present, handle files on backend.
        {
          if ($('input[name="userfile"]').val().length > 0)
            this.checkSubmit();
          console.log('FileAPI not detected');
          return;
        }

        var status = this.checkUpload(file);
        this.debug('check upload status ' + status);

        if (status)
        {
          this.updatePreview(file);
        }
        else {
          this.updatePreview(null);
        }
        this.checkSubmit();
    },
    this.updatePreview = function(file)
    {
      var preview = $('.image_placeholder').last();

      $(preview).find('img').remove();
      $(preview).removeClass('is_image not_image is_document');

      if (file !== null) /// file is null when empty, or error
      {
        target_is_image = (file.type.indexOf('image') >= 0) ? true : false;
        target_type = file.type.trim();
      }
      // If image, load thumbnail and get dimensions.
      if (file && target_is_image)
      {
        var img = new Image();
        img.src = window.URL.createObjectURL(file);
        self = this;

        img.setAttribute('style', 'max-width:100%; max-height: 100%;');
        img.addEventListener("load", function () {
            //  $(preview).find('.textlayer').text(img.naturalWidth + ' x ' + img.naturalHeight );
              self.updateTextLayer(preview, img.naturalWidth + ' x ' + img.naturalHeight);
        });

        $(preview).prepend(img);
        $(preview).addClass('is_image');
      }
      else if(file === null)
      {
        $(preview).addClass('not_image');
        $(preview).find('.dashicons').removeClass().addClass('dashicons dashicons-no');
        //$(preview).find('.textlayer').text('');
        this.updateTextLayer(preview, '');
        this.debug('File is null');
      }
      else { // not an image
        $(preview).addClass('not_image is_document');
        $(preview).find('.dashicons').removeClass().addClass('dashicons dashicons-media-document');
        //$(preview).find('.textlayer').text(file.name);
        this.updateTextLayer(preview, file.name);
        this.debug('Not image, media document');
      }

      if (target_type != source_type)
      {
        this.debug(target_type + ' not ' + source_type);
        this.warningFileType();
      }
    },
    // replace the text, check if text is there ( or hide ), and fix the layout.
    this.updateTextLayer = function (preview, newtext)
    {
        textlayer = $(preview).find('.textlayer');
        textlayer.css('opacity', '0');
        if (newtext !== false)
          textlayer.text(newtext);

        if (textlayer.text() !== '')
        {
          textlayer.css('opacity', '0.7');
      //    textlayer.css('margin-left', '-' + (textlayer.width() / 2 ) + 'px');
        }

    },
    this.checkSubmit = function()
    {
       var check = ($('input[name="userfile"]').val().length > 0) ? true : false;

        if (check)
        {
          $('input[type="submit"]').prop('disabled', false);
        }
        else {
          $('input[type="submit"]').prop('disabled', true);
        }
    },
    this.toggleErrors = function(toggle)
    {
      $('.form-error').fadeOut();
      $('.form-warning').fadeOut();
    }
    this.checkUpload = function(fileItem)
    {
      var maxsize = emr_options.maxfilesize;

      if ($('input[name="userfile"]').val().length <= 0)
      {
        console.info('[EMR] - Upload file value not set in form. Pick a file');
        $('input[name="userfile"]').val('');
        return false;
      }

      if (fileItem.size > maxsize)
      {
          console.info('[EMR] - File too big for uploading - exceeds upload limits');
          this.errorFileSize(fileItem);
          $('input[name="userfile"]').val('');
          return false;
      }
      return true;
    },
    this.errorFileSize = function(fileItem)
    {
      $('.form-error.filesize').find('.fn').text(fileItem.name);
      $('.form-error.filesize').fadeIn();
    }
    this.warningFileType = function(fileItem)
    {
      $('.form-warning.filetype').fadeIn();
    }
    this.debug = function(message)
    {
      console.debug(message);
    }
  } // emrIf

  window.enableMediaReplace = emrIf;
  window.enableMediaReplace.init();
});
