/*! Bulk Delete - v6.0.2 %>
 * https://bulkwp.com
 * Copyright (c) 2019; * Licensed GPLv2+ */
/*global jQuery, document*/
jQuery( document ).ready( function () {
	jQuery( 'input[name="smbd_comment_meta_use_value"]' ).change( function () {
		if ( 'true' === jQuery( this ).val() ) {
			jQuery( '#smbd_comment_meta_filters' ).show();
		} else {
			jQuery( '#smbd_comment_meta_filters' ).hide();
		}
	} );
} );

/*global BulkWP, postboxes, pagenow */
jQuery(document).ready(function () {
	jQuery( '.user_restrict_to_no_posts_filter' ).change( function() {
		var $this = jQuery(this),
			filterEnabled = $this.is( ':checked' ),
		    $filterItems = $this.parents( 'table' ).children().find( '.user_restrict_to_no_posts_filter_items' );

		if ( filterEnabled ) {
			$filterItems.removeClass( 'visually-hidden' );
		} else {
			$filterItems.addClass( 'visually-hidden' );
		}
	} );

	/**
	 * Enable Postbox handling
	 */
	postboxes.add_postbox_toggles(pagenow);

	/**
	 * Change submit button text if scheduling deletion.
	 */
	jQuery( "input:radio.schedule-deletion" ).change( function () {
		var submitButton = jQuery( this ).parents( 'fieldset' ).next().find( 'button[name="bd_action"]' );

		if ( "true" === jQuery( this ).val() ) {
			submitButton.html( 'Schedule Bulk Delete &raquo;' );
		} else {
			submitButton.html( 'Bulk Delete &raquo;' );
		}
	} );

	/**
	 * Toggle the date restrict fields
	 */
	function toggle_date_restrict(el) {
		if (jQuery("#smbd" + el + "_restrict").is(":checked")) {
			jQuery("#smbd" + el + "_op").removeAttr('disabled');
			jQuery("#smbd" + el + "_days").removeAttr('disabled');
		} else {
			jQuery("#smbd" + el + "_op").attr('disabled', 'true');
			jQuery("#smbd" + el + "_days").attr('disabled', 'true');
		}
	}

	/**
	 * Toggle limit restrict fields
	 */
	function toggle_limit_restrict(el) {
		if (jQuery("#smbd" + el + "_limit").is(":checked")) {
			jQuery("#smbd" + el + "_limit_to").removeAttr('disabled');
		} else {
			jQuery("#smbd" + el + "_limit_to").attr('disabled', 'true');
		}
	}

	/**
	 * Toggle user login restrict fields
	 */
	function toggle_login_restrict(el) {
		if (jQuery("#smbd" + el + "_login_restrict").is(":checked")) {
			jQuery("#smbd" + el + "_login_days").removeAttr('disabled');
		} else {
			jQuery("#smbd" + el + "_login_days").attr('disabled', 'true');
		}
	}

	/**
	 * Toggle user registered restrict fields
	 */
	function toggle_registered_restrict(el) {
		if (jQuery("#smbd" + el + "_registered_restrict").is(":checked")) {
			jQuery("#smbd" + el + "_registered_days").removeAttr('disabled');
			jQuery("#smbd" + el + "_op").removeAttr('disabled');
		} else {
			jQuery("#smbd" + el + "_registered_days").attr('disabled', 'true');
			jQuery("#smbd" + el + "_op").attr('disabled', 'true');
		}
	}

	/**
	 * Toggle delete attachments
	 */
	function toggle_delete_attachments(el) {
		if ( "true" === jQuery('input[name="smbd' + el + '_force_delete"]:checked').val()) {
			jQuery("#smbd" + el + "_attachment").removeAttr('disabled');
		} else {
			jQuery("#smbd" + el + "_attachment").attr('disabled', 'true');
		}
	}

    /**
     * Toggle Post type dropdown.
     */
    function toggle_post_type_dropdown( el ) {
        // TODO: Check why the element is not toggling even when display:none is added by JS.
        if ( jQuery( "#smbd" + el + "_no_posts" ).is( ":checked" ) ) {
            jQuery( "tr#smbd" + el + "-post-type-dropdown" ).show();
        } else {
            jQuery( "tr#smbd" + el + "-post-type-dropdown" ).hide();
        }
    }

	// hide all terms
	function hideAllTerms() {
		jQuery('table.terms').hide();
		jQuery('input.terms').attr('checked', false);
	}
	// call it for the first time
	hideAllTerms();

	// taxonomy click handling
	jQuery('.custom-tax').change(function () {
		var $this = jQuery(this),
		$tax = $this.val(),
		$terms = jQuery('table.terms_' + $tax);

		if ($this.is(':checked')) {
			hideAllTerms();
			$terms.show('slow');
		}
	});

	// date time picker
	jQuery.each(BulkWP.dt_iterators, function (index, value) {
		// invoke the date time picker
		jQuery('#smbd' + value + '_cron_start').datetimepicker({
			dateFormat: 'yy-mm-dd',
			timeFormat: 'HH:mm:ss'
		});

		jQuery('#smbd' + value + '_restrict').change(function () {
			toggle_date_restrict(value);
		});

		jQuery('#smbd' + value + '_limit').change(function () {
			toggle_limit_restrict(value);
		});

		jQuery('#smbd' + value + '_login_restrict').change(function () {
			toggle_login_restrict(value);
		});

		jQuery('#smbd' + value + '_registered_restrict').change(function () {
			toggle_registered_restrict(value);
		});

		jQuery('input[name="smbd' + value + '_force_delete"]').change(function () {
			toggle_delete_attachments(value);
		});

		jQuery( '#smbd' + value + '_no_posts' ).change( function () {
			toggle_post_type_dropdown( value );
		});
	});

	jQuery.each( BulkWP.pro_iterators, function ( index, value) {
		jQuery('.bd-' + value.replace( '_', '-' ) + '-pro').hide();

		// `<tr>` displays the documentation link when the pro add-on is installed.
		jQuery('tr.bd-' + value.replace( '_', '-' ) + '-pro').show();

		jQuery('#smbd_' + value + '_cron_freq, #smbd_' + value + '_cron_start, #smbd_' + value + '_cron').removeAttr('disabled');
	} );

	/**
	 * If the given string is a function, then run it and return result, otherwise return the string.
	 *
	 * @param mayBeFunction
	 * @param that
	 *
	 * @returns string
	 */
	function resolveFunction( mayBeFunction, that ) {
		if ( jQuery.isFunction( mayBeFunction ) ) {
			return BulkWP[ mayBeFunction ]( that );
		}

		return mayBeFunction;
	}

	// Validate user action.
	jQuery('button[name="bd_action"]').click(function () {
		var currentButton = jQuery(this).val(),
			deletionScheduled = false,
			valid = false,
			messageKey = "deletePostsWarning",
			errorKey = "selectPostOption";

		if ( "true" === jQuery( this ).parent().prev().find( 'input:radio.schedule-deletion:checked' ).val() ) {
			deletionScheduled = true;
		}

		if (currentButton in BulkWP.validators) {
			valid = BulkWP[BulkWP.validators[currentButton]](this);
		} else {
			if (jQuery(this).parent().prev().children('table').find(":checkbox:checked[value!='true']").size() > 0) { // monstrous selector
				valid = true;
			}
		}

		if ( ! valid ) {
			if ( currentButton in BulkWP.error_msg ) {
				errorKey = BulkWP.error_msg[ currentButton ];
			}

			alert( BulkWP.msg[ errorKey ] );
			return false;
		}

		if ( currentButton in BulkWP.pre_delete_msg ) {
			messageKey = resolveFunction( BulkWP.pre_delete_msg[ currentButton ], this );
		}

		// pre_action_msg is deprecated. This will be eventually removed.
		if ( currentButton in BulkWP.pre_action_msg ) {
			messageKey = resolveFunction( BulkWP.pre_action_msg[ currentButton ], this );
		}

		if ( deletionScheduled ) {
			if ( currentButton in BulkWP.pre_schedule_msg ) {
				messageKey = resolveFunction( BulkWP.pre_schedule_msg[ currentButton ], this );
			}
		}

		return confirm( BulkWP.msg[ messageKey ] );
	});
});

