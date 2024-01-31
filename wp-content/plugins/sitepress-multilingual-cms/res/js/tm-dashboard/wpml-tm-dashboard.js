/*global wpml_tm_strings, jQuery, Backbone, icl_ajxloaderimg, ajaxurl, ProgressBar */
/*jslint laxbreak: true */

var WPML_TM = WPML_TM || {};

(function () {
	'use strict';

WPML_TM.Dashboard = Backbone.View.extend({
	events: {
		'click td[scope="row"] :checkbox':   'update_td',
		"click td.check-column :checkbox":   'icl_tm_select_all_documents',
		"change #parent-filter-control":     'populate_parent_list',
		"change #icl_language_selector":     'populate_parent_list',
		"change #filter_type":               'show_hide_parent_controls'
	},
    counts: {
        all: 0,
        duplicate: 0,
        translate: 0
    },
	init: function ( $ ) {
		var self = this;
		self.$ = $;
		self.counts.all = self.setElement( '.icl_tm_wrap' );
		self.show_hide_parent_controls();
	},
    iclTmUpdateDashboardSelection: function () {
        var self = this;
        if (self.$el.find(':checkbox:checked').length > 0) {
            var checked_items = self.$el.find('td.check-column :checkbox');
            if (self.$el.find('td[scope="row"] :checkbox:checked').length === self.$el.find('td[scope="row"] :checkbox').length) {
                checked_items.prop('checked', true);
            } else {
                checked_items.prop('checked', false);
            }
        }
    },
    recount: function(){
        var self = this;
        var radios = jQuery('#icl_tm_languages').find('tbody').find(':radio:checked');
        self.counts.duplicate = radios.filter('[value=2]').length;
        self.counts.translate = radios.filter('[value=1]').length;
        self.counts.all = radios.length;

        return self;
    },
    update_td: function () {
        var self = this;
        self.icl_tm_update_word_count_estimate();
        self.iclTmUpdateDashboardSelection();
    },
	icl_tm_select_all_documents: function(e) {
		var self = this;
		self.$el.find('#icl-tm-translation-dashboard').
		     find(':checkbox:not(:disabled)').
		     prop('checked', !!jQuery(e.target).prop('checked'));
		self.icl_tm_update_word_count_estimate();
		self.icl_tm_update_doc_count();
	},
    icl_tm_update_word_count_estimate: function () {
        var self = this;
        var element_rows = self.$el.find('tbody').find('tr');
        var current_overall_word_count = 0;
        var icl_tm_estimated_words_count = jQuery('#icl-tm-estimated-words-count');
        jQuery.each(element_rows, function () {
            var row = jQuery(this);
            if (row.find(':checkbox').prop('checked')) {
                var item_word_count = row.data('word_count');
                var val = parseInt(item_word_count);
                val = isNaN(val) ? 0 : val;
                current_overall_word_count += val;
            }
        });
        icl_tm_estimated_words_count.html(current_overall_word_count);
        self.icl_tm_update_doc_count();
    },

	populate_parent_list: function () {
		var self = this,
			parent_select = self.$( '#parent-filter-control' ),
			parent_taxonomy_item_container = self.$( '[name="parent-taxonomy-item-container"]' ),
			val = parent_select.val();

		if ( val ) {
			parent_taxonomy_item_container.hide();
			if ( val != 'any' ) {
				var ajax_loader = self.$( '<span class="spinner"></span>' );
				ajax_loader.insertBefore( parent_taxonomy_item_container ).css( {
					visibility: "visible",
					float: "none"
				} );
				self.$.ajax( {
					type: "POST",
					url: ajaxurl,
					dataType: 'json',
					data: {
						action: 'icl_tm_parent_filter',
						type: val,
						from_lang: self.$( 'select[name="filter[from_lang]"]' ).val(),
						parent_id: self.$( '[name="filter[parent_id]"]' ).val()
					},
					success: function ( response ) {
						parent_taxonomy_item_container.html( response.data.html );
						parent_taxonomy_item_container.show();
						ajax_loader.remove();
					}
				} );
			}
		}
	},

	show_hide_parent_controls: function (e) {
		var self = this,
			selected_option = self.$( '#filter_type option:selected' ),
			parent_data = selected_option.data( 'parent' ),
			taxonomy_data = selected_option.data( 'taxonomy' );

		if ( parent_data || taxonomy_data ) {
			self.$( '#parent-taxonomy-container' ).show();
			self.fill_parent_type_select( parent_data, taxonomy_data );
			self.populate_parent_list();
		} else {
			self.$( '#parent-taxonomy-container' ).hide();
		}
	},

	fill_parent_type_select: function ( parent_data, taxonomy_data ) {
		var self = this,
			parent_select = self.$( '#parent-filter-control' );

		parent_select.find( 'option' ).remove();

		parent_select.append( '<option value="any">' + wpml_tm_strings.any + '</option>' );

		if ( parent_data ) {
			parent_select.append( '<option value="page">' + wpml_tm_strings.post_parent + '</option>' );
		}
		if ( taxonomy_data ) {
			taxonomy_data = taxonomy_data.split( ',' );
			for ( var i = 0; i < taxonomy_data.length; i++ ) {
				var parts = taxonomy_data[i].split( '=' );
				parent_select.append( '<option value="' + parts[0] + '">' + parts[1] + '</option>' );
			}
		}
		parent_select.val( parent_select.data( 'original' ) );
		parent_select.data( 'original', '' );
		if ( ! parent_select.val() ) {
			parent_select.val( 'any' );
		}

	},

    icl_update_button_label: function (dupl_count, trans_count) {
        var button_label;
        if (dupl_count > 0 && trans_count === 0) {
            button_label = wpml_tm_strings.BB_duplicate_all;
        } else if (dupl_count > 0 && trans_count > 0) {
            button_label = wpml_tm_strings.BB_mixed_actions;
        } else if (dupl_count === 0 && trans_count > 0) {
            button_label = wpml_tm_strings.BB_default;
        } else {
            button_label = wpml_tm_strings.BB_no_actions;
        }

		jQuery('#icl_tm_jobs_submit').html(button_label);
    },
	icl_update_button_class: function (dupl_count, trans_count) {
		var button= jQuery('#icl_tm_jobs_submit');
		var button_class= 'otgs-ico-basket';
		if ((dupl_count > 0 && trans_count === 0) || button.data('use-basket') === 0) {
			button.removeClass(button_class);
		} else {
			button.addClass(button_class);
		}
	},
    icl_tm_update_doc_count: function () {
        var self = this;
        var dox = self.$el.find('tbody td :checkbox:checked').length;
        jQuery('#icl-tm-sel-doc-count').html(dox);
        if (dox) {
            jQuery('#icl-tm-doc-wrap').fadeIn();
        } else {
            jQuery('#icl-tm-doc-wrap').fadeOut();
        }
    }
});

jQuery(function () {
    var tmDashboard = new WPML_TM.Dashboard();
    tmDashboard.init(jQuery);
});

}());
