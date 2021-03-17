(function(a, s, y, n, c, h, i, d, e) {
  s.className += ' ' + y; h.start = 1 * new Date;
  h.end = i = function() {s.className = s.className.replace(RegExp(' ?' + y), '');};
  (a[n] = a[n] || []).hide = h; setTimeout(function() {i(); h.end = null}, c); h.timeout = c;
})(window, document.documentElement, 'async-hide', 'dataLayer', 4000, {'{{ GOOGLE_OPTIMIZE_ID }}': true});

window.GOOGLE_OPTIMIZE = true;