/*global jQuery, BulkWP*/
jQuery(document).ready(function () {
	// Start Jetpack.
	BulkWP.jetpack();
});

BulkWP.jetpack = function() {
	jQuery('.bd-feedback-pro').hide();

	jQuery('#smbd_feedback_cron_freq, #smbd_feedback_cron_start, #smbd_feedback_cron').removeAttr('disabled');
	jQuery('#smbd_feedback_use_filter').removeAttr('disabled');

	// enable filters
	jQuery('input[name="smbd_feedback_use_filter"]').change(function() {
		if('true' === jQuery(this).val()) {
			// using filters
			jQuery('#jetpack-filters').show();
		} else {
			jQuery('#jetpack-filters').hide();
		}
	});

	// enable individual filters
	jQuery.each(['name', 'email', 'ip'], function (index, value) {
		jQuery('#smbd_feedback_author_' + value + '_filter').change(function() {
			if(jQuery(this).is(':checked')) {
				jQuery('#smbd_feedback_author_' + value + '_op').removeAttr('disabled');
				jQuery('#smbd_feedback_author_' + value + '_value').removeAttr('disabled');
			} else {
				jQuery('#smbd_feedback_author_' + value + '_op').attr('disabled', 'true');
				jQuery('#smbd_feedback_author_' + value + '_value').attr('disabled', 'true');
			}
		});
	});
};

