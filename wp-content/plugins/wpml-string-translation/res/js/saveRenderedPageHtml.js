// jQuery(function () {
// 	saveRenderedPageHtml();
// });
//
// function saveRenderedPageHtml() {
// 	var settings = wpml_st_save_rendered_page_html;
//
// 	var restUrl   = settings.restUrl;
// 	var restNonce = settings.restNonce;
//
// 	var url = restUrl + '/wpml/st/v1/htmlstrings';
// 	var data = {
// 		pageHtml: document.documentElement.innerHTML,
// 		requestUrl: settings.requestUrl,
// 	};
//
// 	var req = new XMLHttpRequest();
// 	req.open("POST", url);
// 	req.setRequestHeader('Content-type', 'application/json');
// 	req.setRequestHeader('Accept', 'application/json');
// 	req.setRequestHeader('X-WP-Nonce', restNonce);
// 	req.withCredentials = true;
//
// 	req.onload = function() {
// 	};
//
// 	req.send(JSON.stringify(data));
// }