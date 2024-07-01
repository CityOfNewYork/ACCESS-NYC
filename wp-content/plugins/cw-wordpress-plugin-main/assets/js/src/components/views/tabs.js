module.exports = function (app, $, gc) {
	return app.views.base.extend({
		initial: gc._initial,
		el: '#mapping-tabs',

		template: wp.template('gc-tabs-wrapper'),

		events: {
			'click .nav-tab': 'tabClick',
			'click .nav-tab-link': 'triggerClick'
		},

		initialize: function () {
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
				window.setTimeout(function () {
					this.pointer($('.submit .gc-refresh-connection'), 'refresh_connection', {
						position: {
							edge: 'top'
						}
					});

					$('.gc-wp-pointer.refresh_connection').css({'margin-left': '-41px'});
				}.bind(this), 500);
			}
		},

		initMapping: function () {
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

		triggerSaveEnabled: function (model) {
			if (model.changed.field_value) {
				this.trigger('saveEnabled');
				this.stopListening(this.collection, 'rowChange');
			}
		},

		triggerClick: function (evt) {
			evt.preventDefault();

			this.$('.nav-tab[href="' + $(evt.target).attr('href') + '"]').trigger('click');
		},

		tabClick: function (evt) {
			evt.preventDefault();
			this.setTab($(evt.target).attr('href').substring(1));
			this.render();
		},

		setTab: function (id) {
			this.$el.attr('class', id);
			this.collection.invoke('set', {'hidden': true});
			this.collection.getById(id).set('hidden', false);
		},

		render: function () {
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

		renderNav: function () {
			var toAppend;

			if (this.initial) {
				this.setTab(this.defaultTab.get('id'));
				toAppend = (new app.views.tabLink({model: this.defaultTab})).render().el;

			} else {
				toAppend = this.getRenderedModels(app.views.tabLink);
			}

			this.$el.find('.nav-tab-wrapper').append(toAppend);
		},

		renderTabs: function () {
			var frag = document.createDocumentFragment();
			if (this.initial) {

				this.defaultTab.set('initial', this.initial);
				var view = new app.views.defaultTab({model: this.defaultTab});
				frag.appendChild(view.render().el);

			} else {

				this.collection.each(function (model) {
					var viewid = 'mapping-defaults' === model.get('id') ? 'defaultTab' : 'tab';
					var view = new app.views[viewid]({model: model});

					frag.appendChild(view.render().el);
				});
			}

			this.$el.find('.gc-template-tab-group').append(frag);
		},

		renderInitial: function () {
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

		enableSave: function () {
			// Enable save button.
			$('.submit .button-primary').prop('disabled', false);
		},

		disableSave: function () {
			// Disable save button.
			$('.submit .button-primary').prop('disabled', true);
		},

		pointer: function ($selector, key, args) {
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

			$selector = ($selector instanceof jQuery) ? $selector : this.$($selector);
			return $selector.pointer(defaults).pointer('open');
		}

	});
};