/*global jQuery, BulkWP*/
BulkWP.validateCommentsCount = function(that) {
    return ("" !== jQuery(that).parent().prev().children().find(":input.comments_count_num").val());
};

/* global BulkWP */

/**
 * Validation for Post Type select2.
 */
BulkWP.validatePostTypeSelect2 = function(that) {
	if (null !== jQuery(that).parent().prev().children().find(".enhanced-post-types-with-status[multiple]").val()) {
		return true;
	} else {
		return false;
	}
};

/*global BulkWP */
jQuery( document ).ready( function () {
	var stickyAction = jQuery( "input[name='smbd_sticky_post_sticky_action']" ),
		deleteAction = stickyAction.parents( 'tr' ).next(),
		deleteActionRadio = deleteAction.find('[type="radio"]'),
		deleteAttachmentAction = deleteAction.next(),
		deleteAttachmentCheckBox = deleteAttachmentAction.find('[type="checkbox"]'),
		stickyPostCheckbox = jQuery( "input[name='smbd_sticky_post[]']" ),
		deleteButton = jQuery( "button[value='delete_posts_by_sticky_post']" );

	deleteButton.html( 'Remove Sticky &raquo;' );
	deleteAction.hide();
	deleteAttachmentAction.hide();

	stickyAction.change( function () {
		if ( 'delete' === stickyAction.filter( ':checked' ).val() ) {
			deleteButton.html( 'Bulk Delete &raquo;' );
			deleteAction.show();
			deleteAttachmentAction.show();
		} else {
			deleteButton.html( 'Remove Sticky &raquo;' );
			deleteAction.hide();
			deleteAttachmentAction.hide();
		}
	} );

	deleteActionRadio.change( function () {
		if( "true" === deleteActionRadio.filter(':checked').val() ){
			deleteAttachmentCheckBox.removeAttr('disabled');
		} else {
			deleteAttachmentCheckBox.attr('disabled', 'true');
		}
	});

	jQuery("input[value='all']").change( function () {
		if( jQuery(this).is(':checked') ) {
			uncheckAndDisableOtherCheckboxes();
		} else {
			enableCheckboxes();
		}
	});

	function uncheckAndDisableOtherCheckboxes() {
		stickyPostCheckbox.each( function() {
			if ( 'all' !== jQuery(this).val() ){
				jQuery(this).prop('checked', false);
				jQuery(this).attr('disabled', 'true');
			}
		});
	}

	function enableCheckboxes() {
		stickyPostCheckbox.each( function() {
			jQuery(this).removeAttr('disabled');
		});
	}
} );

/**
 * Validate that at least one post was selected.
 *
 * @returns {boolean} True if at least one post was selected, False otherwise.
 */
BulkWP.validateStickyPost = function () {
	return jQuery( "input[name='smbd_sticky_post[]']:checked" ).length > 0;
};

BulkWP.DeletePostsByStickyPostPreAction = function () {
	var stickyAction = jQuery( "input[name='smbd_sticky_post_sticky_action']:checked" ).val();

	if ( 'unsticky' === stickyAction ) {
		return 'unstickyPostsWarning';
	} else {
		return 'deletePostsWarning';
	}
};

/*global jQuery, BulkWP*/
BulkWP.validateUrl = function(that) {
    if (jQuery(that).parent().prev().children('table').find("textarea").val() !== '') {
        return true;
    } else {
        return false;
    }
};

jQuery( document ).ready( function () {
	jQuery( '.enhanced-taxonomy-list' ).select2( {
		width: '300px'
	} );
} );

/*global BulkWP */

/**
 * Validate that term name is not left blank.
 *
 * @returns {boolean} True if term name is not blank, False otherwise.
 */
BulkWP.validateTermName = function() {
	return (jQuery('input[name="smbd_terms_by_name_value"]').val() !== '');
};

/**
 * Validate that post count is not left blank.
 *
 * @returns {boolean} True if post count is not blank, False otherwise.
 */
BulkWP.validatePostCount = function() {
	return (jQuery('input[name="smbd_terms_by_post_count"]').val() !== '');
};

