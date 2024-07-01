module.exports = function (app, $, gc) {
	var thisView;
	var percent = gc.percent;
	app.views.tableSearch = require('./../views/table-search.js')(app, $, gc);
	app.views.tableNav = require('./../views/table-nav.js')(app, $, gc);

	return app.views.tableBase.extend({
		template: wp.template('gc-items-sync'),
		progressTemplate: wp.template('gc-items-sync-progress'),
		modelView: app.views.item,

		events: {
			'click .gc-cancel-sync': 'clickCancelSync',
			'click .gc-field-th.sortable': 'sortRowsByColumn',
			'change .gc-field-th.gc-check-column input': 'checkAll',
			'submit form': 'submit'
		},

		initialize: function () {
			thisView = this;
			app.views.tableBase.prototype.initialize.call(this);

			this.listenTo(this.ajax, 'response', this.ajaxResponse);
			this.listenTo(this.collection, 'enabledChange', this.checkEnableButton);
			this.listenTo(this.collection, 'search', this.initRender);

			this.initRender();
		},

		setupAjax: function () {
			var Ajax = require('./../models/ajax.js')(app, {
				checkHits: 0,
				time: 500,
				nonce: gc.el('_wpnonce').value,
				id: gc.el('gc-input-mapping_id').value,
				flush_cache: gc.queryargs.flush_cache ? 1 : 0
			});

			this.ajax = new Ajax({
				percent: percent
			});
		},

		checkEnableButton: function (syncEnabled) {
			this.buttonStatus(syncEnabled);
		},

		clickCancelSync: function (evt) {
			evt.preventDefault();
			this.cancelSync();
		},

		submit: function (evt) {
			evt.preventDefault();
			this.startSync(this.$('form').serialize());
		},

		startSync: function (formData) {
			this.doSpinner();
			this.ajax.reset().set('stopSync', false);
			this.renderProgress(100 === window.parseInt(percent, 10) ? 0 : percent);
			this.doAjax(formData, percent);
		},

		cancelSync: function (url) {
			percent = null;

			this.ajax.reset();
			this.clearTimeout();

			if (url) {
				this.doAjax('cancel', 0, function () {
					window.location.href = url;
				});
			} else {
				this.doAjax('cancel', 0, function () {
				});
				this.initRender();
			}
		},

		doAjax: function (formData, completed, cb) {
			cb = cb || this.ajaxSuccess.bind(this);
			this.ajax.send(formData, cb, completed);
		},

		ajaxSuccess: function (response) {
			if (this.ajax.get('stopSync')) {
				return;
			}

			percent = response.data.percent || 1;
			var hits = this.checkHits();
			var time = this.ajax.get('time');

			if (hits > 25 && time < 2000) {
				this.clearTimeout();
				this.ajax.set('time', 2000);
			} else if (hits > 50 && time < 5000) {
				this.clearTimeout();
				this.ajax.set('time', 5000);
			}

			this.setTimeout(this.checkProgress.bind(this));

			if (percent > 99) {
				// This is to allow the slight css animation.
				this.renderProgressUpdate(100);

				// This is to render the loading spinner. Wait long enough for css animation to copmlete.
				window.setTimeout(function () {
					thisView.renderProgress(100, true);
				}, 100);

				// Finally, cancel the sync, and redirect.
				this.cancelSync(window.location.href + '&updated=1&flush_cache=1&redirect=1');
			} else {
				this.renderProgressUpdate(percent);
			}
		},

		setTimeout: function (callback) {
			this.timeoutID = window.setTimeout(callback, this.ajax.get('time'));
		},

		checkProgress: function () {
			this.doAjax('check', percent);
		},

		checkHits: function () {
			return window.parseInt(this.ajax.get('checkHits'), 10);
		},

		ajaxResponse: function (response, formData) {
			gc.log('warn', 'hits/interval/response: ' + this.checkHits() + '/' + this.ajax.get('time') + '/', response.success ? response.data : response);

			if ('check' === formData) {
				this.ajax.set('checkHits', this.checkHits() + 1);
			} else if (response.data) {
				this.ajax.set('checkHits', 0);
			}

			if (!response.success) {
				this.renderProgressUpdate(0);
				this.cancelSync();
				if (response.data) {
					if (response.data.url) {
						window.alert(response.data.message);
						window.location.href = response.data.url;
					} else {
						window.alert(response.data);
					}
					return;
				}
			}
		},

		renderProgressUpdate: function (percent) {
			this.$('.gc-progress-bar-partial')
				.css({width: percent + '%'})
				.find('span').text(percent + '%');
		},

		renderProgress: function (percent, showLoader) {
			this.$el.addClass('gc-sync-progress');
			this.buttonStatus(false);
			this.$('#sync-tabs').html(this.progressTemplate({
				percent: null === percent ? 0 : percent,
				loader: true === showLoader
			}));
		},

		renderRows: function (html) {
			this.$('#sync-tabs tbody').html(html || this.getRenderedRows());
		},

		sortRender: function () {
			this.initRender();
		},

		initRender: function () {
			var collection = this.collection.current();
			// If sync is going, show that status.
			if (percent > 0 && percent < 100) {
				this.startSync('check');
			} else {
				this.$('#sync-tabs').html(this.template({
					checked: collection.allChecked,
					sortKey: collection.sortKey,
					sortDirection: collection.sortDirection,
				}));
				this.render();
			}
		},

		render: function () {
			// Not syncing, so remove wrap-class
			this.$el.removeClass('gc-sync-progress');

			return app.views.tableBase.prototype.render.call(this);
		},

	});
};
