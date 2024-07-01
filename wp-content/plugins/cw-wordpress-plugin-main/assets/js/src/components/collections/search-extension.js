module.exports = function (Collection) {

	_.extend(Collection.prototype, {

		//_Cache
		_searchResults: null,

		//@ Search wrapper function
		search: function (keyword, attributes) {
			var results = this._doSearch(keyword, attributes);

			this.trigger('search', results);

			// For use of returning un-async
			return results;
		},

		//@ Search function
		_doSearch: function (keyword, attributes) {
			attributes = attributes && attributes.length ? attributes : false;

			// If collection empty get out
			if (!this.models.length) {
				return [];
			}

			// Filter
			var matcher = this.matcher;
			var results = !keyword ? this.models : this.filter(function (model) {
				attributes = attributes ? attributes : model.searchAttributes || _.keys(model.attributes);
				return _.some(attributes, function (attribute) {
					return matcher(keyword, model.get(attribute));
				});
			});

			this.trigger('searchResults', results);

			// Instantiate new Collection
			var collection = new Collection(results, {reinit: true});

			collection.searching = {
				keyword: keyword,
				attributes: attributes
			};

			collection.getSearchQuery = function () {
				return this.searching;
			};

			// Cache the recently searched metadata
			this._searchResults = collection;

			this.trigger('search', collection);

			// For use of returning un-async
			return collection;
		},

		//@ Default Matcher - may be overwritten
		matcher: function (needle, haystack) {
			if (!needle || !haystack) {
				return;
			}
			needle = needle.toString().toLowerCase();
			haystack = haystack.toString().toLowerCase();
			return haystack.indexOf(needle) >= 0;
		},

		//@ Get recent search value
		getSearchValue: function () {
			return this.getSearchQuery().keyword;
		},

		//@ Get recent search query
		getSearchQuery: function () {
			return this._searchResults && this._searchResults.getSearchQuery() || {};
		},

		//@ Get recent search results
		getSearchResults: function () {
			return this._searchResults;
		},

		current: function () {
			return this._searchResults || this;
		}

	});

	return Collection;
};
