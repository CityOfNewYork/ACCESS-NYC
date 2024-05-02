/*globals icl_vars, wpml_xdomain_data */

var WPMLCore = WPMLCore || {};

WPMLCore.XdomainData = function() {
	this.links = document.querySelectorAll('.' + wpml_xdomain_data.css_selector + ' a');

	var self = this;

	for(var i = 0; i < this.links.length; i++) {
		this.links[i].addEventListener('click', function(e) {
			var link = self.getClosestLink(e.target);
			var currentUrl = window.location.href;
			var targetUrl = link.getAttribute('href');

			if ('#' !== targetUrl && currentUrl !== targetUrl) {
				e.preventDefault();
				self.onLinkClick(link);
			}
		});
	}
};

WPMLCore.XdomainData.prototype = {
	getClosestLink: function(maybeLink) {
		var tagName = maybeLink.nodeName.toLowerCase();
		if(tagName === 'a') {
			return maybeLink;
		}

		return this.getClosestLink(maybeLink.parentNode);
	},

	onLinkClick: function(link) {
		var self = this;
		var originalUrl = link.getAttribute('href');
		// Filter out xdomain_data if already in the url
		originalUrl = originalUrl.replace(/&xdomain_data(=[^&]*)?(?=&|$)|xdomain_data(=[^&]*)?(&|$)/, '');
		originalUrl = originalUrl.replace(/\?$/, '');

		var data = {
			action:        'switching_language',
			from_language: wpml_xdomain_data.current_language,
			_nonce:        wpml_xdomain_data._nonce,
		};
		var params = [];
		for(var prop in data) {
			if(typeof data[prop] !== 'undefined') {
				params.push(encodeURIComponent(prop) + '=' + encodeURIComponent(data[prop]));
			}
		}
		params = params.join('&', params);

		/** @namespace icl_vars.current_language */
		var req = new XMLHttpRequest();
		req.open("POST", wpml_xdomain_data.ajax_url);
		req.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
		req.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
		req.setRequestHeader('Accept', 'application/json, text/javascript, */*; q=0.01');

		req.onload = function() {
			const response = JSON.parse(req.response);
			self.onSuccess(response, originalUrl);
		};
		req.onerror = function() {
			location.href = originalUrl;
		};

		req.send(params);
	},

	onSuccess: function(response, originalUrl) {
		var argsGlue;
		var url;
		var hash;
		var urlSplit;
		var xdomain;
		var form;

		if (response.data.xdomain_data) {
			if (response.success) {
				if ('post' === response.data.method) {

					// POST
					form = document.createElement('form');
					form.setAttribute('method', 'post');
					form.setAttribute('action', originalUrl);
					xdomain = document.createElement('input');
					xdomain.setAttribute('type', 'hidden');
					xdomain.setAttribute('name', 'xdomain_data');
					xdomain.setAttribute('value', response.data.xdomain_data);

					form.appendChild(xdomain);
					document.body.appendChild(form);

					form.submit();

				} else {
					// GET
					urlSplit = originalUrl.split('#');
					hash = '';
					if (1 < urlSplit.length) {
						hash = '#' + urlSplit[1];
					}
					url = urlSplit[0];
					if (url.indexOf('?') === -1) {argsGlue = '?';} else {argsGlue = '&';}
					/** @namespace response.data.xdomain_data */
					url = originalUrl + argsGlue + 'xdomain_data=' + response.data.xdomain_data + hash;
					location.href = url;
				}

			} else {
				url = originalUrl;
				location.href = url;
			}
		} else {
			location.href = originalUrl;
		}
	},
};

document.addEventListener('DOMContentLoaded', function() {
	var xd = new WPMLCore.XdomainData();
});