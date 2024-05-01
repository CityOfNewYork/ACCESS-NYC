/*jshint browser:true, devel:true */
/*wp */
var WPMLCore = WPMLCore || {};

WPMLCore.TranslationFeedback = function() {
	this.form = this.byClass(document, 'js-wpml-tf-feedback-form');
	this.openIcon = this.byClass(document, 'js-wpml-tf-feedback-icon');
	this.ratingInputs = this.byQueryAll(this.form, 'input[name="wpml-tf-rating"]');
	this.changeRating = this.byClass(this.form, 'js-wpml-tf-change-rating');
	this.sendButton = this.byClass(this.form, 'js-wpml-tf-comment-button');
	this.documentId = this.byQuery(this.form, 'input[name="document_id"]').value;
	this.documentType = this.byQuery(this.form, 'input[name="document_type"]').value;
	this.action = this.byQuery(this.form, 'input[name="action"]').value;
	this.nonce = this.byQuery(this.form, 'input[name="nonce"]').value;
	this.noCommentThreshold = 4;
	this.dialogInitialized = false;
	this.feedbackId = null;
	this.assetUrl = {
		'js': this.byQuery(this.form, 'input[name="asset_url_js"]').value.split(';'),
	};
	this.areAssetsLoaded = false;
	this.ajaxUrl = this.byQuery(this.form, 'input[name="ajax_url"]').value;

	var self = this;

	this.openIcon.addEventListener('click', function(e) {
		e.preventDefault();
		self.openForm();
	});

	for(var i = 0; i < this.ratingInputs.length; i++) {
		this.ratingInputs[i].addEventListener('click', function(e) {
			var data = {
				rating: e.target.value,
			};

			if ( self.feedbackId ) {
				data.feedback_id = self.feedbackId;
			}

			self.sendFeedback(data);

			if(data.rating < self.noCommentThreshold) {
				self.displayPendingComment();
			} else {
				self.displayClosingRating();
			}
		});
	}

	this.changeRating.addEventListener('click', function(e) {
		e.preventDefault();
		self.displayPendingRating();
	});

	this.sendButton.addEventListener('click', function(e) {
		e.preventDefault();
		var data = {
			content: self.byQuery(document, 'textarea[name="wpml-tf-comment"]').value,
			feedback_id: self.feedbackId,
		};

		self.sendFeedback(data);
		self.displayClosingComment();
	});
};

