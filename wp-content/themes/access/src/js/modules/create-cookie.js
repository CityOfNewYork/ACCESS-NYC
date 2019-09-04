/**
* Save a cookie
* @param {string} name - Cookie name
* @param {string} value - Cookie value
* @param {string} domain - Domain on which to set cookie
* @param {integer} days - Number of days before cookie expires
*/
export default function(name, value, domain, days) {
  const expires = days ? '; expires=' + (
    new Date(days * 864E5 + (new Date()).getTime())
  ).toGMTString() : '';
  document.cookie = name + '=' + value + expires + '; path=/; domain=' + domain;
}
