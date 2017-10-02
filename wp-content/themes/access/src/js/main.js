/* eslint-env browser */
import jQuery from 'jquery';
// import SmoothScroll from 'smoothscroll-polyfill';
import OfficeMap from 'modules/office-map';
import Screener from 'modules/screener';
import ShareForm from 'modules/share-form';
import StaticMap from 'modules/static-map';
import TextSizer from 'modules/text-sizer';
import Tooltip from 'modules/tooltip';
import Utility from 'modules/utility';

(function(window, $) {
  'use strict';

  const google = window.google;
  /* eslint no-undef: "off" */
  const variables = require('../variables.json');

  // Get SVG sprite file.
  // See: https://css-tricks.com/ajaxing-svg-sprite/
  $.get('/wp-content/themes/access/assets/img/icons.svg', function(data) {
    const svgDiv = document.createElement('div');
    svgDiv.innerHTML =
        new XMLSerializer().serializeToString(data.documentElement);
    $(svgDiv).css('display', 'none').prependTo('body');
  });

  // Attach site-wide event listeners.
  $('body').on('click', '.js-simple-toggle', (e) => {
    // Simple toggle that add/removes "active" and "hidden" classes, as well as
    // applying appropriate aria-hidden value to a specified target.
    // TODO: There are a few siimlar toggles on the site that could be
    // refactored to use this class.
    e.preventDefault();
    const $target = $(e.currentTarget).attr('href') ?
        $($(e.currentTarget).attr('href')) :
        $($(e.currentTarget).data('target'));

    $(e.currentTarget).toggleClass('active');
    $target.toggleClass('active hidden')
        .prop('aria-hidden', $target.hasClass('hidden'));

    // function to hide all elements
    if ($(e.currentTarget).data('hide')) {
      $($(e.currentTarget).data('hide')).not($target)
        .addClass('hidden')
        .removeClass('active')
        .prop('aria-hidden', true);
    }

    if ($(e.currentTarget).data('loc')) {
      window.location.hash = $(e.currentTarget).data('loc');
    }
  }).on('click', '.js-show-nav', (e) => {
    // Shows the mobile nav by applying "nav-active" cass to the body.
    e.preventDefault();
    $(e.delegateTarget).addClass('nav-active');
  }).on('click', '.js-hide-nav', (e) => {
    // Hides the mobile nav.
    e.preventDefault();
    $(e.delegateTarget).removeClass('nav-active');
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
  }).on('click', '.js-toggle-filter', (e) => {
    e.preventDefault();
    $(e.currentTarget).closest('.js-program-filter').toggleClass('active');
  }).on('click', '.js-show-disclaimer', (e) => {
    e.preventDefault();
    let $cnt = $('.js-needs-disclaimer.active').length;
    let $el = $('#js-disclaimer');
    let $hidden = ($cnt > 0) ? 'removeClass' : 'addClass';
    let $animate = ($cnt > 0) ? 'addClass' : 'removeClass';
    $el[$hidden]('hidden');
    $el[$animate]('animated fadeInUp');
    $el.attr('aria-hidden', ($cnt === 0));
    // Scroll-to functionality for mobile
    if (
      window.scrollTo &&
      $cnt != 0 &&
      window.innerWidth < variables['screen-desktop']
    ) {
      let $target = $(e.target);
      window.scrollTo(0, $target.offset().top - $target.data('scrollOffset'));
    }
  });

  // On the search results page, submits the search form when a category is
  // chosen.
  $('.js-program-search-filter').on('change', 'input', (e) => {
    $(e.currentTarget).closest('form')[0].submit();
  });

  // TODO: This should be refactored to just use the .js-simple-toggle class.
  // Toggles Program "What you need to bring for eligibility" displays
  $('.js-program-detail-what-you-need-to-include').removeClass('no-js-open');
  $('.js-hide-or-show-list').removeClass('no-js-hidden');

  $('.js-hide-or-show-list').click(function(e) {
    $(e.currentTarget).toggleClass('show hide')
      .closest('.program-detail-what-you-need-to-include')
      .toggleClass('open');
  });
  // END TODO

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
    // TODO: This could be refactored to just use the js-simple-toggle class.
    $('.program-detail-step:not(.program-detail-body-print)')
       .removeClass('active').filter(`#${step}`).addClass('active');
    $('.program-nav a').removeClass('active')
       .filter(`#nav-link-${step}`).addClass('active');
  }
  if ($('.program-detail-content').length) {
    const isMobileView = () => $('.site-desktop-nav').is(':hidden');

    $('.js-program-nav-step-link').on('click', (e) => {
      if (!history.pushState) {
        return true;
      }
      e.preventDefault();

      const step = Utility.getUrlParameter('step', $(e.target).attr('href'));
      let linkType = '';

      window.history.pushState(null, null, '?step=' + step);

      if ($(e.target).hasClass('js-jump-to-anchor')) {
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
        $(document).scrollTop( $('.content-body').offset().top );
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

  // Mask phone numbers
  $('input[type="tel"]').each((i, el) => {
    Utility.maskPhone(el);
  });

  // For pages with "print-view" class, print the page on load. Currently only
  // used on program detail pages after the print link is clicked.
  if ($('html').hasClass('print-view')) {
    window.onload = window.print;
  }
})(window, jQuery);
