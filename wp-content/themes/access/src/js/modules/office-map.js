/* eslint-env browser */
'use strict';

import $ from 'jquery';
import OfficeFilter from 'modules/office-filter';
import OfficeLocation from 'modules/office-location';
import Utility from 'modules/utility';
import _ from 'underscore';

/* eslint-disable no-undef */

/**
 * This is the main controller for the map at the /locations page. This handles
 * rendering the Google map, fetching location details, placing markers, and
 * any map interactions - like filtering and featuring.
 * @class
 */
class OfficeMap {
  /**
   * @param {HTMLElement} el - The html element for the component.
   * @constructor
   */
  constructor(el) {
    /** URL Parameters */
    this._params = new URLSearchParams(window.location.search);

    /** @private {HTMLElement} The component element. */
    this._el = $(el);

    /** @private {HTMLElement} The map element. */
    this._mapEl = $(el).find(`.${OfficeMap.CssClass.MAP_BOX}`)[0];

    /** @private {HTMLElement} The map element. */
    this._listEl = $(el).find(`.${OfficeMap.CssClass.RESULT_LIST}`)[0];

    /** @private {HTMLElement} The search box element. */
    this._searchEl = $(el).find(`.${OfficeMap.CssClass.SEARCH_BOX}`)[0];

    /** @private {HTMLElement} The filter control element. */
    this._filterEl = $(el).find(`.${OfficeMap.CssClass.FILTER}`)[0];

    /** @private {HTMLElement} The filter control element. */
    this._paginationEl = $(el).find(`.${OfficeMap.CssClass.PAGINATION}`)[0];

    /** @private {boolean} Whether the map has been initialized. */
    this._initialized = false;

    /** @private {google.maps.LatLng} Map position. */
    this._mapPosition = (this._params.get('lat') && this._params.get('lng')) ?
      new google.maps.LatLng(parseFloat(this._params.get('lat')),
          parseFloat(this._params.get('lng'))) :
      new google.maps.LatLng(Utility.CONFIG.DEFAULT_LAT,
          Utility.CONFIG.DEFAULT_LNG);

    /** @private {?google.maps.Map} The google map object. */
    this._map = new google.maps.Map(this._mapEl, {
      zoom: 11,
      center: this._mapPosition
    });

    /** @private {google.maps.places.Autocomplete} Search box controller. */
    this._Autocomplete = new google.maps.places.Autocomplete(this._searchEl);

    /** @private {OfficeFilter} Program filter controller. */
    this._filter = new OfficeFilter(this._filterEl);

    /** @private {Array<OfficeLocation>} The office locations. */
    this._locations = [];

    /** @private {Array<OfficeLocation>} The office locations. */
    this._filteredLocations = [];

    /** @private {Array<Number>} The IDs of programs to filter by. */
    this._programs = Utility.getUrlParameter('programs') ?
        _.map(decodeURIComponent(Utility.getUrlParameter('programs'))
        .split(','), num => {
            return parseInt(num, 10);
        }) : [];
  }

  /**
   * If this form has not yet been initialized, attaches event listeners.
   * @method
   * @return {this} OfficeMap
   */
  init() {
    if (this._initialized) {
      return this;
    }

    // Set window resize handler for the map.
    $(window).on('resize', () => {
      google.maps.event.trigger(this._map, 'resize');
    });

    // Adds handler for highlighting and bouncing a pin when a list item gets
    // focus.
    $(this._el).on('focus', `.${OfficeMap.CssClass.LIST_LOCATION}`, e => {
      const markerId = parseInt($(e.currentTarget).data('marker'), 10);
      const location = _.findWhere(this._locations, {
        id: markerId
      });
      if (location && 'marker' in location) {
        $('html, body').animate({
          scrollTop: `${$(this._mapEl).offset().top}px`
        }, 'fast').promise().then(() => {
          this.centerOnMarker(location.marker);
          $(e.delegateTarget).find(`.${OfficeMap.CssClass.LIST_LOCATION}`)
              .removeClass(OfficeMap.CssClass.ACTIVE);
          $(e.currentTarget).addClass(OfficeMap.CssClass.ACTIVE);
        });
      }
    }).on('click', `.${OfficeMap.CssClass.MORE}`, e => {
      // Hanlder for the 'Show more' button.
      e.preventDefault();
      this.updateList().updateUrl();
    });

    // Bias the Autocomplete's results towards current map's viewport when
    // the map bounds change.
    this._map.addListener('bounds_changed', _.debounce(() => {
      this._Autocomplete.setBounds(this._map.getBounds());
    }, 100));

    // Avoid paying for data that you don't need by restricting the set of
    // place fields that are returned to just the geometry components.
    this._Autocomplete.setFields(['geometry']);

    // Attach handler for the autocomplete search box. This updates the map
    // position and re-sorts locations around that position.
    this._Autocomplete.addListener('place_changed', () => {
      const place = this._Autocomplete.getPlace();

      if (place && place.geometry) {
        this._mapPosition = place.geometry.location;
        this._map.panTo(this._mapPosition);
        this.sortByDistance()
          .clearLocations()
          .updateUrl()
          .updateList()
          .updateUrl();

        $(this._searchEl).blur();
      }
    });

    // Initialize the filter control and listen for filter updates.
    this._filter.setPrograms(this._programs).init();
    $(this._filterEl).on(OfficeFilter.Event.UPDATE, () => {
      this._programs = this._filter.getPrograms();
      this.filterLocations().updateUrl().sortByDistance().updateList()
          .updateUrl();
    });

    // Load pin data.
    this.loading(true);

    this.clearLocations(true).fetchLocations().then(() => {
      this.loading(false);
      this.filterLocations().sortByDistance();
      if (Utility.getUrlParameter('lat') || Utility.getUrlParameter('lng') ||
          Utility.getUrlParameter('programs')) {
        // These parameters indicate an initial app state, so update the map
        // and list to reflect that.
        this.updateList().updateUrl();
      } else {
        // If there is no initial application state, open the filter drawer.
        const $filterToggle =
            $(this._el).find(`.${OfficeFilter.CssClass.MAIN_TOGGLE}`);
        if (!$filterToggle.hasClass('active')) {
          $filterToggle.trigger('click');
        }
      }
    });

    this._initialized = true;

    return this;
  }

