/**
 * Alert Banner module
 * @module modules/alert
 * @see modules/toggleOpen
 */

import readCookie from './read-cookie.js';
import dataset from './dataset.js';
import createCookie from './create-cookie.js';
import getDomain from './get-domain.js';

/**
 * Displays an alert banner.
 * @param {string} openClass - The class to toggle on if banner is visible
 */
export default function(openClass) {
  if (!openClass) {
    openClass = 'is-open';
  }

  /**
  * Make an alert visible
  * @param {object} alert - DOM node of the alert to display
  * @param {object} siblingElem - DOM node of alert's closest sibling,
  * which gets some extra padding to make room for the alert
  */
  function displayAlert(alert, siblingElem) {
    alert.classList.remove('hidden');
    alert.classList.add(openClass);
  }

  /**
  * Check alert cookie
  * @param {object} alert - DOM node of the alert
  * @return {boolean} - Whether alert cookie is set
  */
  function checkAlertCookie(alert) {
    const cookieName = dataset(alert, 'cookie');
    if (!cookieName) {
      return false;
    }
    return typeof readCookie(cookieName, document.cookie) !== 'undefined';
  }

  /**
  * Add alert cookie
  * @param {object} alert - DOM node of the alert
  */
  function addAlertCookie(alert) {
    const cookieName = dataset(alert, 'cookie');
    if (cookieName) {
      createCookie(
          cookieName,
          'dismissed',
          getDomain(window.location, false),
          360
      );
    }
  }

  const alerts = document.querySelectorAll('.js-alert');

  if (alerts.length) {
    for (let i=0; i <= alert.length; i++) {
      if (!checkAlertCookie(alerts[i])) {
        const alertSibling = alerts[i].previousElementSibling;
        const alertButton = document.getElementById('alert-button');
        displayAlert(alerts[i], alertSibling);
        alertButton.addEventListener('click', e => {
            alerts[i].classList.add('hidden');
            addAlertCookie(alerts[i]);
          });
      } else {
        alerts[i].classList.add('hidden');
      }
    }
  }
}

