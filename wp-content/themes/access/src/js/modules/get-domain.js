/**
* Get the domain from a URL
* @param {string} url - The URL
* @param {boolean} root - Whether to return root domain rather than subdomain
* @return {string} - The parsed domain
*/
export default function(url, root) {
  /**
  * Parse the URL
  * @param {string} url - The URL
  * @return {string} - The link element
  */
  function parseUrl(url) {
    const target = document.createElement('a');
    target.href = url;
    return target;
  }

  if (typeof url === 'string') {
    url = parseUrl(url);
  }
  let domain = url.hostname;
  if (root) {
    const slice = domain.match(/\.uk$/) ? -3 : -2;
    domain = domain.split('.').slice(slice).join('.');
  }
  return domain;
}
