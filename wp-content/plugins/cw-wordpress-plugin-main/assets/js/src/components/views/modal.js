module.exports = function (app, gc, $) {
	app.modalView = undefined;

	var ESCAPE = 27;
	var thisView;
	/**
	 * Taken from https://github.com/aut0poietic/wp-admin-modal-example
	 */
	return app.views.tableBase.extend({
		id: 'gc-bb-modal-dialog',
		template: wp.template('gc-modal-window'),
		selected: [],
		navItems: null,
		btns: {},
		currID: 'select-items',
		currNav: false,
		metaboxView: null,
		modelView: app.views.modalPostRow,
		$search: gc.$id('gc-items-search'),

		events: {
			'click .gc-bb-modal-close': 'closeModal',
			'click #btn-cancel': 'closeModal',
			'click .gc-bb-modal-backdrop': 'closeModal',
			'click .gc-bb-modal-nav-tabs a': 'clickSelectTab',
			'change .gc-field-th.gc-check-column input': 'checkAll',
			'click #gc-btn-pull': 'startPull',
			'click #gc-btn-push': 'startPush',
			'click .gc-cloak': 'maybeResetMetaboxView',
			'click #gc-btn-assign-mapping': 'startAssignment',
			'click .gc-field-th.sortable': 'sortRowsByColumn',
		},

		/**
		 * Instantiates the Template object and triggers load.
		 */
		initialize: function () {
			thisView = this;

			if (!this.$search.length) {
				$(document.body).append('<div id="gc-items-search" class="hidden"></div>');
				this.$search = gc.$id('gc-items-search');
			}

			app.views.tableBase.prototype.initialize.call(this);

			_.bindAll(this, 'render', 'preserveFocus', 'maybeClose', 'closeModal');

			if (gc._nav_items) {
				this.navItems = new app.collections.navItems(gc._nav_items);
				this.currNav = this.navItems.getActive();
				this.listenTo(this.navItems, 'render', this.render);
			}

			this.btns = new app.collections.base(gc._modal_btns);

			this.listenTo(this.collection, 'updateItems', this.maybeRender);
			this.listenTo(this.collection, 'change:checked', this.checkEnableButton);
			this.listenTo(this.collection, 'search', this.render);

			this.initMetaboxView = require('./../views/modal-assign-mapping.js')(app, $, gc);
		},

		checked: function (selected) {
			this.selected = selected;
			if (!selected.length) {
				return;
			}

			if (selected.length === this.collection.length) {
				return this.collection.trigger('checkAll', true);
			}

			this.collection.trigger('checkSome', function (model) {
				return -1 !== _.indexOf(thisView.selected, model.get('id')) && !model.get('disabled');
			});

			return this;
		},

		setupAjax: function () {
			var Ajax = require('./../models/ajax.js')(app, {
				action      : 'cwby_pull_items',
				nonce: gc._edit_nonce,
				flush_cache: gc.queryargs.flush_cache ? 1 : 0,
			});

			this.ajax = new Ajax();
		},

		/**
		 * Assembles the UI from loaded templates.
		 * @internal Obviously, if the templates fail to load, our modal never launches.
		 */
		render: function () {
			var collection = this.collection.current();

			// Build the base window and backdrop, attaching them to the $el.
			// Setting the tab index allows us to capture focus and redirect it in Application.preserveFocus
			this.$el.removeClass('gc-set-mapping').attr('tabindex', '0')
				.html(this.template({
					btns: this.btns.toJSON(),
					navItems: this.navItems ? this.navItems.toJSON() : [],
					currID: this.currNav ? this.currNav.get('id') : 'select-items',
					checked: collection.allChecked,
					sortKey: collection.sortKey,
					sortDirection: collection.sortDirection,
				}))
				.append('<div class="gc-bb-modal-backdrop">&nbsp;</div>');

			app.views.tableBase.prototype.render.call(this);

			$(document)
				// Handle any attempt to move focus out of the modal.
				.on('focusin', this.preserveFocus)
				// Close modal on escape key.
				.on('keyup', this.maybeClose);

			// set overflow to "hidden" on the body so that it ignores any scroll events
			$(document.body).addClass('gc-modal-open');

			// Add modal before the search input.
			this.$search.before(this.$el);

			// Position search input. (After the above line, where we render the modal)
			this.$search.css(jQuery('#gc-tablenav').offset());

			// If we're not focused on the search input...
			if (!this.isSearch(document.activeElement)) {

				// Then set focus on the modal to prevent accidental actions in the underlying page.
				this.$el.focus();
			}

			return this;
		},

		/**
		 * Ensures that keyboard focus remains within the Modal dialog or search input.
		 * @param evt {object} A jQuery-normalized event object.
		 */
		preserveFocus: function (evt) {
			var isOk = this.$el[0] === evt.target || this.$el.has(evt.target).length || this.isSearch(evt.target);
			if (!isOk) {
				this.$el.focus();
			}
		},

		/**
		 * Closes modal if escape key is hit.
		 * @param evt {object} A jQuery-normalized event object.
		 */
		maybeClose: function (evt) {
			if (ESCAPE === evt.keyCode && !this.isSearch(evt.target)) {
				this.closeModal(evt);
			}
		},

		isSearch: function (el) {
			return this.$search[0] === el || this.$search.has(el).length;
		},

		/**
		 * Closes the modal and cleans up after the instance.
		 * @param evt {object} A jQuery-normalized event object.
		 */
		closeModal: function (evt) {
			evt.preventDefault();
			this.resetMetaboxView();
			this.undelegateEvents();
			$(document).off('focusin');
			$(document).off('keyup', this.maybeClose);
			$(document.body).removeClass('gc-modal-open');
			this.remove();

			gc.$id('bulk-edit').find('button.cancel').trigger('click');
			app.modalView = undefined;
		},

		clickSelectTab: function (evt) {
			evt.preventDefault();

			this.selectTab($(evt.target).data('id'));
		},

		selectTab: function (id) {
			this.currID = id;
			this.currNav = this.navItems.getById(id);
			this.navItems.trigger('activate', id);
		},

		checkEnableButton: function (btnEnabled) {
			this.buttonStatus(btnEnabled);
		},

		buttonStatus: function (enable) {
			if (this.collection.processing) {
				return;
			}
			if (!enable) {
				this.$('.media-toolbar button').prop('disabled', true);
			} else {
				this.$('#gc-btn-assign-mapping').prop('disabled', !this.collection.checkedCan('assign'));
				this.$('#gc-btn-push').prop('disabled', !this.collection.checkedCan('push'));
				this.$('#gc-btn-pull').prop('disabled', !this.collection.checkedCan('pull'));
			}
		},

		startPull: function (evt) {
			evt.preventDefault();
			this.startSync('pull');
		},

		startPush: function (evt) {
			evt.preventDefault();
			this.startSync('push');
		},

		startSync: function (direction) {
			var toCheck = 'push' === direction ? 'canPush' : 'canPull';
			var selected = this.selectiveGet(toCheck);

			if (window.confirm(gc._sure[direction])) {
				selected = _.map(selected, function (model) {
					model.set('mappingStatus', 'starting');
					return model.toJSON();
				});

				this.doAjax(selected, direction);
			}
		},

		startAssignment: function (evt) {
			var postIds = _.map(this.selectiveGet('disabled'), function (model) {
				return model.get('id');
			});

			this.resetMetaboxView();

			this.$el.addClass('gc-set-mapping');

			this.$('#gc-btn-assign-mapping').prop('disabled', true);

			this.metaboxView = this.initMetaboxView(postIds);
			this.listenTo(this.metaboxView, 'cancel', this.maybeResetMetaboxView);
			this.listenTo(this.metaboxView, 'complete', function (model, data) {
				model.set('waiting', true);

				this.collection.map(function (model) {
					if (model.get('id') in data.ids) {
						model.set('mapping', data.mapping);
						model.set('mappingName', data.mappingName);
						model.set('mappingLink', data.mappingLink);
					}
				});

				this.render();
			});

		},

		maybeResetMetaboxView: function () {
			if (this.metaboxView) {
				this.resetMetaboxView();
				this.buttonStatus(true);
			}
		},

		resetMetaboxView: function () {
			if (this.metaboxView) {
				this.stopListening(this.metaboxView);
				this.metaboxView.close();
				this.$el.removeClass('gc-set-mapping');
			}
		},

		selectiveGet: function (toCheck) {
			var selected = [];
			var staysChecked;

			this.collection.trigger('checkSome', function (model) {
				staysChecked = model.get('checked') && model.get(toCheck);
				if (staysChecked) {
					selected.push(model);
				}

				return staysChecked;
			});

			return selected;
		},

		getChecked: function (cb) {
			this.collection.filter(function (model) {
				var shouldGet = model.get('checked');
				if (shouldGet && cb) {
					cb(model);
				}
				return shouldGet;
			});
		},

		ajaxSuccess: function (response) {
			if (!response.data.mappings) {
				return this.ajaxFail();
			}

			var mappings = [];

			var toCheck = 'push' === response.data.direction ? 'canPush' : 'canPull';
			var checked = this.getChecked(function (model) {
				if (!model.get(toCheck)) {
					return;
				}

				if (response.data.mappings.length && -1 !== _.indexOf(response.data.mappings, model.get('mapping'))) {
					model.set('mappingStatus', 'syncing');
					mappings.push(model.get('mapping'));
				} else {
					model.set('checked', false);
					model.set('mappingStatus', 'complete');
					model.fetch().done(function () {
						model.trigger('render');
					});
				}
			});

			if (!mappings.length) {
				return this.clearTimeout();
			}

			this.checkStatus(mappings, response.data.direction);
		},

		ajaxFail: function (response) {
			this.setSelectedMappingStatus('failed');
			this.clearTimeout();
		},

		setSelectedMappingStatus: function (status) {
			return this.getChecked(function (model) {
				model.set('mappingStatus', status);
			});
		},

		checkStatus: function (mappings, direction) {
			this.clearTimeout();
			this.setTimeout(function () {
				thisView.doAjax({check: mappings}, direction);
			});
		},

		doAjax: function (formData, direction) {
			this.ajax.set( 'action', 'cwby_'+ direction +'_items' );

			this.ajax.send(
				formData,
				this.ajaxSuccess.bind(this),
				0,
				this.ajaxFail.bind(this)
			);
		},

		maybeRender: function () {
			if (!this.metaboxView) {
				this.render();
			}
		},

	});
};
