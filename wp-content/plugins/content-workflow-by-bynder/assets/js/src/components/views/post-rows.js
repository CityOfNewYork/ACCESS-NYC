module.exports = function (app, gc, $) {
	var thisView;
	return app.views.statusSelect2.extend({
		template: wp.template('gc-status-select2'),

		el: '#posts-filter tbody',

		width: '200px',

		initialize: function () {
			thisView = this;
			this.listenTo(this, 'quickEdit', this.edit);
			this.listenTo(this, 'quickEditSend', this.sending);
			this.render();
			this.updatePosts();
		},

		updatePosts: function () {
			// Trigger an un-cached update for the posts
			$.post(window.ajaxurl, {
				action      : 'cwby_get_posts',
				posts: gc._posts,
				flush_cache: gc.queryargs.flush_cache ? 1 : 0
			}, function (response) {
				if (response.success, response.data) {
					thisView.collection.trigger('updateItems', response.data);
				}
			});
		},

		sending: function (request, settings) {
			var data = this.parseQueryString(settings.data);
			if (data.post_ID && data.gc_status) {
				var model = this.collection.getById(data.post_ID);

				var status = _.find(model.get('statuses'), function (status) {
					return parseInt(status.id, 10) === parseInt(data.gc_status, 10);
				});

				model.set('status', status);
			}
		},

		edit: function (id, inlineEdit) {
			// get the post ID
			var postId = 0;
			if ('object' === typeof (id)) {
				postId = parseInt(inlineEdit.getId(id), 10);
			}

			this.waitSpinner(postId);

			if (!postId) {
				return;
			}

			var model = this.collection.getById(postId);

			if (model.get('statusesChecked')) {
				return this.renderStatuses(model);
			}

			$.post(window.ajaxurl, {
				action      : 'cwby_get_post_statuses',
				postId: postId,
				flush_cache: gc.queryargs.flush_cache ? 1 : 0
			}, this.ajaxResponse).done(function () {
				thisView.renderStatuses(model);
			});
		},

		ajaxResponse: function (response) {
			if (!response.data) {
				return;
			}

			var model = thisView.collection.getById(response.data.postId);
			if (!model) {
				return;
			}

			model.set('statusesChecked', true);

			if (response.success) {
				model.set('statuses', response.data.statuses);

				if (model.get('statuses').length) {
					thisView.$('.gc-select2').each(function () {
						$(this).select2('destroy');
					});

					thisView.renderStatuses(model);
				}
			}

		},

		renderStatuses: function (model) {
			var postId = model.get('id');
			this.editSelect(postId).html(this.template(model.toJSON()));
			if (model.get('statuses').length) {
				this.renderSelect2(gc.$id('edit-' + postId));
			}
		},

		waitSpinner: function (postId) {
			this.editSelect(postId).html('<span class="spinner"></span>');
		},

		editSelect: function (postId) {
			return gc.$id('edit-' + postId).find('.inline-edit-group .gc-status-select2');
		},

		render: function () {
			this.collection.each(function (model) {
				(new app.views.postRow({model: model})).render();
			});
			return this;
		},

		/**
		 * Parse query string.
		 * ?a=b&c=d to {a: b, c: d}
		 * @param {String} (option) queryString
		 * @return {Object} query params
		 */
		parseQueryString: function (string) {
			if (!string) {
				return {};
			}
			return _
				.chain(string.split('&'))
				.map(function (params) {
					var p = params.split('=');
					return [p[0], decodeURIComponent(p[1])];
				})
				.object()
				.value();
		}

	});
};
