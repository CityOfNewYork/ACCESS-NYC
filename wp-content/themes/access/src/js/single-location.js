/* eslint-env browser */

(function() {
  'use strict';

  (collection => {
    // collection of selected elements is passed to this function
    for (let i = 0; i < collection.length; i++) {
      const element = collection[i];

      window.addEventListener('load', () => {
        const iframe = document.createElement('iframe');

        iframe.setAttribute('src', element.dataset.googleStaticMap);
        iframe.setAttribute('class', element.attributes.class.value);
        iframe.setAttribute('width', element.attributes.width.value);
        iframe.setAttribute('height', element.attributes.height.value);
        iframe.setAttribute('frameborder',
                              element.attributes.frameborder.value);

        element.replaceWith(iframe);
      });
    }
  })(document.querySelectorAll('[data-js="google-static-map"]'));
})();
