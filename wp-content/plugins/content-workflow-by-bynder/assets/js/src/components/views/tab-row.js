module.exports = function (app, _meta_keys) {
	return app.views.base.extend({
		tagName: 'tr',
		template: wp.template('gc-mapping-tab-row'),

		events: {
			'change .wp-type-select': 'changeType',
			'change .wp-type-value-select': 'changeValue',
			'change .wp-type-field-select': 'changeField',
			'change .wp-subfield-select': 'changeSubfield',
			'click  .gc-reveal-items': 'toggleExpanded'
		},

		initialize: function () {
			this.listenTo(this.model, 'change:field_type', this.render);

			// Initiate the metaKeys collection.
			this.metaKeys = new (app.collections.base.extend({
				model: app.models.base.extend({
					defaults: {
						value: '',
						field: '',
						subfields: '',
					}
				}),
				getByValue: function (value) {
					return this.find(function (model) {
						return model.get('value') === value;
					});
				},
				getByField: function (field) {
					return this.find(function (model) {
						return model.get('field') === field;
					});
				},
				getBySubfields: function (subfields) {
					return this.find(function (model) {
						return model.get('subfields') === subfields;
					});
				},
			}))(_meta_keys);
		},

		/**
		 * 1st Dropdown - event change
		 */
		changeType: function (evt) {
			this.model.set('field_type', jQuery(evt.target).val());
		},

		/**
		 * 2nd Dropdown - event change
		 */
		changeValue: function (evt) {
			var component = jQuery(evt.target).closest('.component-table-wrapper').attr('id');
			var value = jQuery(evt.target).val();
			var type = this.model.get('type');
			var fieldType = this.model.get('field_type');
			if ('' === value) {
				this.model.set('field_type', '');
				this.model.set('field_value', '');
				this.model.set('field_field', '');
				this.model.set('field_subfields', {});
				jQuery('#' + component + ' .component-table-inner ').find('select').html("<option value=''>Unused</option>").val("");
			} else {
				this.model.set('field_value', value);
				// Components - Update "Field"
				if ("component" === type) {
					this.updateAjax_Field(component, value, false);
				}
				// Repeaters - Update "Field"
				else if ("wp-type-acf" === fieldType) {
					var id = jQuery(evt.target).closest('td').attr('id');
					this.updateAjax_Field(id, value, false);
				}
			}
		},

		/**
		 * 3rd Dropdown - event change
		 */
		changeField: function (evt) {
			var value = jQuery(evt.target).val();
			var component = jQuery(evt.target).closest('.component-table-wrapper').attr('id');
			// Update Data
			this.model.set('field_subfields', {});
			if ('' === value) {
				this.model.set('field_field', '');
				jQuery('#' + component + ' .component-table-inner ').find('select').html("<option value=''>Unused</option>").val("");
			} else {
				this.model.set('field_field', value);
				// Update subfields
				this.updateAjax_ComponentSubfields(component, value, false);
			}
		},

		/**
		 * LVL 2: Subfield Dropdown - event change
		 */
		changeSubfield: function (evt) {
			var value = jQuery(evt.target).val();
			var index = jQuery(evt.target).attr('data-index');
			var subfield_data = this.model.get('field_subfields');
			if (!subfield_data) {
				subfield_data = {};
			}
			subfield_data[index] = value;
			this.model.set('field_subfields', subfield_data);
		},

		/**
		 * Helper function: build option html elements for AJAX funtions
		 */
		optionBuilder: function (data) {
			var options_html = "<option value=''>Unused</option>";
			jQuery.each(data.field_data, function (i, field) {
				options_html += "<option class='hidden' value='" + field.key + "' data-type='" + field.type + "'>" + field.label + "</option>";
			});
			return options_html;
		},

		/**
		 * AJAX Update: "Field" - ACF Field group's field
		 * - "Field" refers to the 3rd dropdown of the component fields top level
		 * - After selecting the field group from the 2nd dropdown, call WP_AJAX to get the relevent fields from the group selected and populate the 3rd dropdown (aka "Field")
		 *
		 * @param {string} component - ID without the "#" of the component parent row
		 * @param {string} field_name - Parent field name/key of the sub fields, should be a repeater
		 * @param {object} saved_fields - OPTIONAL: Pass saved subfields if you want to set pre-existing values
		 */
		updateAjax_Field: function (component, field_name, saved_fields) {
			saved_fields = typeof saved_fields !== 'undefined' ? saved_fields : "";
			var $this = this;

			// Update UI
			jQuery('#' + component + ' .wp-type-field-select ~ span.select2').addClass('ajax-disabled');
			// Get Updated Data
			jQuery.post(window.ajaxurl, {
				action: 'cwby_component_subfields',
				subfields_data: {
					name: field_name,
				}
			}, function (response) {
				// Update UI
				jQuery('#' + component + ' .wp-type-field-select ~ span.select2').removeClass('ajax-disabled');

				// SUCCESS
				if (response.success) {
					// Ensure response has subfield data
					if (response.data.field_data && response.data.field_data.length) {
						// Build options HTML:
						var options_html = $this.optionBuilder(response.data);
						// Inject into select fields
						jQuery('#' + component).find('.wp-type-field-select').html(options_html);

						// If existing subfields are passed, update specific dropdown options
						if (saved_fields) {
							jQuery('#' + component).find('.wp-type-field-select').val(saved_fields);
						}
					}
				}
				// ERROR
				else {
					window.alert('Please refresh and try again. If the issue persists, reach out to support');
				}
			});
		},

		/**
		 * AJAX Update: "Subfields" - ACF Field group's repeater subfields
		 * - "Subfields" are in the component accordion
		 * - After selecting the field group from the 3rd dropdown, call WP_AJAX to get the relevent subfields from the ACF Repeater selected and populate the subfields
		 *
		 * @param {string} component - ID without the "#" of the component parent row
		 * @param {string} field_name - Parent field name/key of the sub fields, should be a repeater
		 * @param {object} saved_fields - OPTIONAL: Pass saved subfields if you want to set pre-existing values
		 */
		updateAjax_ComponentSubfields: function (component, field_name, saved_fields) {
			saved_fields = typeof saved_fields !== 'undefined' ? saved_fields : {};
			var $this = this;

			// Update UI
			jQuery('#' + component + ' .component-table-inner').find('select').addClass('ajax-disabled');
			// Get Updated Data
			jQuery.post(window.ajaxurl, {
				action: 'cwby_component_subfields',
				subfields_data: {
					name: field_name,
				}
			}, function (response) {
				// Update UI
				jQuery('#' + component + ' .component-table-inner').find('select').removeClass('ajax-disabled');

				// SUCCESS
				if (response.success) {
					// Ensure response has subfield data
					if (response.data.field_data && response.data.field_data.length) {
						// Build options HTML:
						var options_html = $this.optionBuilder(response.data);
						// Inject into select fields
						jQuery('#' + component).find('.component-table-inner select').html(options_html);

						// If existing subfields are passed, update specific dropdown options
						if (Object.keys(saved_fields).length) {
							var dropdowns = jQuery('#' + component).find('.component-table-inner select').toArray();
							jQuery.each(dropdowns, function (i, dropdown) {
								i++;
								jQuery(dropdown).val(saved_fields[i]);
							});
						}
					} else {
						window.alert('The chosen field is not a repeater field and therefore not compatible with Content Workflow components');
					}
				}
				// ERROR
				else {
					window.alert('Please refresh and try again. If the issue persists, reach out to support');
				}
			});
		},

		/**
		 * Init
		 */
		render: function () {
			var val = this.model.get('field_value');
			var valField = this.model.get('field_field');
			var valSubfields = this.model.get('field_subfields');
			var component;

			if (val && !this.metaKeys.getByValue(val)) {
				this.metaKeys.add({value: val});
			}
			if (valField && !this.metaKeys.getByField(valField)) {
				this.metaKeys.add({field: valField});
			}
			if (valSubfields && !this.metaKeys.getBySubfields(valSubfields)) {
				this.metaKeys.add({subfields: valSubfields});
			}

			// Init subfields
			if (valField) {
				component = this.model.get('name');
				if (valSubfields) {
					this.updateAjax_ComponentSubfields(component, valField, valSubfields);
				}
			}

			var json = this.model.toJSON();
			json.metaKeys = this.metaKeys.toJSON();

			this.$el.html(this.template(json));

			this.$('.gc-select2').each(function () {
				var $this = jQuery(this);
				var args = {
					width: '250px'
				};

				if ($this.hasClass('gc-select2-add-new')) {
					args.tags = true;
				}

				$this.select2(args);
			});

			return this;
		}

	});
};