/*global jQuery, BulkWP*/
jQuery( document ).ready( function () {
	var reassignSelectBoxes = jQuery( ".reassign-user" ),
		contentDeleteRadios = jQuery( ".post-reassign" );

	reassignSelectBoxes.select2(
		{
			width: '200px'
		}
	);

	reassignSelectBoxes.each( function () {
		jQuery( this ).attr( 'disabled', 'true' );
	} );

	contentDeleteRadios.change( function () {
		var reassignSelectBox = jQuery( this ).parents( 'tr' ).find( '.reassign-user' );

		if ( "true" === jQuery( this ).val() ) {
			reassignSelectBox.removeAttr( 'disabled' );
		} else {
			reassignSelectBox.attr( 'disabled', 'true' );
		}
	} );
} );

BulkWP.validateUserMeta = function () {
	return (jQuery( '#smbd_u_meta_value' ).val() !== '');
};

BulkWP.validateUserRole = function ( that ) {
	return (null !== jQuery( that ).parent().prev().find( ".enhanced-role-dropdown" ).val());
};

/*global ajaxurl*/
jQuery( document ).ready( function () {
	/**
	 * Normal select2.
	 */
	jQuery( '.select2-taxonomy, .enhanced-dropdown, .enhanced-role-dropdown' ).select2( {
		width: '300px'
	} );

	/**
	 * Select 2 for posts types with status.
	 *
	 * The label of the selected item is modified to include the optgroup label.
	 */
	jQuery( '.enhanced-post-types-with-status' ).select2( {
		width: '400px',
		templateSelection: function (state) {
			if ( ! state.id ) {
				return state.text;
			}

			return jQuery(
				'<span>' + state.element.parentElement.label + ' - ' + state.text + '</span>'
			);
		}
	});

	/**
	 * Enable AJAX for Taxonomy Select2.
	 */
	jQuery( '.select2-taxonomy-ajax' ).select2( {
		ajax: {
			url: ajaxurl,
			dataType: 'json',
			delay: 250,
			data: function ( params ) {
				return {
					q: params.term,
					taxonomy: jQuery( this ).attr( 'data-taxonomy' ),
					action: 'bd_load_taxonomy_term'
				};
			},
			processResults: function ( data ) {
				var options = [];

				if ( data ) {
					jQuery.each( data, function ( index, dataPair ) {
						options.push( { id: dataPair[ 0 ], text: dataPair[ 1 ] } );
					} );
				}

				return {
					results: options
				};
			},
			cache: true
		},
		minimumInputLength: 2, // the minimum of symbols to input before perform a search
		width: '300px'
	} );
} );

/*global jQuery, BulkWP*/
jQuery(document).ready(function () {
	BulkWP.enableHelpTooltips( jQuery( '.bd-help' ) );
});

BulkWP.enableHelpTooltips = function ( $selector ) {
	$selector.tooltip({
		content: function() {
			return jQuery(this).prop('title');
		},
		position: {
			my: 'center top',
			at: 'center bottom+10',
			collision: 'flipfit'
		},
		hide: {
			duration: 200
		},
		show: {
			duration: 200
		}
	});
};

/*global BulkWP*/

/**
 * No need to validate anything.
 *
 * @returns {boolean} Returns true always.
 */
BulkWP.noValidation = function() {
	return true;
};

/**
 * Validate enhanced dropdowns.
 *
 * @param that Reference to the button.
 * @returns {boolean} True if validation succeeds, False otherwise.
 */
BulkWP.validateEnhancedDropdown = function ( that ) {
	var value = jQuery( that ).parent().prev().children().find( ".enhanced-dropdown" ).val();

	return ( value !== null && value !== '-1' );
};

BulkWP.validateSelect2 = function(that) {
	if ( null !== jQuery( that ).parent().prev().children().find( ".select2-taxonomy[multiple]" ).val() ) {
		return true;
	} else {
		return false;
	}
};

/**
 * Validate textboxes.
 *
 * @param that Reference to the button.
 * @returns {boolean} True if validation succeeds, False otherwise.
 */
BulkWP.validateTextbox = function(that) {
	return ( "" !== jQuery(that).parent().prev().children().find(":input[type=number].validate, :text.validate").val() );
};

/**
 * Validate checkboxes.
 *
 * @param that Reference to the button.
 * @returns {boolean} True if validation succeeds, False otherwise.
 */
BulkWP.validateCheckbox = function(that) {
	return ( jQuery(that).parent().prev().find("input:checkbox.validate").is ( ":checked" ) );
};

//# sourceMappingURL=bulk-delete.js.map