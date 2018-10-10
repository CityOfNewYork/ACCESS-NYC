/* eslint-env browser */
import jQuery from 'jquery';
import OfficeMap from 'modules/office-map';
import Screener from 'modules/screener';
import ShareForm from 'modules/share-form';
import StaticMap from 'modules/static-map';
import TextSizer from 'modules/text-sizer';
import Tooltip from 'modules/tooltip';
import Utility from 'modules/utility';
import Accordion from 'components/accordion/accordion.common';
import Filter from 'components/filter/filter.common';
import NearbyStops from 'components/nearby-stops/nearby-stops.common';

(function(window, $) {
  'use strict';

  const google = window.google;

  Utility.configErrorTracking();

  // Get SVG sprite file.
  // See: https://css-tricks.com/ajaxing-svg-sprite/
  $.get('/wp-content/themes/access/assets/svg/icons.svg', Utility.svgSprites);

  let $body = $('body');

  // Attach site-wide event listeners.
  $body.on(
    'click',
    '.js-simple-toggle, [data-js="toggle"]', // use the data attr selector
    Utility.simpleToggle
  ).on('click', '[data-js="toggle-nav"]', (event) => {
    let element = $(event.currentTarget);
    // Shows/hides the mobile nav and overlay.
    event.preventDefault();
    $('body').toggleClass('active:overlay');
    $(element.attr('href')).toggleClass('active:o-mobile-nav');
  }).on('click', '.js-toggle-search', (e) => {
    // Shows/hides the search drawer in the main nav.
    e.preventDefault();
    const $search = $('#search');
    $search.toggleClass('active');
    if ($search.hasClass('active')) {
      setTimeout(function() {
        $('#search-field').focus();
      }, 20);
    }
  }).on('click', '.js-hide-search', (e) => {
    // Hides the search drawer in the main nav.
    e.preventDefault();
    $('#search').removeClass('active');
  });

  // Initialize ACCESS NYC Patterns lib components
  new Accordion();
  new Filter();
  new NearbyStops();

  // Show/hide share form disclaimer
  $body.on('click', '.js-show-disclaimer', ShareForm.ShowDisclaimer);

  // A basic click tracking function
  $body.on('click', '[data-js*="track"]', (event) => {
    /* eslint-disable no-console, no-debugger */
    let key = event.currentTarget.dataset.trackKey;
    let data = JSON.parse(event.currentTarget.dataset.trackData);
    Utility.track(key, data);
    /* eslint-enable no-console, no-debugger */
  });

  // Capture the queries on Search page
  $(window).on('load', function() {
    let $wtSearch = $('[data-js="wt-search"]');
    if (~window.location.href.indexOf('?s=') && $wtSearch.length) {
      let key = $wtSearch.data('wtSearchKey');
      let data = $wtSearch.data('wtSearchData');
      Utility.webtrends(key, data);
    }
  });

  // On the search results page, submits the search form when a category is
  // chosen.
  $('.js-program-search-filter').on('change', 'input', (e) => {
    $(e.currentTarget).closest('form')[0].submit();
  });

  // TODO: This function and the conditional afterwards should be refactored
  // and pulled out to its own program detail controller module. The main
  // unique thing about program details is that they use a ?step=x query
  // parameter in the URL to determine the visible section. It is still all
  // the same page. A hash would seem more appropriate, but there were
  // some supposed issues with WPML where the hash was being stripped when
  // switching between langauges. Because it is a single page, we don't need
  // to actually reload the browser, which is why history.pushState is used.
  /**
   * Advances Program Page Steps
   * @param {string} step - the kebab case identifier for the section
   */
  function showSection(step) {
    $('[data-js="program-detail-step"]')
       .removeClass('active').filter(`#${step}`).addClass('active');

    $('[data-js="program-nav"] a').removeClass('active')
       .filter(`#nav-link-${step}`).addClass('active');
  }

  if ($('[data-js="program-detail-content"]').length) {
    const isMobileView = () => $('[data-js="site-desktop-nav"]')
      .is(':hidden');

    $('[data-js*="program-nav-step-link"]').on('click', (e) => {
      if (!history.pushState) {
        return true;
      }
      e.preventDefault();

      const step = Utility.getUrlParameter('step', $(e.target).attr('href'));
      let linkType = '';

      window.history.pushState(null, null, '?step=' + step);

      if ($(e.target).hasClass('[data-js*="jump-to-anchor"]')) {
        linkType = 'buttonLink';
      } else {
        linkType = 'navLink';
      }
      $(window).trigger('popstate', linkType);
    });

    $(window).on('popstate', (e, linkType) => {
      const possibleSections = [
        'how-it-works',
        'how-to-apply',
        'determine-your-eligibility',
        'what-you-need-to-include'
      ];

      let sectionId = Utility.getUrlParameter('step');

      if (!sectionId || !$.inArray(sectionId, possibleSections)) {
        sectionId = 'how-it-works';
      }

      // If the page is in a mobile view, and the user has clicked a button
      // (as opposed to one of the table of content links) we want to scroll
      // the browser to the content body as opposed to the top of the page.
      if (isMobileView() && linkType === 'buttonLink') {
        $(document).scrollTop(
          $('[data-js="program-detail-content"]').offset().top
        );
      } else {
        $(document).scrollTop(0);
      }
      showSection(sectionId);
    }).trigger('popstate');
  }
  // END TODO

  // Initialize text sizer module.
  $(`.${TextSizer.CssClass.CONTROLLER}`).each((i, el) => {
    const textSizer = new TextSizer(el);
    textSizer.init();
  });

  // Initialize eligibility screener.
  $(`.${Screener.CssClass.FORM}`).each((i, el) => {
    const screener = new Screener(el);
    screener.init();
  });

  // Initialize maps if present.
  const $maps = $('.js-map');

  /**
   * Callback function for loading the Google maps library.
   */
  function initializeMaps() {
    $maps.each((i, el) => {
      const map = new OfficeMap(el);
      map.init();
    });
  }

  if ($maps.length > 0) {
    const options = {
      key: Utility.CONFIG.GOOGLE_API,
      libraries: 'geometry,places'
    };

    google.load('maps', '3', {
      /* eslint-disable camelcase */
      other_params: $.param(options),
      /* eslint-enable camelcase */
      callback: initializeMaps
    });
  }

  // Initialize simple maps.
  $('.js-static-map').each((i, el) => {
    const staticMap = new StaticMap(el);
    staticMap.init();
  });

  // For location detail pages, this overwrites the link to the "back to map"
  // button if the previous page was the map. We want the user to return to
  // the previous state of the map (via the same URL) rather than simply going
  // back to the default map.
  $('.js-location-back').each((i, el) => {
    if (window.document.referrer.indexOf('/locations/?') >= 0) {
      $(el).attr('href', window.document.referrer);
    }
  });

  // Initialize tooltips.
  $(`.${Tooltip.CssClass.TRIGGER}`).each((i, el) => {
    const tooltip = new Tooltip(el);
    tooltip.init();
  });

  // Initialize share by email/sms forms.
  $(`.${ShareForm.CssClass.FORM}`).each((i, el) => {
    const shareForm = new ShareForm(el);
    shareForm.init();
  });

  // For pages with "print-view" class, print the page on load. Currently only
  // used on program detail pages after the print link is clicked.
  if ($('html').hasClass('print-view')) {
    window.onload = window.print;
  }

  // Add noopener attribute to new window links if it isn't there.
  $('a[target="_blank"]').each((i, el) => {
    let rel = $(el).attr('rel');
    if (rel.indexOf('noopener') == -1) {
      $(el).attr('rel', `${rel} noopener`);
    }
  });

  // Enable environment warnings
  $(window).on('load', () => Utility.warnings());
})(window, jQuery);
