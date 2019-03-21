/* eslint-env browser */
'use strict';

/**
 * WordPress Archive Vue. Creates a filterable reactive interface using Vue.js
 * for a WordPress Archive. Uses the WordPress REST API for retrieving filters
 * and posts. Fully configurable for any post type (default or custom). Works
 * with multiple languages using the lang attribute set in on the htmt tag
 * and the multilingual url endpoint provided by WPML.
 */
class WpArchiveVue {
  /**
   * The constructor will merge user settings with default settings and
   * instantiate the Vue instance.
   * @param   {object}  Vue       Pre instantiated Vue object.
   * @param   {object}  settings  Configuration for the Vue application.
   * @return  {object}            The instantiated Vue application.
   */
  constructor(Vue, settings) {
    this._default = {
      el: WpArchiveVue.el,
      data: {
        terms: [],
        posts: [],
        query: {
          page: 1,
          per_page: 5
        },
        headers: {
          pages: 0,
          total: 0,
          link: 'rel="next";'
        },
        init: false
      },
      computed: {
        loading: function() {
          if (!this.posts.length) return false;
          let page = this.posts[this.query.page];
          return this.init && !page.posts.length && page.show;
        },
        none: function() {
          return !this.headers.pages && !this.headers.total;
        },
        next: function() {
          let number = this.query.page;
          let total = this.headers.pages;
          if (!this.posts.length) return false;
          let page = this.posts[number];
          return (number < total) && (page.posts.length && page.show);
        },
        previous: function() {
          return (this.query.page > 1);
        },
        filtering: function() {
          if (!this.init) return false;
          return (this.terms.find(t => t.active)) ? true : false;
        },
        lang: () => {
          let lang = document.querySelector('html').lang;
          return (lang !== 'en') ? {
            code: lang,
            path: `/${lang}`
          } : {
            code: 'en',
            path: ''
          };
        }
      },
      methods: {
        fetch: WpArchiveVue.fetch,
        click: WpArchiveVue.click,
        filter: WpArchiveVue.filter,
        filterAll: WpArchiveVue.filterAll,
        updateQuery: WpArchiveVue.updateQuery,
        reset: WpArchiveVue.reset,
        paginate: WpArchiveVue.paginate,
        wp: WpArchiveVue.wp,
        queue: WpArchiveVue.queue,
        wpQuery: WpArchiveVue.wpQuery,
        response: WpArchiveVue.response,
        process: WpArchiveVue.process,
        error: WpArchiveVue.error
      }
    };

    // Assign missing top level props to settings.
    this._settings = Object.assign({}, this._default, settings);

    // Apply next level properties to settings.
    Object.keys(settings).forEach(prop => {
      if (!this._default.hasOwnProperty(prop)) {
        this._settings[prop] = settings[prop];
      } else if (
        this._default.hasOwnProperty(prop) &&
        Object.isExtensible(this._default[prop])
      ) {
        this._settings[prop] = Object
          .assign({}, this._default[prop], settings[prop]);
      }
    });

    // Start the app.
    return new Vue(this._settings);
  }
}

/** The default selector for the Vue application */
WpArchiveVue.el = '[data-js="archive"]';

/**
 * Basic fetch for retrieving data from an endpoint configured in the
 * data.endpoints property.
 * @param   {object}  data  A key representing an endpoint configured in the
 *                          data.endpoints property.
 * @return  {promise}       The fetch request for that endpoint.
 */
WpArchiveVue.fetch = function(data = false) {
  if (!data) return data;

  return (this[data].length) ? this[data] :
    fetch(this.lang.path + this.endpoints[data])
      .then(response => response.json())
      .then(d => {
        this.$set(this, data, d.map(this.maps()[data]));
      });
};

/**
 * The click event to begin filtering.
 * @param   {object}  event  The click event on the element that triggers
 *                           the filter.
 */
WpArchiveVue.click = function(event) {
  let taxonomy = event.data.parent;
  let term = event.data.id || false;

  if (term) {
    this.filter(taxonomy, term);
  } else {
    this.filterAll(taxonomy);
  }
};

