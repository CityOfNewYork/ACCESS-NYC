/**
 * GatherContent Plugin - v3.1.13 - 2019-05-22
 * http://www.gathercontent.com
 *
 * Copyright (c) 2019 GatherContent
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
'use strict';

module.exports = function (app) {
	app.models = { base: require('./models/base.js') };
	app.collections = { base: require('./collections/base.js') };
	app.views = { base: require('./views/base.js') };
};

},{"./collections/base.js":1,"./models/base.js":3,"./views/base.js":7}],3:[function(require,module,exports){
"use strict";

module.exports = Backbone.Model.extend({
	sync: function sync() {
		return false;
	}
});

},{}],4:[function(require,module,exports){
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

},{}],5:[function(require,module,exports){
'use strict';

module.exports = function (gc) {
	return require('./../models/modify-json.js')(Backbone.Model.extend({
		defaults: {
			id: 0,
			item: 0,
			itemName: '',
			updated_at: '',
			current: true,
			editLink: '',
			mapping: 0,
			mappingName: '',
			mappingLink: '',
			mappingStatus: '',
			mappingStatusId: '',
			status: {},
			checked: false,
			disabled: false,
			canPull: false,
			canPush: false,
			statuses: [],
			statusesChecked: false,
			ptLabel: false
		},

		searchAttributes: ['itemName', 'mappingName', 'post_title'],

		url: function url() {
			var url = window.ajaxurl + '?action=gc_fetch_js_post&id=' + this.get('id');
			if (this.get('uncached')) {
				this.set('uncached', false);
				url += '&flush_cache=force';
			}
			return url;
		},

		_get_disabled: function _get_disabled(value) {
			return !this.get('mapping');
		},

		_get_canPull: function _get_canPull(value) {
			return this.get('item') > 0 && this.get('mapping') > 0;
		},

		_get_canPush: function _get_canPush(value) {
			return this.get('mapping') > 0;
		},

		_get_mappingLink: function _get_mappingLink(value) {
			if ('failed' === Backbone.Model.prototype.get.call(this, 'mappingStatus')) {
				value += '&sync-items=1';
			}
			return value;
		},

		_get_mappingStatus: function _get_mappingStatus(value) {
			return gc._statuses[value] ? gc._statuses[value] : '';
		},

		_get_mappingStatusId: function _get_mappingStatusId(value) {
			return Backbone.Model.prototype.get.call(this, 'mappingStatus');
		}
	}));
};

},{"./../models/modify-json.js":4}],6:[function(require,module,exports){
'use strict';

window.GatherContent = window.GatherContent || {};

(function (window, document, $, gc, undefined) {
	'use strict';

	gc.single = gc.single || {};
	var app = gc.single;

	// Initiate base objects.
	require('./initiate-objects.js')(app);

	/*
  * Posts
  */

	app.models.post = require('./models/post.js')(gc);
	app.views.statusSelect2 = require('./views/status-select2.js')(app);

	app.init = function () {
		if (gc._post.mapping) {
			app.views.metabox = require('./views/metabox.js')(app, $, gc);
			app.metaboxView = new app.views.metabox({
				model: new app.models.post(gc._post)
			});
		} else {
			app.views.metabox = require('./views/mapping-metabox.js')(app, $, gc);
			app.metaboxView = new app.views.metabox({
				model: new app.models.post(gc._post)
			});
			app.metaboxView.on('complete', app.reinit);
		}
	};

	app.reinit = function (model) {
		app.metaboxView.unbind();
		if (app.metaboxView.onClose) {
			app.metaboxView.onClose();
		}

		app.views.metabox = require('./views/metabox.js')(app, $, gc);
		app.metaboxView = new app.views.metabox({
			model: model
		});
	};

	// Kick it off.
	$(app.init);
})(window, document, jQuery, window.GatherContent);

},{"./initiate-objects.js":2,"./models/post.js":5,"./views/mapping-metabox.js":8,"./views/metabox.js":11,"./views/status-select2.js":12}],7:[function(require,module,exports){
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

},{}],8:[function(require,module,exports){
'use strict';

module.exports = function (app, $, gc) {
	var thisView;
	var base = require('./../views/metabox-base.js')(app, $, gc);
	return base.extend({
		template: wp.template('gc-mapping-metabox'),
		stepArgs: false,
		events: {
			'click #gc-map': 'step',
			'change #select-gc-next-step': 'setProperty',
			'click #gc-map-cancel': 'cancel'
		},

		initialize: function initialize() {
			thisView = this;
			this.listenTo(this.model, 'change:waiting', this.toggleWaitingRender);
			this.listenTo(this.model, 'change', this.maybeEnableAndRender);
			this.listenTo(this.model, 'change:step', this.changeStep);
			this.listenTo(this, 'cancel', this.resetAndRender);
			this.render();
			this.$el.removeClass('no-js').addClass('gc-mapping-metabox');
		},

		changeStep: function changeStep(model) {
			if ('accounts' === model.changed.step) {
				this.$el.addClass('gc-mapping-started');
			}

			if (model.changed.step) {
				this.stepArgs = this['step_' + model.changed.step]();
			}
		},

		setProperty: function setProperty(evt) {
			var value = $(evt.target).val();

			this.model.set(this.stepArgs.property, value);

			if ('account' === this.stepArgs.property || 'project' === this.stepArgs.property) {
				// Autoclick "next" for user.
				this.step();
			}
		},

		setMapping: function setMapping() {
			var success = function success(data) {
				this.model.set('waiting', false);

				// Goodbye
				this.trigger('complete', this.model, data);
			};

			this.ajax({
				action: 'gc_save_mapping_id'
			}, success, this.failMsg);
		},

		maybeEnableAndRender: function maybeEnableAndRender(model) {
			if (model.changed.account || model.changed.project || model.changed.mapping) {
				this.model.set('btnDisabled', false);
				this.render();
			}
		},

		toggleWaitingRender: function toggleWaitingRender(model) {
			if (model.changed.waiting) {
				this.model.set('btnDisabled', true);
			}
			this.render();
		},

		step: function step() {
			this.model.set('waiting', true);

			if ('mapping' === this.stepArgs.property) {
				return this.setMapping();
			}

			this.setStep();

			var properties = this.model.get(this.stepArgs.properties);

			if (properties && properties.length) {

				this.successHandler(properties);
			} else {

				this.ajax({
					action: 'gc_wp_filter_mappings',
					property: this.stepArgs.property
				}, this.successHandler, this.failMsg);
			}

			return this;
		},

		failMsg: function failMsg(msg) {
			msg = 'string' === typeof msg ? msg : gc._errors.unknown;
			window.alert(msg);
			thisView.model.set('waiting', false);
		},

		successHandler: function successHandler(objects) {
			this.model.set(this.stepArgs.properties, objects);
			if (objects.length < 2) {
				this.model.set('btnDisabled', false);
			}
			this.model.set('waiting', false);
		},

		setStep: function setStep() {
			if (!this.model.get('step')) {
				return this.model.set('step', 'accounts');
			}

			if ('accounts' === this.model.get('step')) {
				return this.model.set('step', 'projects');
			}

			if ('projects' === this.model.get('step')) {
				return this.model.set('step', 'mappings');
			}
		},

		step_accounts: function step_accounts() {
			return {
				property: 'account',
				properties: 'accounts'
			};
		},

		step_projects: function step_projects() {
			return {
				property: 'project',
				properties: 'projects'
			};
		},

		step_mappings: function step_mappings() {
			return {
				property: 'mapping',
				properties: 'mappings'
			};
		},

		cancel: function cancel(evt) {
			this.trigger('cancel', evt);
		},

		resetModel: function resetModel() {
			this.stepArgs = false;
			this.model.set({
				'step': false,
				'account': 0,
				'project': 0,
				'mapping': 0
			});
			return this.model;
		},

		resetAndRender: function resetAndRender() {
			this.resetModel();
			this.render();
		},

		render: function render() {
			var json = this.model.toJSON();
			if (this.stepArgs) {
				json.label = gc._step_labels[json.step];
				json.property = this.stepArgs.property;
			}
			this.$el.html(this.template(json));
			return this;
		}

	});
};

},{"./../views/metabox-base.js":9}],9:[function(require,module,exports){
'use strict';

module.exports = function (app, $, gc) {
	return app.views.base.extend({
		el: '#gc-related-data',

		ajax: function ajax(args, successcb, failcb) {
			var view = this;
			var success = function success(response) {
				if (response.success) {
					successcb.call(view, response.data);
				} else if (failcb) {
					failcb.call(view, response.data);
				}
			};

			var promise = $.post(window.ajaxurl, $.extend({
				action: '',
				post: this.model.toJSON(),
				nonce: gc.$id('gc-edit-nonce').val(),
				flush_cache: gc.queryargs.flush_cache ? 1 : 0
			}, args), success);

			if (failcb) {
				promise.fail(function () {
					failcb.call(view);
				});
			}

			return promise;
		}
	});
};

},{}],10:[function(require,module,exports){
'use strict';

module.exports = function (app, $, gc) {
	var thisView;
	return app.views.statusSelect2.extend({
		className: 'misc-pub-section',
		select2template: wp.template('gc-status-select2'),
		template: wp.template('gc-metabox-statuses'),
		isOpen: false,
		rendered: false,

		initialize: function initialize() {
			thisView = this;
			this.listenTo(this, 'render', this.render);
			this.listenTo(this, 'statusesOpen', this.statusesOpen);
			this.listenTo(this, 'statusesClose', this.statusesClose);
		},

		statusesOpen: function statusesOpen() {
			this.isOpen = true;
			if (!this.model.get('statusesChecked')) {
				this.asyncInit();
			}
			this.$('.edit-gc-status').addClass('hidden');
			this.$('#gc-post-status-select').slideDown('fast' /*, function() {
                                                     thisView.$( '#gc-set-status' ).focus();
                                                     }*/);
		},

		statusesClose: function statusesClose() {
			this.isOpen = false;
			this.$('.edit-gc-status').removeClass('hidden');
			this.$('#gc-post-status-select').slideUp('fast');
		},

		asyncInit: function asyncInit() {
			this.rendered = false;
			$.post(window.ajaxurl, {
				action: 'gc_get_post_statuses',
				postId: this.model.get('id'),
				flush_cache: gc.queryargs.flush_cache ? 1 : 0
			}, this.ajaxResponse.bind(this)).done(function () {
				thisView.firstToRender();
			}).fail(function () {
				thisView.model.set('statusesChecked', false);
			});

			this.model.set('statusesChecked', true);
		},

		ajaxResponse: function ajaxResponse(response) {
			if (!response.data || !response.success) {
				this.model.set('statusesChecked', false);
				return;
			}

			this.model.set('statusesChecked', true);
			this.model.set('statuses', response.data.statuses);

			if (this.model.get('statuses').length) {
				thisView.$('.gc-select2').each(function () {
					$(this).select2('destroy');
				});

				thisView.firstToRender();
			}
		},

		firstToRender: function firstToRender() {
			if (!thisView.rendered) {
				thisView.renderStatuses();
				thisView.rendered = true;
			}
		},

		renderStatuses: function renderStatuses() {
			var postId = this.model.get('id');
			this.$('#gc-status-selec2').html(this.select2template(this.model.toJSON()));
			if (this.model.get('statuses').length) {
				this.renderSelect2(this.$el);
			}
		},

		render: function render() {
			this.$el.html(this.template(this.model.toJSON()));
			if (this.model.get('statusesChecked')) {
				thisView.renderStatuses();
			}
			return this;
		}

	});
};

},{}],11:[function(require,module,exports){
'use strict';

module.exports = function (app, $, gc) {
	var thisView;
	var base = require('./../views/metabox-base.js')(app, $, gc);
	var StatusesView = require('./../views/metabox-statuses.js')(app, $, gc);

	return base.extend({
		template: wp.template('gc-metabox'),
		statusesView: null,
		timeoutID: null,
		events: {
			'click .edit-gc-status': 'editStatus',
			'click .cancel-gc-status': 'cancelEditStatus',
			'click .save-gc-status': 'saveStatus',
			'click #gc-pull': 'pull',
			'click #gc-push': 'push',
			'click #gc-disconnect': 'disconnect'
		},

		initialize: function initialize() {
			thisView = this;
			this.listenTo(this.model, 'change:status', this.renderStatusView);
			this.listenTo(this.model, 'change:mappingStatus', this.render);
			this.listenTo(this.model, 'render', this.render);

			this.statusesView = new StatusesView({
				model: this.model
			});

			this.render();
			this.$el.removeClass('no-js');

			this.refreshData();
		},

		refreshData: function refreshData() {
			// Trigger an un-cached update for the item data
			this.model.set('uncached', true);
			this.model.fetch().done(function (data) {
				if (!thisView.statusesView.isOpen) {
					thisView.render();
				}
			});
		},

		updateModel: function updateModel(data) {
			var id = this.model.get('id');
			if (id in data) {
				if (data[id].status) {
					this.model.set('status', data[id].status);
				}
				if (data[id].itemName) {
					this.model.set('itemName', data[id].itemName);
				}
				if (data[id].updated_at) {
					this.model.set('updated_at', data[id].updated_at);
				}
			}
		},

		editStatus: function editStatus(evt) {
			evt.preventDefault();
			this.statusesView.trigger('statusesOpen');
		},

		cancelEditStatus: function cancelEditStatus(evt) {
			evt.preventDefault();
			this.statusesView.trigger('statusesClose');
		},

		saveStatus: function saveStatus() {
			var newStatusId = this.$('.gc-default-mapping-select').val();
			var oldStatus = this.model.get('status');
			var oldStatusId = oldStatus && oldStatus.id ? oldStatus.id : false;
			var newStatus, statuses;

			if (newStatusId === oldStatusId) {
				return this.statusesView.trigger('statusesClose');
			}

			statuses = this.model.get('statuses');
			newStatus = _.find(statuses, function (status) {
				return parseInt(newStatusId, 10) === parseInt(status.id, 10);
			});

			this.statusesView.trigger('statusesClose');
			this.model.set('status', newStatus);

			this.ajax({
				action: 'set_gc_status',
				status: newStatusId
			}, this.refreshData, function () {
				this.model.set('status', oldStatus);
			});
		},

		disconnect: function disconnect() {
			if (window.confirm(gc._sure.disconnect)) {
				thisView.model.set('mappingStatus', 'starting');
				this.ajax({
					action: 'gc_disconnect_post',
					data: thisView.model.toJSON(),
					nonce: gc._edit_nonce
				}, this.disconnectResponse, this.syncFail);
			}
		},

		pull: function pull() {
			if (window.confirm(gc._sure.pull)) {
				thisView.model.set('mappingStatus', 'starting');
				this.doSync('pull');
			}
		},

		push: function push() {
			var msg = this.model.get('item') ? gc._sure.push : gc._sure.push_no_item;
			if (window.confirm(msg)) {
				thisView.model.set('mappingStatus', 'starting');
				this.doSync('push');
			}
		},

		syncFail: function syncFail(msg) {
			msg = 'string' === typeof msg ? msg : gc._errors.unknown;
			window.alert(msg);
			this.model.set('mappingStatus', 'failed');
			this.clearTimeout();
		},

		disconnectResponse: function disconnectResponse(data) {
			this.clearTimeout();
			this.$el.html(wp.template('gc-mapping-metabox'));
		},

		syncResponse: function syncResponse(data) {
			if (data.mappings) {
				if (data.mappings.length && -1 !== _.indexOf(data.mappings, this.model.get('mapping'))) {

					this.model.set('mappingStatus', 'syncing');
					this.checkStatus(data.direction);
				} else {
					this.finishedSync(data.direction);
				}
			} else {
				this.syncFail(data);
			}
		},

		doSync: function doSync(direction, data) {
			this.ajax({
				action: 'gc_' + direction + '_items',
				// action : 'glsjlfjs',
				data: data || [this.model.toJSON()],
				nonce: gc._edit_nonce
			}, this.syncResponse, this.syncFail);
		},

		finishedSync: function finishedSync(direction) {
			this.clearTimeout();
			this.model.set('mappingStatus', 'complete');
			if ('push' === direction) {
				window.setTimeout(function () {
					// Give DB time to catch up, and avoid race condtions.
					thisView.refreshData();
				}, 800);
			} else {
				window.location.href = window.location.href;
			}
		},

		checkStatus: function checkStatus(direction) {
			this.clearTimeout();
			this.timeoutID = window.setTimeout(function () {
				thisView.doSync(direction, { check: [thisView.model.get('mapping')] });
			}, 1000);
		},

		clearTimeout: function clearTimeout() {
			window.clearTimeout(this.timeoutID);
			this.timeoutID = null;
		},

		render: function render() {
			this.$el.html(this.template(this.model.toJSON()));

			// This needs to happen after rendering.
			this.$('.misc-pub-section.gc-item-name').after(this.statusesView.render().el);

			return this;
		},

		renderStatusView: function renderStatusView() {
			this.statusesView.$el.replaceWith(this.statusesView.render().el);
		}

	});
};

},{"./../views/metabox-base.js":9,"./../views/metabox-statuses.js":10}],12:[function(require,module,exports){
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

},{}]},{},[6]);
