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
			'click #gc-disconnect': 'disconnect',
		},

		initialize: function () {
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

		refreshData: function () {
			// Trigger an un-cached update for the item data
			this.model.set('uncached', true);
			this.model.fetch().done(function (data) {
				if (!thisView.statusesView.isOpen) {
					thisView.render();
				}
			});
		},

		updateModel: function (data) {
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

		editStatus: function (evt) {
			evt.preventDefault();
			this.statusesView.trigger('statusesOpen');
		},

		cancelEditStatus: function (evt) {
			evt.preventDefault();
			this.statusesView.trigger('statusesClose');
		},

		saveStatus: function () {
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
				action : 'set_cwby_status',
				status: newStatusId,
			}, this.refreshData, function () {
				this.model.set('status', oldStatus);
			});
		},

		disconnect: function () {
			if (window.confirm(gc._sure.disconnect)) {
				thisView.model.set('mappingStatus', 'starting');
				this.ajax({
					action : 'cwby_disconnect_post',
					data: thisView.model.toJSON(),
					nonce: gc._edit_nonce,
				}, this.disconnectResponse, this.syncFail);
			}
		},

		pull: function () {
			if (window.confirm(gc._sure.pull)) {
				thisView.model.set('mappingStatus', 'starting');
				this.doSync('pull');
			}
		},

		push: function () {
			var msg = this.model.get('item') ? gc._sure.push : gc._sure.push_no_item;
			if (window.confirm(msg)) {
				thisView.model.set('mappingStatus', 'starting');
				this.doSync('push');
			}
		},

		syncFail: function (msg) {
			msg = 'string' === typeof msg ? msg : gc._errors.unknown;
			window.alert(msg);
			this.model.set('mappingStatus', 'failed');
			this.clearTimeout();
		},

		disconnectResponse: function (data) {
			this.clearTimeout();
			this.$el.html(wp.template('gc-mapping-metabox'));
		},

		syncResponse: function (data) {
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

		doSync: function (direction, data) {
			this.ajax({
				action : 'cwby_'+ direction +'_items',
				// action : 'glsjlfjs',
				data: data || [this.model.toJSON()],
				nonce: gc._edit_nonce,
			}, this.syncResponse, this.syncFail);
		},

		finishedSync: function (direction) {
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

		checkStatus: function (direction) {
			this.clearTimeout();
			this.timeoutID = window.setTimeout(function () {
				thisView.doSync(direction, {check: [thisView.model.get('mapping')]});
			}, 1000);
		},

		clearTimeout: function () {
			window.clearTimeout(this.timeoutID);
			this.timeoutID = null;
		},

		render: function () {
			this.$el.html(this.template(this.model.toJSON()));

			// This needs to happen after rendering.
			this.$('.misc-pub-section.gc-item-name').after(this.statusesView.render().el);

			return this;
		},

		renderStatusView: function () {
			this.statusesView.$el.replaceWith(this.statusesView.render().el);
		}


	});
};