  /**
   * Clears the map markers, map listing, and, optionally, resets
   * this._locations and this._filteredLocations.
   * @method
   * @param {boolean} reset - True if resetting the entire set.
   * @return {this} OfficeMap
   */
  clearLocations(reset) {
    _.each(this._locations, location => {
      location.marker.setMap(null);
      location.active = false;
    });

    $(this._listEl).empty();

    if (reset) {
      this._locations = [];
      this._filteredLocations = [];
    }

    return this;
  }

  /**
   * Updates this._locations based on a given set of parameters. Recursively
   * makes requests to the API until all results are loaded.
   * @method
   * @return {jqXHR} - JSON response.
   */
  fetchLocations() {
    return $.getJSON($(this._el).data('source')).then(data => {
      _.each(data, item => {
        const location = new OfficeLocation(item);
        google.maps.event.addListener(location.marker, 'click', () => {
          this.focusListOnMarker(location.marker);
        });
        this._locations.push(location);
      });
    });
  }

  /**
   * Update the list of locations on the map.
   * @method
   * @return {this} OfficeMap
   */
  updateList() {
    // If there are no qualified locations, show "no results".
    if (this._filteredLocations.length === 0) {
      $(this._el).find(OfficeMap.Selectors.MESSAGE_NO_RESULTS)
        .removeClass('hidden')
        .attr('aria-hidden', false);
      $(this._listEl).empty();
      $(this._paginationEl).empty();
      return this;
    }

    // Underscore templates.
    const locationTemplate = window.JST['locations/template-card-map'];
    const paginationTemplate = window.JST['locations/template-pagination'];

    // Determine the set of locations to retrieve based on count.
    const currentCount = $(this._listEl).find('li').length;
    const newCount = Utility.getUrlParameter('count') &&
        parseInt(Utility.getUrlParameter('count'), 10) > currentCount &&
        !(parseInt(Utility.getUrlParameter('count'), 10) < 25) ?
        parseInt(Utility.getUrlParameter('count'), 10) : currentCount + 25;
    const addedLocations = this._filteredLocations
        .slice(currentCount, newCount);

    // For the locations to be added, attach their marker to the map and
    // set them to active.
    _.each(addedLocations, location => {
      location.marker.setMap(this._map);
      location.active = true;
    });

    // Update the list.
    $(this._el).find(OfficeMap.Selectors.MESSAGE_NO_RESULTS)
      .addClass('hidden')
      .attr('aria-hidden', true);

    $(this._listEl).append(locationTemplate({
      locations: addedLocations,
      localize: Utility.localize
    }));

    // Update the pagination controller.
    $(this._paginationEl).html(paginationTemplate({
      displayedCount: $(this._listEl).find('li').length,
      totalCount: this._filteredLocations.length
    }));

    // Re-zoom the map.
    this.fitMapToPins();

    return this;
  }

  /**
   * Centers the map on the marker and highlights the marker with a bouncing
   * animation.
   * @param {google.maps.Marker} marker
   * @return {this} OfficeMap
   */
  centerOnMarker(marker) {
    if (!marker) {
      return this;
    }

    this._map.panTo(marker.getPosition());

    // Bounce the marker once the pan has completed.
    // A single bounce animation is about 700ms, so the animation is set here
    // to last for three bounces (2100ms).
    google.maps.event.addListenerOnce(this._map, 'idle', () => {
      marker.setAnimation(google.maps.Animation.BOUNCE);
      _.delay(() => {
        marker.setAnimation(null);
      }, 2100);
    });

    return this;
  }