/**
 * Single filter function. If the filter is already present in the query
 * it will add the filter to the query.
 * @param   {string}  taxonomy  The taxonomy slug of the filter
 * @param   {number}  term      The id of the term to filter on
 */
WpArchiveVue.filter = function(taxonomy, term) {
  let terms = (this.query.hasOwnProperty(taxonomy)) ?
    this.query[taxonomy] : [term]; // get other query or initialize.

  // Toggle, if the taxonomy exists, filter it out, otherwise add it.
  if (this.query.hasOwnProperty(taxonomy))
    terms = (terms.includes(term)) ?
      terms.filter(el => el !== term) : terms.concat([term]);

  this.updateQuery(taxonomy, terms);
};

/**
 * A control for filtering all of the terms in a particular taxonomy on or off.
 * @param   {string}  taxonomy  The taxonomy slug of the filter
 */
WpArchiveVue.filterAll = function(taxonomy) {
  let tax = this.terms.find(t => t.slug === taxonomy);
  let checked = !(tax.checked);

  this.$set(tax, 'checked', checked);

  let terms = tax.filters.map(term => {
      this.$set(term, 'checked', checked);
      return term.id;
    });

  this.updateQuery(taxonomy, (checked) ? terms : []);
};

/**
 * This updates the query property with the new filters.
 * @param   {string}  taxonomy  The taxonomy slug of the filter
 * @param   {array}   terms     Array of term ids
 * @return  {promise}           Resolves when the terms are updated
 */
WpArchiveVue.updateQuery = function(taxonomy, terms) {
  return new Promise((resolve, reject) => { // eslint-disable-line no-undef
    this.$set(this.query, taxonomy, terms);
    this.$set(this.query, 'page', 1);
    // hide all of the posts
    this.posts.map((value, index) => {
      if (value) this.$set(this.posts[index], 'show', false);
      return value;
    });
    resolve();
  })
  .then(this.wp)
  .catch(message => {
    // console.dir(message);
  });
};

/**
 * A function to reset the filters to "All Posts."
 * @param   {object}  event  The taxonomy slug of the filter
 * @return  {promise}        Resolves after resetting the filter
 */
WpArchiveVue.reset = function(event) {
  return new Promise(resolve => { // eslint-disable-line no-undef
    let taxonomy = event.data.slug;
    if (this.query.hasOwnProperty(taxonomy)) {
      this.$set(this.query, taxonomy, []);
      resolve();
    }
  });
};

/**
 * A function to paginate up or down a post's list based on the change amount
 * assigned to the clicked element.
 * @param   {object}  event  The click event of the pagination element
 * @return  {promise}        Resolves after updating the pagination in the query
 */
WpArchiveVue.paginate = function(event) {
  event.preventDefault();

  // The change is the next page as well as an indication of what
  // direction we are moving in for the queue.
  let change = parseInt(event.target.dataset.amount);
  let page = this.query.page + change;

  return new Promise(resolve => { // eslint-disable-line no-undef
    this.$set(this.query, 'page', page);
    this.$set(this.posts[this.query.page], 'show', true);

    this.queue([0, change]);
    resolve();
  });
};

/**
 * Wrapper for the queue promise
 * @return  {promise} Returns the queue function.
 */
WpArchiveVue.wp = function() {
  return this.queue();
};

/**
 * This queues the current post request and the next request based on the
 * direction of pagination. It uses an Async method to retrieve the requests
 * in order so that we can determine if there are more posts to show after the
 * request for the current view.
 * @param   {array}  queries  The amount of queries to make and which direction
 *                            to make them in. 0 means the current page, 1 means
 *                            the next page. -1 would mean the previous page.
 */
