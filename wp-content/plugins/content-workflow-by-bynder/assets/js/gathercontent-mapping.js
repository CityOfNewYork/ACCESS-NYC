/**
 * Content Workflow (by Bynder) - v1.0.5 - 2025-04-10
 * 
 *
 * Copyright (c) 2025 Content Workflow (by Bynder)
 * Licensed under the GPLv2 license.
 */

(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
'use strict';

module.exports = Backbone.Collection.extend({
	getById: function getById(id) {
		return this.find(function (model) {
			var modelId = model.get('id');
			return modelId === id || modelId && id && modelId == id;
		});
	}
});

},{}],2:[function(require,module,exports){
"use strict";

module.exports = function (app) {
	return app.collections.base.extend({
		model: app.models.tabRow,

		initialize: function initialize(models, options) {
			this.tab = options.tab;
		}
	});
};

},{}],3:[function(require,module,exports){
"use strict";

module.exports = function (app) {
	return app.collections.base.extend({
		model: app.models.tab
	});
};

},{}],4:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	app.models = { base: require('./models/base.js') };
	app.collections = { base: require('./collections/base.js') };
	app.views = { base: require('./views/base.js') };
};

},{"./collections/base.js":1,"./models/base.js":6,"./views/base.js":10}],5:[function(require,module,exports){
'use strict';

window.GatherContent = window.GatherContent || {};

(function (window, document, $, gc, undefined) {
	'use strict';

	gc.mapping = gc.mapping || {};
	var app = gc.mapping;

	// Initiate base objects.
	require('./initiate-objects.js')(app);
	app.views.statusSelect2 = require('./views/status-select2.js')(app);

	/*
  * Tab Row setup
  */

	app.models.tabRow = require('./models/tab-row.js')(app, gc);
	app.collections.tabRows = require('./collections/tab-rows.js')(app);
	app.views.tabRow = require('./views/tab-row.js')(app, gc._meta_keys);

	/*
  * Tab setup
  */

	app.models.tab = require('./models/tab.js')(app, gc._table_headings);
	app.collections.tabs = require('./collections/tabs.js')(app);
	app.views.tab = require('./views/tab.js')(app);

	app.views.tabLink = require('./views/tab-link.js')(app);

	app.views.defaultTab = require('./views/default-tab.js')(app, gc._table_headings);

	/*
  * Overall view setup
  */

	app.views.tabs = require('./views/tabs.js')(app, $, gc);

	app.init = function () {
		// Kick it off.
		app.mappingView = new app.views.tabs({
			collection: new app.collections.tabs(gc._tabs)
		});
	};

	$(app.init);
})(window, document, jQuery, window.GatherContent);

},{"./collections/tab-rows.js":2,"./collections/tabs.js":3,"./initiate-objects.js":4,"./models/tab-row.js":8,"./models/tab.js":9,"./views/default-tab.js":11,"./views/status-select2.js":12,"./views/tab-link.js":13,"./views/tab-row.js":14,"./views/tab.js":15,"./views/tabs.js":16}],6:[function(require,module,exports){
"use strict";

module.exports = Backbone.Model.extend({
	sync: function sync() {
		return false;
	}
});

},{}],7:[function(require,module,exports){
'use strict';

module.exports = function (model) {

	model.prototype._get = function (value, attribute) {
		if (this['_get_' + attribute]) {
			value = this['_get_' + attribute](value);
		}
		return value;
	};

	model.prototype.get = function (attribute) {
		return this._get(Backbone.Model.prototype.get.call(this, attribute), attribute);
	};

	// hijack the toJSON method and overwrite the data that is sent back to the view.
	model.prototype.toJSON = function () {
		return _.mapObject(Backbone.Model.prototype.toJSON.call(this), _.bind(this._get, this));
	};

	return model;
};

},{}],8:[function(require,module,exports){
'use strict';

module.exports = function (app, gc) {
	return require('./../models/modify-json.js')(app.models.base.extend({
		defaults: {
			id: '',
			label: '',
			name: '',
			field_type: '',
			type: '',
			typeName: '',
			post_type: 'post',
			field_value: false,
			field_field: false,
			field_subfields: false,
			expanded: false,
			required: false,
			value: '',
			microcopy: '',
			limit_type: '',
			limit: 0,
			plain_text: false
		},

		_get_post_type: function _get_post_type(value) {
			return app.mappingView ? app.mappingView.defaultTab.get('post_type') : value;
		},

		_get_type: function _get_type(value) {
			if ('text' === value) {
				value = this.get('plain_text') ? 'text_plain' : 'text_rich';
			}
			return value;
		},

		_get_typeName: function _get_typeName(value) {
			value = this.get('type');
			return gc._type_names[value] ? gc._type_names[value] : value;
		}
	}));
};

},{"./../models/modify-json.js":7}],9:[function(require,module,exports){
'use strict';

module.exports = function (app, table_headings) {
	return app.models.base.extend({
		defaults: {
			id: '',
			label: '',
			hidden: false,
			navClasses: '',
			rows: [],
			table_id: '',
			col_headings: table_headings['default']
		},

		initialize: function initialize() {
			this.rows = new app.collections.tabRows(this.get('rows'), { tab: this });
			this.listenTo(this.rows, 'change', this.triggerRowChange);
		},

		triggerRowChange: function triggerRowChange(rowModel) {
			this.trigger('rowChange', rowModel);
		}
	});
};

},{}],10:[function(require,module,exports){
'use strict';

module.exports = Backbone.View.extend({
	toggleExpanded: function toggleExpanded(evt) {
		this.model.set('expanded', !this.model.get('expanded'));
	},

	getRenderedModels: function getRenderedModels(View, models) {
		models = models || this.collection;
		var addedElements = document.createDocumentFragment();

		models.each(function (model) {
			var view = new View({ model: model }).render();
			addedElements.appendChild(view.el);
		});

		return addedElements;
	},

	render: function render() {
		this.$el.html(this.template(this.model.toJSON()));
		return this;
	},

	close: function close() {
		this.remove();
		this.unbind();
		if (this.onClose) {
			this.onClose();
		}
	}
});

},{}],11:[function(require,module,exports){
'use strict';

module.exports = function (app, table_headings) {
	return app.views.tab.extend({
		events: {
			'change select': 'changeDefault',
			'click .gc-reveal-items': 'toggleExpanded'
		},

		defaultTabTemplate: wp.template('gc-mapping-defaults-tab'),
		statusMappingsTemplate: wp.template('gc-mapping-defaults-tab-status-mappings'),

		changeDefault: function changeDefault(evt) {
			var $this = jQuery(evt.target);
			var value = $this.val();
			var column = $this.data('column');

			if (value) {
				if ($this.data('select2')) {
					var data = $this.select2('data')[0];
					if (data.text) {
						this.model.set('select2:' + column + ':' + value, data.text);
					}
				}
				this.model.set(column, value);
			}
		},

		render: function render() {
			var json = this.model.toJSON();

			this.$el.html(this.htmlWrap(json));
			this.$el.find('tbody').first().html(this.defaultTabTemplate(json));
			this.$el.find('#gc-status-mappings tbody').html(this.statusMappingsTemplate(json));

			this.renderSelect2();

			return this;
		},

		htmlWrap: function htmlWrap(json) {
			var html = this.template(json);

			// Only add the GatherContent status => WP status table if initialized.
			if (!this.model.get('initial')) {
				json.table_id = 'gc-status-mappings';
				delete json.label;
				json.col_headings = table_headings.status;

				html += '<br>';
				html += this.template(json);
			}

			return html;
		},

		select2Args: function select2Args(_data) {
			var args = {};

			switch (_data.column) {
				case 'gc_status':
					args = app.views.statusSelect2.prototype.select2Args.call(this, _data);
					break;

				case 'post_author':
					args.width = '250px';
					args.minimumInputLength = 2;
					args.ajax = {
						url: _data.url,
						data: function data(params) {
							return {
								q: params.term,
								column: _data.column
							};
						},
						delay: 250,
						cache: true
					};

					break;
			}

			return args;
		}

	});
};

},{}],12:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	var thisView;
	return app.views.base.extend({
		select2ItemTemplate: wp.template('gc-select2-item'),
		width: '250px',

		renderSelect2: function renderSelect2($context) {
			var $selector = $context ? $context.find('.gc-select2') : this.$('.gc-select2');
			thisView = this;

			$selector.each(function () {
				var $this = jQuery(this);
				var data = $this.data();
				$this.select2(thisView.select2Args(data));
				var s2Data = $this.data('select2');

				// Add classes for styling.
				s2Data.$results.addClass('select2-' + data.column);
				s2Data.$container.addClass('select2-' + data.column);
			});

			return this;
		},

		select2Args: function select2Args(data) {
			var args = {
				width: thisView.width
			};

			args.templateResult = (function (status, showDesc) {
				var data = jQuery.extend(status, jQuery(status.element).data());
				data.description = false === showDesc ? false : data.description || '';
				return jQuery(thisView.select2ItemTemplate(status));
			}).bind(thisView);

			args.templateSelection = function (status) {
				return args.templateResult(status, false);
			};

			return args;
		}

	});
};

},{}],13:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	return app.views.base.extend({
		tagName: 'a',

		id: function id() {
			return 'tabtrigger-' + this.model.get('id');
		},

		className: function className() {
			return 'nav-tab ' + (this.model.get('hidden') ? '' : 'nav-tab-active') + ' ' + this.model.get('navClasses');
		},

		render: function render() {
			this.$el.text(this.model.get('label')).attr('href', '#' + this.model.get('id'));

			return this;
		}

	});
};

},{}],14:[function(require,module,exports){
'use strict';

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

		initialize: function initialize() {
			this.listenTo(this.model, 'change:field_type', this.render);

			// Initiate the metaKeys collection.
			this.metaKeys = new (app.collections.base.extend({
				model: app.models.base.extend({
					defaults: {
						value: '',
						field: '',
						subfields: ''
					}
				}),
				getByValue: function getByValue(value) {
					return this.find(function (model) {
						return model.get('value') === value;
					});
				},
				getByField: function getByField(field) {
					return this.find(function (model) {
						return model.get('field') === field;
					});
				},
				getBySubfields: function getBySubfields(subfields) {
					return this.find(function (model) {
						return model.get('subfields') === subfields;
					});
				}
			}))(_meta_keys);
		},

		/**
   * 1st Dropdown - event change
   */
		changeType: function changeType(evt) {
			this.model.set('field_type', jQuery(evt.target).val());
		},

		/**
   * 2nd Dropdown - event change
   */
		changeValue: function changeValue(evt) {
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
		changeField: function changeField(evt) {
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
		changeSubfield: function changeSubfield(evt) {
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
		optionBuilder: function optionBuilder(data) {
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
		updateAjax_Field: function updateAjax_Field(component, field_name, saved_fields) {
			saved_fields = typeof saved_fields !== 'undefined' ? saved_fields : "";
			var $this = this;

			// Update UI
			jQuery('#' + component + ' .wp-type-field-select ~ span.select2').addClass('ajax-disabled');
			// Get Updated Data
			jQuery.post(window.ajaxurl, {
				action: 'cwby_component_subfields',
				subfields_data: {
					name: field_name
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
		updateAjax_ComponentSubfields: function updateAjax_ComponentSubfields(component, field_name, saved_fields) {
			saved_fields = typeof saved_fields !== 'undefined' ? saved_fields : {};
			var $this = this;

			// Update UI
			jQuery('#' + component + ' .component-table-inner').find('select').addClass('ajax-disabled');
			// Get Updated Data
			jQuery.post(window.ajaxurl, {
				action: 'cwby_component_subfields',
				subfields_data: {
					name: field_name
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
		render: function render() {
			var val = this.model.get('field_value');
			var valField = this.model.get('field_field');
			var valSubfields = this.model.get('field_subfields');
			var component;

			if (val && !this.metaKeys.getByValue(val)) {
				this.metaKeys.add({ value: val });
			}
			if (valField && !this.metaKeys.getByField(valField)) {
				this.metaKeys.add({ field: valField });
			}
			if (valSubfields && !this.metaKeys.getBySubfields(valSubfields)) {
				this.metaKeys.add({ subfields: valSubfields });
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

},{}],15:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	return app.views.statusSelect2.extend({
		template: wp.template('gc-tab-wrapper'),

		tagName: 'fieldset',

		id: function id() {
			return this.model.get('id');
		},

		className: function className() {
			return 'gc-template-tab ' + (this.model.get('hidden') ? 'hidden' : '');
		},

		render: function render() {
			this.$el.html(this.template(this.model.toJSON()));

			var rendered = this.getRenderedModels(app.views.tabRow, this.model.rows);

			this.$el.find('tbody').html(rendered);

			return this;
		}
	});
};

},{}],16:[function(require,module,exports){
'use strict';

module.exports = function (app, $, gc) {
	return app.views.base.extend({
		initial: gc._initial,
		el: '#mapping-tabs',

		template: wp.template('gc-tabs-wrapper'),

		events: {
			'click .nav-tab': 'tabClick',
			'click .nav-tab-link': 'triggerClick'
		},

		initialize: function initialize() {
			this.listenTo(this.collection, 'render', this.render);
			this.listenTo(this, 'render', this.render);
			this.listenTo(this, 'saveEnabled', this.enableSave);
			this.listenTo(this, 'saveDisabled', this.disableSave);

			if (this.initial) {
				// Listen for initialization
				this.listenTo(this.collection, 'change:post_type', this.initMapping);
			}

			this.defaultTab = this.collection.getById('mapping-defaults');
			this.render();

			if (!this.initial && gc._pointers.refresh_connection) {
				window.setTimeout((function () {
					this.pointer($('.submit .gc-refresh-connection'), 'refresh_connection', {
						position: {
							edge: 'top'
						}
					});

					$('.gc-wp-pointer.refresh_connection').css({ 'margin-left': '-41px' });
				}).bind(this), 500);
			}
		},

		initMapping: function initMapping() {
			this.initial = false;

			this.stopListening(this.collection, 'change:post_type', this.initMapping);
			this.listenTo(this.collection, 'rowChange', this.triggerSaveEnabled);

			this.defaultTab.set('initial', this.initial);
			this.render();

			if (gc._tabs && gc._tabs.length > 0) {
				var firstTabId = gc._tabs[0].id;
				if (firstTabId) {
					this.setTab(firstTabId);
					this.$('.nav-tab[href="#' + firstTabId + '"]').trigger('click');
				}
			}

			if (gc._pointers.select_tab_how_to) {
				this.pointer('.gc-nav-tab-wrapper-bb', 'select_tab_how_to');
				this.pointer('#gc-status-mappings', 'map_status_how_to');
			}
		},

		triggerSaveEnabled: function triggerSaveEnabled(model) {
			if (model.changed.field_value) {
				this.trigger('saveEnabled');
				this.stopListening(this.collection, 'rowChange');
			}
		},

		triggerClick: function triggerClick(evt) {
			evt.preventDefault();

			this.$('.nav-tab[href="' + $(evt.target).attr('href') + '"]').trigger('click');
		},

		tabClick: function tabClick(evt) {
			evt.preventDefault();
			this.setTab($(evt.target).attr('href').substring(1));
			this.render();
		},

		setTab: function setTab(id) {
			this.$el.attr('class', id);
			this.collection.invoke('set', { 'hidden': true });
			this.collection.getById(id).set('hidden', false);
		},

		render: function render() {
			this.$('.gc-select2').each(function () {
				$(this).select2('destroy');
			});

			this.$el.html(this.template());

			// Add tab links
			this.renderNav();

			// Add tab content
			this.renderTabs();

			if (this.initial) {
				this.renderInitial();
			}

			return this;
		},

		renderNav: function renderNav() {
			var toAppend;

			if (this.initial) {
				this.setTab(this.defaultTab.get('id'));
				toAppend = new app.views.tabLink({ model: this.defaultTab }).render().el;
			} else {
				toAppend = this.getRenderedModels(app.views.tabLink);
			}

			this.$el.find('.nav-tab-wrapper').append(toAppend);
		},

		renderTabs: function renderTabs() {
			var frag = document.createDocumentFragment();
			if (this.initial) {

				this.defaultTab.set('initial', this.initial);
				var view = new app.views.defaultTab({ model: this.defaultTab });
				frag.appendChild(view.render().el);
			} else {

				this.collection.each(function (model) {
					var viewid = 'mapping-defaults' === model.get('id') ? 'defaultTab' : 'tab';
					var view = new app.views[viewid]({ model: model });

					frag.appendChild(view.render().el);
				});
			}

			this.$el.find('.gc-template-tab-group').append(frag);
		},

		renderInitial: function renderInitial() {
			// Show the "select post-type" pointer.
			this.pointer('[data-column="post_type"]', 'select_type', {
				dismissable: false,
				position: {
					edge: 'bottom',
					align: 'left'
				}
			});

			this.trigger('saveDisabled');
		},

		enableSave: function enableSave() {
			// Enable save button.
			$('.submit .button-primary').prop('disabled', false);
		},

		disableSave: function disableSave() {
			// Disable save button.
			$('.submit .button-primary').prop('disabled', true);
		},

		pointer: function pointer($selector, key, args) {
			args = args || {};
			var defaults = {
				content: gc._pointers[key],
				pointerClass: 'wp-pointer gc-wp-pointer ' + key
			};

			if (false !== args.dismissable) {
				defaults.close = function () {
					$.post(window.ajaxurl, {
						pointer: 'gc_' + key,
						action: 'dismiss-wp-pointer'
					});
				};
			}

			if (args.position) {
				defaults.position = args.position;
			}

			$selector = $selector instanceof jQuery ? $selector : this.$($selector);
			return $selector.pointer(defaults).pointer('open');
		}

	});
};

},{}]},{},[5]);
