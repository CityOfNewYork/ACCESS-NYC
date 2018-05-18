<?

/**
 * Storage for all site related notifications such as missing dependencies, etc.
 */

namespace Notifications;


/**
 * Notify admin to activate Timber if it has not been activated.
 * @return null
 */
function timber() {
  if (!class_exists('Timber')) {
    add_action('admin_notices', function() {
      echo 'Timber not activated. Make sure you activate the plugin in <a href="/wp-admin/plugins.php#timber">/wp-admin/plugins.php</a>';
    });
    return;
  }
}

/**
 * Return a localized reading friendly string of the enviroment.
 * @param  string $env The environment string to return if uknown. Default 'Unknown.'
 * @return string      The localized reading friendly string.
 */
function environment_string($env = 'Unkown') {
  switch (WP_ENV) {
    case 'accessnyc':
        $env = __('Production');
        break;
    case 'accessnycstage':
        $env = __('Staging');
        break;
    case 'accessnycdemo':
        $env = __('Demo');
        break;
    case 'accessnyctest':
        $env = __('Testing');
        break;
    case 'development':
        $env = __('Development');
        break;
  }
  return $env;
}