WpArchiveVue.queue = function(queries = [0, 1]) {
  // Set a benchmark query to compare the upcomming query to.
  let Obj1 = Object.assign({}, this.query); // create copy of object.
  delete Obj1.page; // delete the page attribute because it will be different.
  Object.freeze(Obj1); // prevent changes to our comparison.

  // The function is async because we want to wait until each promise
  // is query is finished before we run the next. This is because we
  // don't want to bother sending a request if there are no previous or
  // next pages. The way we find out if there are previous or next pages
  // relative to the current page query is through the headers of the
  // response provided by the WP REST API.
  (async () => {
    for (let i = 0; i < queries.length; i++) {
      let query = Object.assign({}, this.query);
      // eslint-disable-next-line no-undef
      let promise = new Promise(resolve => resolve());
      let pages = this.headers.pages;
      let page = this.query.page;
      let current = false;
      let next = false;
      let previous = false;

      // Build the query and set its page number.
      Object.defineProperty(query, 'page', {
        value: page + queries[i],
        enumerable: true
      });

      // There will never be a page 0 or below, so skip this query.
      if (query.page <= 0) continue;

      // Check to see if we have the page that we are going to queued
      // and the query structure of that page matches the current query
      // structure (other than the page, which will obviously be different).
      // This will help us determine if we need to make a new request.
      let havePage = (this.posts[query.page]) ? true : false;
      let pageQueryMatches = false;

      if (havePage) {
        let Obj2 = Object.assign({}, this.posts[query.page].query);
        delete Obj2.page;
        pageQueryMatches = (JSON.stringify(Obj1) === JSON.stringify(Obj2));
      }

      if (havePage && pageQueryMatches) continue;

      // If this is the current page we want the query to go through.
      current = (query.page === page);

      // If there is a next or previous page, we'll prefetch them.
      // We'll know there's a next or previous page based on the
      // headers sent by the current page query.
      next = (page < pages && query.page > page);
      previous = (page > 1 && query.page < page);

      if (current || next || previous)
        await promise.then(() => {
          return this.wpQuery(query);
        })
        .then(this.response)
        .then(data => {
          let headers = Object.assign({}, this.headers);
          this.process(data, query, headers);
        }).catch(this.error);
    }
  })();
};

/**
 * Builds the URL query from the provided query property.
 * @param   {object}  query  A WordPress query written in JSON format
 * @return  {promise}        The fetch request for the query
 */
WpArchiveVue.wpQuery = function(query) {
  // eslint-disable-next-line no-undef
  // this.$set(this, 'abort', (new AbortController));
  // let signal = this.abort.signal;
  let url = `${this.lang.path}${this.endpoints[this.type]}`;

  // Build the url query.
  url = `${url}?` + Object.keys(query)
    .map(k => {
      if (Array.isArray(query[k]))
        return query[k].map(a => `${k}[]=${a}`).join('&');
      return `${k}=${query[k]}`;
    }).join('&');

  // Set posts and store a copy of the query for reference.
  this.$set(this.posts, query.page, {
    posts: [],
    query: Object.freeze(query),
    show: (this.query.page >= query.page)
  });

  // return fetch(url, {signal: signal});
  return fetch(url);
};

WpArchiveVue.response = function(response) {
  let headers = {
    total: 'X-WP-Total',
    pages: 'X-WP-TotalPages',
    link: 'Link'
  };

  if (response.ok) {
    let keys = Object.keys(headers);

    for (let i = 0; i < keys.length; i++) {
      let header = response.headers.get(headers[keys[i]]);
      let value = (isNaN(header)) ? header : (parseInt(header) || 0);
      headers[keys[i]] = value;
    }

    this.$set(this, 'headers', headers);
  }

  return response.json();
};

/**
 * Processes the posts and maps the data to data maps provided by the
 * data.maps property.
 * @param   {object}  data     The post data retrieved from the WP REST API
 * @param   {object}  query    The the query used to get the data
 * @param   {object}  headers  The the headers of the request
 */
WpArchiveVue.process = function(data, query, headers) {
  // If there are posts for this query, map them to the template.
  let posts = (Array.isArray(data)) ?
    data.map(this.maps()[this.type]) : false;

  // Set posts and store a copy of the query for reference.
  this.$set(this.posts[query.page], 'posts', posts);
  this.$set(this.posts[query.page], 'headers', Object.freeze(headers));

  // If there are no posts, pass along to the error handler.
  if (!Array.isArray(data))
    this.error({error: data, query: query});

  this.$set(this, 'init', true);
};

/**
 * Error response thrown when there is an error in the WP REST AP request.
 * @param   {object}  response  The error response
 */
WpArchiveVue.error = function(response) {
  // console.dir(response);
};

export default WpArchiveVue;