  /**
   * Centers the map on the clicked marker and highlights the accompanying
   * result list item, scrolling either the page or the result list container
   * to the item as appropraite.
   * @method
   * @param {google.maps.Marker} marker
   * @return {this} OfficeMap
   */
  focusListOnMarker(marker) {
    if (!marker) {
      return this;
    }

    // Center the map.
    this.centerOnMarker(marker);

    // Highlight the list item related to the marker.
    const $highlightedItem = $(this._el)
        .find(`.${OfficeMap.CssClass.LIST_LOCATION}`)
        .removeClass(OfficeMap.CssClass.ACTIVE)
        .filter(`[data-marker="${marker.id}"]`)
        .addClass(OfficeMap.CssClass.ACTIVE);
    const $resultContainer = $(this._el)
        .find(`.${OfficeMap.CssClass.CONTROLS}`);
    // The result container will have `overflow: scroll` on large viewports.
    // In this case we want to only scroll that control. If the result container
    // does not have `overflow: scroll` then we want to scroll the window.
    let $scrollTarget = $('html, body');
    let scrollPos = $highlightedItem.offset().top;
    // TODO(jjandoc): Is there a better conditional for this?
    if ($resultContainer.css('overflow') === 'auto' ||
        $resultContainer.css('overflow-y') === 'auto') {
      $scrollTarget = $resultContainer;
      scrollPos = $scrollTarget.scrollTop() + $highlightedItem.position().top;
    }
    $scrollTarget.animate({
      scrollTop: `${scrollPos}px`
    }, 'fast');

    return this;
  }
  /**
   * Updates the list of locations based on programs.
   * @method
   * @return {this} OfficeMap
   */
  filterLocations() {
    this.clearLocations();
    this._filteredLocations = [];
    _.each(this._locations, location => {
      if (!this._programs.length || location.hasProgram(this._programs)) {
        this._filteredLocations.push(location);
      }
    });
    return this;
  }

  /**
   * Sort this._filteredLocations by distance of markers to a given point.
   * @method
   * @param {google.maps.LatLng} origin
   * @return {this} OfficeMap
   */
  sortByDistance(origin = this._mapPosition) {
    _.each(this._filteredLocations, location => {
      location.distance =
          google.maps.geometry.spherical.computeDistanceBetween(origin,
              location.marker.position);
    });
    this._filteredLocations = _.sortBy(this._filteredLocations, 'distance');

    return this;
  }

  /**
   * Updates the query parameters in the URL.
   * @method
   * @return {this} OfficeMap
   */
  updateUrl() {
    if ('replaceState' in window.history) {
      const mapState = {
        lat: this._mapPosition.lat(),
        lng: this._mapPosition.lng()
      };
      if (this._programs.length) {
        mapState.programs = this._programs.join(',');
      }
      const locationCount = _.filter(this._filteredLocations, location =>
          location.active).length;
      if (locationCount) {
        mapState.count = locationCount;
      }
      window.history.replaceState(null, null, `?${$.param(mapState)}`);
    }

    return this;
  }

  /**
   * Sets the zoom level of the map to fit all active markers.
   * @method
   * @return {this} OfficeMap
   */
  fitMapToPins() {
    const bounds = new google.maps.LatLngBounds();
    _.each(this._filteredLocations, location => {
      if (location.active) {
        bounds.extend(location.marker.getPosition());
      }
    });
    this._map.fitBounds(bounds);
    return this;
  }

  /**
   * Method for the loading state
   * @param  {boolean} isLoading Wether or not the map is loading
   * @return {this} OfficeMap
   */
  loading(isLoading) {
    if (isLoading) {
      $(this._el).find(OfficeMap.Selectors.MESSAGE_LOADING)
        .removeClass('hidden')
        .attr('aria-hidden', false);
    } else {
      $(this._el).find(OfficeMap.Selectors.MESSAGE_LOADING)
        .addClass('hidden')
        .attr('aria-hidden', true);
    }
    return this;
  }
}

/* eslint-enable no-undef */

/**
 * CSS classes used by this component.
 * @enum {string}
 */
OfficeMap.CssClass = {
  ACTIVE: 'active bg-color-yellow-light',
  CONTROLS: 'js-map-controls',
  FILTER: 'js-map-filter',
  LIST_LOCATION: 'js-map-location',
  LOADING: 'loading',
  MAP_BOX: 'js-map-mapbox',
  MORE: 'js-map-more',
  NO_RESULTS: 'no-results',
  PAGINATION: 'js-map-pagination',
  RESULT_CONTAINER: 'js-map-results-container',
  RESULT_LIST: 'js-map-results',
  SEARCH_BOX: 'js-map-searchbox'
};

OfficeMap.Selectors = {
  MAIN: '[data-js="google-maps-embed"]',
  MESSAGE_LOADING: '[data-js="message-loading"]',
  MESSAGE_NO_RESULTS: '[data-js="message-no-results"]'
};

export default OfficeMap;
