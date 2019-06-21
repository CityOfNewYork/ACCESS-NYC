/**
 * plugin admin area javascript
 */
(function($){$(function () {
	
	if ( ! $('body.wpallimport-plugin').length) return; // do not execute any code if we are not on plugin page

	var pmai_repeater_clone = function($parent){
		
		var $clone = $parent.find('tbody:first').children('.row-clone:first').clone();
		var $number = parseInt($parent.find('tbody:first').children().length);		

		$clone.removeClass('row-clone').addClass('row').find('td.order').html($number);
		$clone.find('.switcher').each(function(){
			$(this).attr({'id':$(this).attr('id').replace('ROWNUMBER', $number)});
		});
		$clone.find('.chooser_label').each(function(){
			$(this).attr({'for':$(this).attr('for').replace('ROWNUMBER', $number)});
		});
		$clone.find('div[class^=switcher-target]').each(function(){
			$(this).attr({'class':$(this).attr('class').replace('ROWNUMBER', $number)});
		});
		$clone.find('input, select, textarea').each(function(){
			var name = $(this).attr('name');
			if (name != undefined) $(this).attr({'name':$(this).attr('name').replace('ROWNUMBER', $number)});
		});
		
		$parent.find('.acf-input-table:first').find('tbody:first').append($clone);		

		$parent.find('tr.row').find('.sortable').each(function(){			
			if ( ! $(this).hasClass('ui-sortable') && ! $(this).parents('tr.row-clone').length ){
				$(this).pmxi_nestedSortable({
			        handle: 'div',
			        items: 'li.dragging',
			        toleranceElement: '> div',
			        update: function () {
				       $(this).parents('td:first').find('.hierarhy-output').val(window.JSON.stringify($(this).pmxi_nestedSortable('toArray', {startDepthCount: 0})));
				       if ($(this).parents('td:first').find('input:first').val() == '') $(this).parents('td:first').find('.hierarhy-output').val('');
				    }
			    });
			}
		
		});
	};

	$('form.wpallimport-template:visible').find('.repeater').find('.add-row-end').live('click', function(){

		var $parent = $(this).parents('.repeater:first');
		
		pmai_repeater_clone($parent);

	});

	$('.add_layout_button').live('click', function(){

		var $parent = $(this).parents('.acf-flexible-content:first');

		var $dropdown = $parent.children('.add_layout').children('select'); //$('.add_layout select');

		if ($dropdown.val() == "" || $dropdown.val() == "Select Layout") return;

		var $clone = $parent.children('.clones:first').children('div.layout[data-layout = ' + $dropdown.val() + ']').clone();

		var $number = parseInt($parent.children('.values:first').children().length) + 1;

		$clone.find('.fc-layout-order:first').html($number);

		$clone.find('.switcher').each(function(){
			$(this).attr({'id':$(this).attr('id').replace('ROWNUMBER', $number)});
		});
		$clone.find('.chooser_label').each(function(){
			$(this).attr({'for':$(this).attr('for').replace('ROWNUMBER', $number)});
		});
		$clone.find('div[class^=switcher-target]').each(function(){
			$(this).attr({'class':$(this).attr('class').replace('ROWNUMBER', $number)});
		});
		$clone.find('input, select, textarea').each(function(){
			var name = $(this).attr('name');
			if (name != undefined) $(this).attr({'name':$(this).attr('name').replace('ROWNUMBER', $number)});
		});

		pmai_init($clone);

		$parent.children('div.values:first').append($clone);

	});

	$('.delete_layout_button').live('click', function(){

		var $parent = $(this).parents('.acf-flexible-content:first');

		$parent.children('.values:first').children(':last').remove();

	});

	$('.delete_row').live('click', function(){

		var $parent = $(this).parents('.repeater:first');
		
		$parent.find('tbody:first').children('.row:last').remove();

	});

	var pmai_get_acf_group = function(ths){

		var request = {
			action:'get_acf',		
			security: wp_all_import_security,		
			acf: ths.attr('rel')
	    };

	    if (typeof import_id != "undefined") request.id = import_id;

	    var $ths = ths.parents('.pmai_options:first');

	    $ths.find('.acf_groups').prepend('<div class="pmai_preloader"></div>');

	    $('.pmai_acf_group').attr('disabled', 'disabled');

		$.ajax({
			type: 'GET',
			url: ajaxurl,
			data: request,
			success: function(response) {
				$('.pmai_acf_group').removeAttr('disabled');
				$ths.find('.pmai_preloader').remove();						
				$ths.find('.acf_groups').prepend(response.html);
				pmai_init($ths.find('.acf_signle_group:first'));
				// swither show/hide logic
				$ths.find('.acf_groups').find('input.switcher').change();
			},
			error: function(jqXHR, textStatus){
				$('.pmai_acf_group').removeAttr('disabled');
				$ths.find('.pmai_preloader').remove();
				alert('Something went wrong. ' + textStatus );
			},
			dataType: "json"
		});
	}

	var pmai_reset_acf_groups = function(){
		
		$('.pmai_options').find('.acf_signle_group').remove();

		$('.pmai_options:visible').find('.pmai_acf_group:checked').each(function(){
			
			pmai_get_acf_group($(this));

		});
	}

	pmai_reset_acf_groups();

	$('.pmxi_plugin').find('.nav-tab').click(function(){
		
		pmai_reset_acf_groups();

	});

	$('.pmai_acf_group').live('change', function(){

		var acf = $(this).attr('rel');

		if ($(this).is(':checked')){

			// if requsted ACF group doesn't exists
			if ( ! $(this).parents('.pmai_options:first').find('.acf_signle_group[rel=' + acf + ']').length){

				pmai_get_acf_group($(this));
			}	
		}
		else{ 
			if (confirm("Confirm removal?")) 
				$(this).parents('.pmai_options:first').find('.acf_signle_group[rel=' + acf + ']').remove();
			else 
				$(this).attr('checked','checked');
		}

	});	

	function pmai_init(ths){

		ths.find('input.datetimepicker').datetimepicker({
			dateFormat: 'dd/mm/yy',
			timeFormat: 'hh:mm TT',
			ampm: true
		});

		ths.find('input.datepicker').datepicker();
		
		ths.find('.sortable').each(function(){
			if ( ! $(this).parents('tr.row-clone').length ){
				$(this).pmxi_nestedSortable({
			        handle: 'div',
			        items: 'li.dragging',
			        toleranceElement: '> div',
			        update: function () {	        
				       $(this).parents('td:first').find('.hierarhy-output').val(window.JSON.stringify($(this).pmxi_nestedSortable('toArray', {startDepthCount: 0})));
				       if ($(this).parents('td:first').find('input:first').val() == '') $(this).parents('td:first').find('.hierarhy-output').val('');
				    }
			    });		    
			} 
		});
	}

	$('.acf_signle_group').find('.switcher').live('click', function(){
	
		$(this).parents('div.acf-input-wrap:first').find('.sortable').each(function(){			
			if ( ! $(this).hasClass('ui-sortable') && ! $(this).parents('tr.row-clone').length ){
				$(this).pmxi_nestedSortable({
			        handle: 'div',
			        items: 'li.dragging',
			        toleranceElement: '> div',
			        update: function () {
				       $(this).parents('td:first').find('.hierarhy-output').val(window.JSON.stringify($(this).pmxi_nestedSortable('toArray', {startDepthCount: 0})));
				       if ($(this).parents('td:first').find('input:first').val() == '') $(this).parents('td:first').find('.hierarhy-output').val('');
				    }
			    });
			}
		
		});

	});

	$('.variable_repeater_mode').live('change', function(){
		// if variable mode
		if ($(this).is(':checked') && ($(this).val() == 'yes' || $(this).val() == 'csv')){
			var $parent = $(this).parents('.repeater:first');
			$parent.find('tbody:first').children('.row:not(:first)').remove();
			if ( ! $parent.find('tbody:first').children('.row').length) pmai_repeater_clone($parent);
		}
	});

});})(jQuery);
