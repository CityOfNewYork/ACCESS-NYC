/* eslint-env browser */

import 'main';

(function() {
  'use strict';

  /**
   * Google Static Map
   */
  (element => {
    // Selected element is passed to this self-instantiating function
    if (element) {
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
  })(document.querySelector('[data-js="google-static-map"]'));
})();
