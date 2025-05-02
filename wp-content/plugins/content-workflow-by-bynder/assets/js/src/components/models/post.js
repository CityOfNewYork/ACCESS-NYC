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
			ptLabel: false,
		},

		searchAttributes: [
			'itemName',
			'mappingName',
			'post_title',
		],

		url: function () {
			var url = window.ajaxurl +'?action=cwby_fetch_js_post&id='+ this.get( 'id' );
			if (this.get('uncached')) {
				this.set('uncached', false);
				url += '&flush_cache=force';
			}
			return url;
		},

		_get_disabled: function (value) {
			return !this.get('mapping');
		},

		_get_canPull: function (value) {
			return this.get('item') > 0 && this.get('mapping') > 0;
		},

		_get_canPush: function (value) {
			return this.get('mapping') > 0;
		},

		_get_mappingLink: function (value) {
			if ('failed' === Backbone.Model.prototype.get.call(this, 'mappingStatus')) {
				value += '&sync-items=1';
			}
			return value;
		},

		_get_mappingStatus: function (value) {
			return gc._statuses[value] ? gc._statuses[value] : '';
		},

		_get_mappingStatusId: function (value) {
			return Backbone.Model.prototype.get.call(this, 'mappingStatus');
		}
	}));
};
