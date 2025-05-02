document.addEventListener('DOMContentLoaded', function() {
	const {tokenStart, tokenEnd, hgColor} = window.wpml_st_loaded_page_with_tracked_string_data;
	var highlightString = function() {
		document.body.innerHTML = document.body.innerHTML
			.replace(new RegExp(tokenStart, 'g'), '<div class="wpml-st-loaded-page-with-tracked-string-highlight" style="color: ' + hgColor + '">')
			.replace(new RegExp(tokenEnd, 'g'), '</div>');
	};

	highlightString();

	var maybeHighlightAjaxRequestsInterval = null;
	var maxTries = 10;
	var tries = 0;
	maybeHighlightAjaxRequestsInterval = setInterval(function() {
		tries++;
		highlightString();
		if (tries >= maxTries) {
			clearInterval(maybeHighlightAjaxRequestsInterval);
		}
	}, 500);

	window.addEventListener('beforeunload', function() {
		clearInterval(maybeHighlightAjaxRequestsInterval);
	});
});