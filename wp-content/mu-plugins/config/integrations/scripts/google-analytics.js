function gtag() { dataLayer.push(arguments); }
gtag('js', new Date());
if (window.hasOwnProperty('GOOGLE_OPTIMIZE')) {
  gtag('config', '{{ GOOGLE_ANALYTICS }}', {'optimize_id': '{{ GOOGLE_OPTIMIZE_ID }}', 'transport_type': 'beacon'});
} else {
  gtag('config', '{{ GOOGLE_ANALYTICS }}', {'transport_type': 'beacon'});
}
