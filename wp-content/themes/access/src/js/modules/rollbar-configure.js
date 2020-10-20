/* eslint-env browser */
'use strict';

/**
 * Sends a configuration object to Rollbar, the most important config is
 * the code_version which maps to the source maps version.
 */

class RollbarConfigure {
  constructor() {
    // eslint-disable-next-line no-undef
    if (typeof Rollbar === 'undefined') return false;

    let scripts = document.getElementsByTagName('script');
    let source = scripts[scripts.length - 1].src;
    let path = source.split('/');
    let basename = path[path.length - 1];
    let hash = basename.split('.')[1];

    let config = {
      payload: {
        client: {
          javascript: {
            // This is will be true by default if you have enabled
            // this in settings.
            source_map_enabled: true,
            // This is transformed via envify in the scripts task.
            code_version: hash,
            // Optionally guess which frames the error was thrown from
            // when the browser does not provide line and column numbers.
            guess_uncaught_frames: true
          }
        }
      }
    };

    window.addEventListener('load', () => {
    // $(window).on('load', () => {
      // eslint-disable-next-line no-undef
      let rollbarConfigure = Rollbar.configure(config);
      let msg = `Configured Rollbar with ${hash}`;

      if (process.env.NODE_ENV === 'development') {
        // eslint-disable-next-line no-console
        console.dir({
          init: msg,
          settings: rollbarConfigure
        });

        Rollbar.debug(msg); // eslint-disable-line no-undef
      }
    });
  }
}

export default RollbarConfigure;