WPMLCore.TranslationFeedback.prototype = {
	byClassAll: function(node, className) {
		return node.getElementsByClassName(className);
	},

	byQueryAll: function(node, query) {
		return node.querySelectorAll(query);
	},

	byClass: function(node, className) {
		return this.byClassAll(node, className)[0];
	},

	byQuery: function(node, query) {
		return this.byQueryAll(node, query)[0];
	},

	disableRating: function() {
		for(var i = 0; i < this.ratingInputs.length; i++) {
			this.ratingInputs[i].setAttribute('disabled', 'disabled');
		}
	},

	enableRating: function() {
		if(this.feedbackId && !this.form.classList.contains('wpml-tf-closing-rating')) {
			for(var i = 0; i < this.ratingInputs.length; i++) {
				this.ratingInputs[i].removeAttribute('disabled');
			}
		}
	},

	enableComment: function() {
		this.sendButton.removeAttribute('disabled');
	},

	displayClosingComment: function() {
		this.form.classList.remove('wpml-tf-pending-comment');
		this.form.classList.add('wpml-tf-closing-comment');
		var self = this;
		window.setTimeout(function() {
			self.destroyDialogAndButton.call(self);
		}, 3000);
	},

	displayPendingComment: function() {
		this.form.classList.add('wpml-tf-pending-comment');
		this.form.classList.remove('wpml-tf-closing-rating');
		this.enableRating();
	},

	displayClosingRating: function() {
		this.form.classList.add('wpml-tf-closing-rating');
		this.form.classList.remove('wpml-tf-pending-comment');
		this.disableRating();
	},

	displayPendingRating: function() {
		this.form.classList.remove('wpml-tf-closing-rating');
		this.form.classList.remove('wpml-tf-pending-comment');
		this.enableRating();
	},

	sendFeedback: function(data) {
		var self = this;

		if ( ! this.itLooksLikeSpam() ) {
			this.disableRating();
			this.form.classList.add('wpml-tf-pending-request');

			data.nonce         = this.nonce;
			data.document_id   = this.documentId;
			data.document_type = this.documentType;
			data.action        = this.action;

			var params = [];
			for(var prop in data) {
				params.push(encodeURIComponent(prop) + '=' + encodeURIComponent(data[prop]));
			}
			params = params.join('&', params);

			var req = new XMLHttpRequest();
			req.open("POST", this.ajaxUrl);
			req.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
			req.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
			req.setRequestHeader('Accept', 'application/json, text/javascript, */*; q=0.01');

			req.onload = function() {
				const response = JSON.parse(req.response);
				const data = response.data;

				self.feedbackId = data.feedback_id;
				self.form.classList.add('wpml-tf-has-feedback-id');
				self.form.classList.remove('wpml-tf-pending-request');
				self.enableRating();
				self.enableComment();
			};

			req.send(params);
		}
	},

	itLooksLikeSpam: function() {
		var more_comment = this.byQuery(this.form, 'textarea[name="more_comment"]');
		return ! this.dialogInitialized || more_comment.value;
	},

	loadAsset: function(url) {
		var self = this;
		var scr = document.createElement('script');
		var head = document.head || document.getElementsByTagName('head')[0];
		scr.src = url;
		scr.async = false;

		scr.addEventListener('load', function() {
			self.assetsLoadedCount++;
			if(self.assetsLoadedCount === self.assetsToLoadCount) {
				self.areAssetsLoaded = true;
				self.execOpenForm();
			}
		});
	
		head.insertBefore(scr, head.firstChild);
	},

	openForm: function() {
		if(this.areAssetsLoaded) {
			this.execOpenForm();
			return;
		}

		var assets = this.filterOnlyNotAlreadyLoadedAssets();

		this.assetsLoadedCount = 0;
		this.assetsToLoadCount = assets.length;
		for(var i = 0; i < assets.length; i++) {
			this.loadAsset(assets[i]);
		}
	},

	filterOnlyNotAlreadyLoadedAssets: function() {
		var loadedScripts = this.getScripts();
		var loadScripts = [];
		for(var i = 0; i < this.assetUrl.js.length; i++) {
			var isAlreadyLoaded = false;
			for(var j = 0; j < loadedScripts.length; j++) {
				if(this.assetUrl.js[i] == loadedScripts[j]) {
					isAlreadyLoaded = true;
					break;
				}
			}

			if(isAlreadyLoaded) {
				continue;
			}

			loadScripts.push(this.assetUrl.js[i]);
		}

		return loadScripts;
	},

	getScripts: function() {
		var allScripts = document.getElementsByTagName('script');
		var scripts = [];
		for(var i = 0; i < allScripts.length; i++) {
			var src = allScripts[i].getAttribute('src');
			if(typeof src != 'string') {
				continue;
			}

			var srcParts = src.split('?');
			scripts.push(srcParts[0]);
		}

		return scripts;
	},

	execOpenForm: function() {
		jQuery(this.form).dialog({
			dialogClass: 'wpml-tf-feedback-form-dialog otgs-ui-dialog',
			closeOnEscape: true,
			draggable: true,
			modal: false,
			title: this.form.getAttribute('data-dialog-title'),
			dragStop: function() {
				jQuery(this).parent().height('auto');
			}
		});

		this.dialogInitialized = true;

		// Fix display glitch with bootstrap.js
		var dialog = this.byClass(document, 'wpml-tf-feedback-form-dialog');
		var dialogClose = this.byClass(dialog, 'ui-dialog-titlebar-close');
		dialogClose.classList.add('ui-button');
	},

	destroyDialogAndButton: function() {
		jQuery(this.form).dialog( 'destroy' );
		this.openIcon.parentNode.removeChild(this.openIcon);
	},
};

document.addEventListener('DOMContentLoaded', function() {
	var tf = new WPMLCore.TranslationFeedback();
});