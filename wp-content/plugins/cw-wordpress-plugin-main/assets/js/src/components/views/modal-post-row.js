module.exports = function (app, gc) {
	var item = require('./../views/item.js')(app);
	return item.extend({
		template: wp.template('gc-item'),

		id: function () {
			return 'gc-modal-post-' + this.model.get('id');
		},

		className: function () {
			return 'gc-item ' + (this.model.get('disabled') ? 'gc-disabled' : 'gc-enabled');
		},

		events: {
			'change .gc-check-column input': 'toggleCheck',
			'click .gc-status-column': 'toggleCheckAndRender',
		},

		initialize: function () {
			this.listenTo(this.model, 'change:post_title', this.renderTitle);
			this.listenTo(this.model, 'change:mappingStatus', this.render);
			this.listenTo(this.model, 'render', this.render);
		},

		renderTitle: function () {
			var title = this.model.get('post_title');
			var id = this.model.get('id');
			gc.$id('post-' + id).find('.column-title .row-title').text(title);
			gc.$id('edit-' + id).find('[name="post_title"]').text(title);
			gc.$id('inline_' + id).find('.post_title').text(title);
		},

	});
};
