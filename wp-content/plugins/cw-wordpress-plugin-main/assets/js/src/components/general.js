window.GatherContent = window.GatherContent || {};

(function (window, document, $, gc, undefined) {
	'use strict';

	gc.general = gc.general || {};
	var app = gc.general;

	// Initiate base objects.
	require('./initiate-objects.js')(app);

	/*
	 * Posts
	 */

	app.models.post = require('./models/post.js')(gc);
	app.collections.posts = require('./collections/posts.js')(app);
	app.views.postRow = require('./views/post-row.js')(app, gc);
	app.views.statusSelect2 = require('./views/status-select2.js')(app);
	app.views.postRows = require('./views/post-rows.js')(app, gc, $);

	/*
	 * Nav Items
	 */
	app.models.navItem = require('./models/modal-nav-item.js')(app);
	app.collections.navItems = require('./collections/modal-nav-items.js')(app);

	app.views.tableBase = require('./views/table-base.js')(app, $, gc);
	app.views.modalPostRow = require('./views/modal-post-row.js')(app, gc);
	app.views.modal = require('./views/modal.js')(app, gc, $);

	app.monkeyPatchQuickEdit = function (cb) {
		// we create a copy of the WP inline edit post function
		var edit = window.inlineEditPost.edit;

		// and then we overwrite the function with our own code
		window.inlineEditPost.edit = function () {
			// "call" the original WP edit function
			// we don't want to leave WordPress hanging
			edit.apply(this, arguments);

			// now we take care of our business
			cb.apply(this, arguments);
		};
	};

	app.triggerModal = function (evt) {
		evt.preventDefault();

		var posts = app.getChecked();
		if (!posts.length) {
			return;
		}

		if (app.modalView === undefined) {
			app.modalView = new app.views.modal({
				collection: app.generalView.collection
			});
			app.modalView.checked(posts);
			app.generalView.listenTo(app.modalView, 'updateModels', app.generalView.updatePosts);
		}
	};

	app.getChecked = function () {
		return $('tbody th.check-column input[type="checkbox"]:checked').map(function () {
			return parseInt($(this).val(), 10);
		}).get();
	};

	app.init = function () {
		$(document.body)
			.addClass('gathercontent-admin-select2')
			.on('click', '#gc-sync-modal', app.triggerModal);

		$(document).ajaxSend(function (evt, request, settings) {
			if (settings.data && -1 !== settings.data.indexOf('&action=inline-save')) {
				app.generalView.trigger('quickEditSend', request, settings);
			}
		});

		app.generalView = new app.views.postRows({
			collection: new app.collections.posts(gc._posts)
		});

		app.monkeyPatchQuickEdit(function () {
			app.generalView.trigger('quickEdit', arguments, this);
		});

	};

	$(app.init);

})(window, document, jQuery, window.GatherContent);
