window.addEventListener('load', function(event) {

	var url = new URL(window.location.href);
	url.searchParams.set('emr_success', 1);

  var timeout = 10;
	if (emr_success_options.timeout)
		timeout = emr_success_options.timeout;
	var counter = document.getElementById('redirect_counter');
	var redirectUrl = document.getElementById('redirect_url');
	var redirected = false;

	counter.textContent = timeout;

	var t = window.setInterval(function () {
		counter.textContent = timeout;
		timeout--;
		if (timeout <= 0 && false == redirected)
		{
			 window.location.href = redirectUrl;
			 redirected = true;
			 window.clearInterval(t);
		}
	}, 1000);

});
