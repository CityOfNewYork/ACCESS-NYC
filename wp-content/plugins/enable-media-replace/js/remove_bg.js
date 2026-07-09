jQuery(document).ready(function ($) {

	// Dismiss handler for the in-form notice.
	$(document).on('click', '#emr-bg-notice .notice-dismiss', function () {
		$('#emr-bg-notice').fadeOut(150);
	});

	// Init
	$('input[type=radio][name=background_type]').on('change', backgroundInputs);
	$('#bg_transparency').on('input', transparancyOptions);

	backgroundInputs(); // init initial
	transparancyOptions();

 $('.replace_type.wrapper input').on('change', function () {
	 	 $('#replace_image_button').prop('disabled', 'disabled');
 });

  // Remove bg click
  $('#remove_background_button').on('click', () => {
    const method = 'POST'
    const url = emrObject.ajax_url;
   // const image = emrObject.base_url;
    const nonce = emrObject.nonce;
		const attachment_id = $('input[name="ID"]').val();
    const action = 'emr_remove_background';
    const bgType = $('input[type=radio][name="background_type"]:checked').val();
    const cLvl = $('input[type=radio][name="compression_level"]:checked').val();
    let background = {
      type: "transparent"
    }

    background = {
      type: bgType,
      color: $('#bg_color').val(),
      transparency: $('#bg_transparency').val()
    }

    $.ajax({
      method,
      url,
      data: {
        action,
        nonce,
        attachment_id,
        background,
        compression_level : cLvl
      },
      beforeSend: function () {
        $('html, body').animate({
          scrollTop: $(".emr_upload_form").offset().top
        }, 1000);
        $('input[type=radio][name=background_type]').attr('disabled', 'disabled');
        $('input[type=radio][name=compression_level]').attr('disabled', 'disabled');
        $('#remove_background_button').attr('disabled', 'disabled');
				$('h1.response').remove();
				$('#emr-bg-notice').hide().find('p').empty();
        $('#overlay').css('visibility', 'visible');
				var preview = $('.image_placeholder').last();
				preview.find('img').remove();
       // $('#preview-area').hide();
      },
      success: function (response) {
				var preview = $('.image_placeholder').last();

        if (response.success) {


					$('#overlay').css('visibility', 'hidden');
					preview.find('img').remove();
		      preview.removeClass('is_image not_image is_document');

          $('#replace_image_button').prop('disabled', false);

				 var img = new Image();
         img.src = response.image;
				 img.setAttribute('style', 'height: inherit;');

				 preview.prepend(img);
				// preview.removeClass('not_image');
				 preview.addClass('is_image');

				 $('input[name="key"]').val(response.key);
				 $('input[type=radio][name=background_type]').attr('disabled', false);
         $('input[type=radio][name=compression_level]').attr('disabled', false);
         $('#remove_background_button').attr('disabled', false);

				 var badBg = document.getElementById('bad-background-link');
				 var href = badBg.dataset.link;
				 href = href.replace('{url}', response.url);
				 href = href.replace('{settings}', response.settings);

				 badBg.setAttribute('href', href);

				 badBg.style.visibility = 'visible';
         /* $('#removed_image').html(`
						<div class="img-comp-container">
  						<div class="img-comp-img">
								<img src="${image}"  width="${width}" height="${height}" />
  						</div>

						</div>
					`); */
     //     initComparisons();
        }else{

          $('#overlay').css('visibility', 'hidden');
          if (response.plan_expired || response.quota_exceeded) {
            // Leave the preview empty; surface the message as a dismissible notice instead.
            var $p = $('#emr-bg-notice').find('p').empty();
            $('<strong></strong>').text('Enable Media Replace: ').appendTo($p);

            var msg = response.message;
            var linkText = 'shortpixel.com/pricing';
            var idx = msg.indexOf(linkText);
            if (idx !== -1) {
              $p.append(document.createTextNode(msg.substring(0, idx)));
              $('<a></a>')
                .attr({ href: 'https://shortpixel.com/pricing', target: '_blank', rel: 'noopener' })
                .text(linkText)
                .appendTo($p);
              $p.append(document.createTextNode(msg.substring(idx + linkText.length)));
            } else {
              $p.append(document.createTextNode(msg));
            }

            $('#emr-bg-notice').show();
            $('html, body').animate({ scrollTop: 0 }, 400);
          } else {
            preview.prepend(`<h1 class='response'>${response.message}</h1>`);
          }
          $('#remove_background_button').attr('disabled', false)
          $('input[type=radio][name=background_type]').attr('disabled', false)
          $('input[type=radio][name=compression_level]').attr('disabled', false)
         //$('#preview-area').show();
        }
      }
    })
  });

  function backgroundInputs () {
    const bgInputs = $('#solid_selecter');
		var input = $('input[type=radio][name=background_type]:checked');
    if (input.val() === 'solid') {
      bgInputs.show();
    } else {
      bgInputs.hide();
    }

  };

  $('#bg_display_picker').on('input', function () {
    $('#color_range').html($(this).val());
    $('#bg_color').val($(this).val());
  });

  function transparancyOptions() {
    $('#transparency_range').html($('#bg_transparency').val());
  };

});