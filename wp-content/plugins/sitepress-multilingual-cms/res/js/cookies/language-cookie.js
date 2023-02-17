jQuery(function () {
    jQuery.each(wpml_cookies, function (cookieName, cookieData) {
        document.cookie = cookieName + '=' + cookieData.value + ';expires=' + cookieData.expires + '; path=' + cookieData.path
    });
});
