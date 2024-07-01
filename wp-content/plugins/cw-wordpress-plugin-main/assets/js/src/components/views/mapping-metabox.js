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

		initialize: function () {
			thisView = this;
			this.listenTo(this.model, 'change:waiting', this.toggleWaitingRender);
			this.listenTo(this.model, 'change', this.maybeEnableAndRender);
			this.listenTo(this.model, 'change:step', this.changeStep);
			this.listenTo(this, 'cancel', this.resetAndRender);
			this.render();
			this.$el.removeClass('no-js').addClass('gc-mapping-metabox');
		},

		changeStep: function (model) {
			if ('accounts' === model.changed.step) {
				this.$el.addClass('gc-mapping-started');
			}

			if (model.changed.step) {
				this.stepArgs = this['step_' + model.changed.step]();
			}
		},

		setProperty: function (evt) {
			var value = $(evt.target).val();

			this.model.set(this.stepArgs.property, value);

			if ('account' === this.stepArgs.property || 'project' === this.stepArgs.property) {
				// Autoclick "next" for user.
				this.step();
			}
		},

		setMapping: function () {
			var success = function (data) {
				this.model.set('waiting', false);

				// Goodbye
				this.trigger('complete', this.model, data);
			};

			this.ajax({
				action : 'cwby_save_mapping_id',
			}, success, this.failMsg);
		},

		maybeEnableAndRender: function (model) {
			if (model.changed.account || model.changed.project || model.changed.mapping) {
				this.model.set('btnDisabled', false);
				this.render();
			}
		},

		toggleWaitingRender: function (model) {
			if (model.changed.waiting) {
				this.model.set('btnDisabled', true);
			}
			this.render();
		},

		step: function () {
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
					action   : 'cwby_wp_filter_mappings',
					property: this.stepArgs.property
				}, this.successHandler, this.failMsg);

			}

			return this;
		},

		failMsg: function (msg) {
			msg = 'string' === typeof msg ? msg : gc._errors.unknown;
			window.alert(msg);
			thisView.model.set('waiting', false);
		},

		successHandler: function (objects) {
			this.model.set(this.stepArgs.properties, objects);
			if (objects.length < 2) {
				this.model.set('btnDisabled', false);
			}
			this.model.set('waiting', false);
		},

		setStep: function () {
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

		step_accounts: function () {
			return {
				property: 'account',
				properties: 'accounts',
			};
		},

		step_projects: function () {
			return {
				property: 'project',
				properties: 'projects',
			};
		},

		step_mappings: function () {
			return {
				property: 'mapping',
				properties: 'mappings',
			};
		},

		cancel: function (evt) {
			this.trigger('cancel', evt);
		},

		resetModel: function () {
			this.stepArgs = false;
			this.model.set({
				'step': false,
				'account': 0,
				'project': 0,
				'mapping': 0,
			});
			return this.model;
		},

		resetAndRender: function () {
			this.resetModel();
			this.render();
		},

		render: function () {
			var json = this.model.toJSON();
			if (this.stepArgs) {
				json.label = gc._step_labels[json.step];
				json.property = this.stepArgs.property;
			}
			this.$el.html(this.template(json));
			return this;
		},

	});
};
