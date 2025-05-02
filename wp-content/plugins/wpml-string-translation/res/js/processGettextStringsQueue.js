jQuery(function () {
	//processGettextStringsQueue();

	jQuery(window).on('launchAutoregisterConsumer', function() {
		processGettextStringsQueue();
	});

	//makeTestStringsApiRequest();
});

function processGettextStringsQueue() {
	var settings = WPML_TM_SETTINGS;

	var restUrl   = settings.restUrl;
	var restNonce = settings.restNonce;

	var url = restUrl + '/wpml/st/v1/gettextstrings';
	var data = {};

	var req = new XMLHttpRequest();
	req.open("POST", url);
	req.setRequestHeader('Content-type', 'application/json');
	req.setRequestHeader('Accept', 'application/json');
	req.setRequestHeader('X-WP-Nonce', restNonce);
	req.withCredentials = true;

	req.onload = function() {
	};

	req.send(JSON.stringify(data));
}

function makeTestStringsApiRequest() {
	var restUrl   = WPML_TM_SETTINGS.restUrl;
	var restNonce = WPML_TM_SETTINGS.restNonce;

	if(restUrl.includes('?')) {
		restUrl = restUrl.replace('?', '&');
	}

	var url = restUrl + '/wpml/st/v1/strings_testapi';
	var data = {
	};
	var headers = {
		'Accept': 'application/json',
		'Content-Type': 'application/json',
		'X-WP-Nonce': restNonce,
	};
	jQuery.ajax({
		url: url,
		type: 'GET',
		data: data,
		success: function(res) {
			console.log(res);
		},
		dataType: 'json',
		headers: headers,
		xhrFields: {
			withCredentials: true,
		},
	});
}