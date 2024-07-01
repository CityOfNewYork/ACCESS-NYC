module.exports = function (app) {
	var thisView;
	return app.views.base.extend({
		select2ItemTemplate: wp.template('gc-select2-item'),
		width: '250px',

		renderSelect2: function ($context) {
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

		select2Args: function (data) {
			var args = {
				width: thisView.width
			};

			args.templateResult = function (status, showDesc) {
				var data = jQuery.extend(status, jQuery(status.element).data());
				data.description = false === showDesc ? false : (data.description || '');
				return jQuery(thisView.select2ItemTemplate(status));
			}.bind(thisView);

			args.templateSelection = function (status) {
				return args.templateResult(status, false);
			};

			return args;
		}

	});
};
