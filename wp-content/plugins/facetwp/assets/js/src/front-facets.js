(function($) {

    FWP.logic = FWP.logic || {};

    /* ======== IE11 .val() fix ======== */

    $.fn.pVal = function() {
        var val = $(this).eq(0).val();
        return val === $(this).attr('placeholder') ? '' : val;
    }

    /* ======== Autocomplete ======== */

    wp.hooks.addAction('facetwp/refresh/autocomplete', function($this, facet_name) {
        var val = $this.find('.facetwp-autocomplete').val() || '';
        FWP.facets[facet_name] = val;
    });

    $(document).on('facetwp-loaded', function() {
        $('.facetwp-autocomplete:not(.ready)').each(function() {
            var $this = $(this);
            var $parent = $this.closest('.facetwp-facet');
            var facet_name = $parent.attr('data-name');
            var opts = wp.hooks.applyFilters('facetwp/set_options/autocomplete', {
                serviceUrl: ('wp' === FWP.template) ? document.URL : FWP_JSON.ajaxurl,
                type: 'POST',
                minChars: 3,
                deferRequestBy: 200,
                showNoSuggestionNotice: true,
                noSuggestionNotice: FWP_JSON['no_results'],
                params: {
                    action: 'facetwp_autocomplete_load',
                    facet_name: facet_name,
                    data: FWP.build_post_data()
                }
            }, { 'facet_name': facet_name });
            $this.autocomplete(opts);
            $this.addClass('ready');
        });
    });

    $(document).on('keyup', '.facetwp-autocomplete', function(e) {
        if (13 === e.which) {
            FWP.autoload();
        }
    });

    $(document).on('click', '.facetwp-autocomplete-update', function() {
        FWP.autoload();
    });

    /* ======== Checkboxes ======== */

    wp.hooks.addAction('facetwp/refresh/checkboxes', function($this, facet_name) {
        var selected_values = [];
        $this.find('.facetwp-checkbox.checked').each(function() {
            selected_values.push($(this).attr('data-value'));
        });
        FWP.facets[facet_name] = selected_values;
    });

    wp.hooks.addFilter('facetwp/selections/checkboxes', function(output, params) {
        var choices = [];
        $.each(params.selected_values, function(idx, val) {
            var choice = params.el.find('.facetwp-checkbox[data-value="' + val + '"]').clone();
            choice.find('.facetwp-counter').remove();
            choice.find('.facetwp-expand').remove();
            choices.push({
                value: val,
                label: choice.text()
            });
        });
        return choices;
    });

    $(document).on('click', '.facetwp-type-checkboxes .facetwp-expand', function(e) {
        var $wrap = $(this).parent('.facetwp-checkbox').next('.facetwp-depth');
        $wrap.toggleClass('visible');
        var content = $wrap.hasClass('visible') ? FWP_JSON['collapse'] : FWP_JSON['expand'];
        $(this).text(content);
        e.stopPropagation();
    });

    $(document).on('click', '.facetwp-type-checkboxes .facetwp-checkbox:not(.disabled)', function() {
        $(this).toggleClass('checked');
        FWP.autoload();
    });

    $(document).on('click', '.facetwp-type-checkboxes .facetwp-toggle', function() {
        var $parent = $(this).closest('.facetwp-facet');
        $parent.find('.facetwp-toggle').toggleClass('facetwp-hidden');
        $parent.find('.facetwp-overflow').toggleClass('facetwp-hidden');
    });

    $(document).on('facetwp-loaded', function() {
        $('.facetwp-type-checkboxes .facetwp-overflow').each(function() {
            var num = $(this).find('.facetwp-checkbox').length;
            var $el = $(this).siblings('.facetwp-toggle:first');
            $el.text($el.text().replace('{num}', num));
        });

        // are children visible?
        $('.facetwp-type-checkboxes').each(function() {
            var $facet = $(this);
            var name = $facet.attr('data-name');

            // error handling
            if (Object.keys(FWP.settings).length < 1) {
                return;
            }

            // hierarchy toggles
            if ('yes' === FWP.settings[name]['show_expanded']) {
                $facet.find('.facetwp-depth').addClass('visible');
            }

            if (1 > $facet.find('.facetwp-expand').length) {
                $facet.find('.facetwp-depth').each(function() {
                    var which = $(this).hasClass('visible') ? 'collapse' : 'expand';
                    $(this).prev('.facetwp-checkbox').append(' <span class="facetwp-expand">' + FWP_JSON[which] + '</span>');
                });

                // un-hide groups with selected items
                $facet.find('.facetwp-checkbox.checked').each(function() {
                    $(this).parents('.facetwp-depth').each(function() {
                        $(this).prev('.facetwp-checkbox').find('.facetwp-expand').text(FWP_JSON['collapse']);
                        $(this).addClass('visible');
                    });

                    // show children of selected items
                    $(this).find('.facetwp-expand').trigger('click');
                });
            }
        });
    });

    /* ======== Radio ======== */

    wp.hooks.addAction('facetwp/refresh/radio', function($this, facet_name) {
        var selected_values = [];
        $this.find('.facetwp-radio.checked').each(function() {
            selected_values.push($(this).attr('data-value'));
        });
        FWP.facets[facet_name] = selected_values;
    });

    wp.hooks.addFilter('facetwp/selections/radio', function(output, params) {
        var choices = [];
        $.each(params.selected_values, function(idx, val) {
            var choice = params.el.find('.facetwp-radio[data-value="' + val + '"]').clone();
            choice.find('.facetwp-counter').remove();
            choices.push({
                value: val,
                label: choice.text()
            });
        });
        return choices;
    });

    $(document).on('click', '.facetwp-type-radio .facetwp-radio:not(.disabled)', function() {
        var is_checked = $(this).hasClass('checked');
        $(this).closest('.facetwp-facet').find('.facetwp-radio').removeClass('checked');
        if (! is_checked) {
            $(this).addClass('checked');
        }
        FWP.autoload();
    });

    /* ======== Date Range ======== */

    wp.hooks.addAction('facetwp/refresh/date_range', function($this, facet_name) {
        var min = $this.find('.facetwp-date-min').pVal() || '';
        var max = $this.find('.facetwp-date-max').pVal() || '';
        FWP.facets[facet_name] = ('' !== min || '' !== max) ? [min, max] : [];
    });

    wp.hooks.addFilter('facetwp/selections/date_range', function(output, params) {
        var vals = params.selected_values;
        var $el = params.el;
        var out = '';

        if ('' !== vals[0]) {
            out += ' from ' + $el.find('.facetwp-date-min').next().val();
        }
        if ('' !== vals[1]) {
            out += ' to ' + $el.find('.facetwp-date-max').next().val();
        }
        return out;
    });

    $(document).on('facetwp-loaded', function() {
        var $dates = $('.facetwp-type-date_range .facetwp-date:not(".ready, .flatpickr-alt")');

        if (0 === $dates.length) {
            return;
        }

        var flatpickr_opts = {
            altInput: true,
            altInputClass: 'flatpickr-alt',
            altFormat: 'Y-m-d',
            disableMobile: true,
            locale: FWP_JSON.datepicker.locale,
            onChange: function() {
                FWP.autoload();
            },
            onReady: function(dateObj, dateStr, instance) {
                var clearBtn = '<div class="flatpickr-clear">' + FWP_JSON.datepicker.clearText + '</div>';
                $(clearBtn).on('click', function() {
                        instance.clear();
                        instance.close();
                })
                .appendTo($(instance.calendarContainer));
            }
        };

        $dates.each(function() {
            var $this = $(this);
            var facet_name = $this.closest('.facetwp-facet').attr('data-name');
            flatpickr_opts.altFormat = FWP.settings[facet_name].format;

            var opts = wp.hooks.applyFilters('facetwp/set_options/date_range', flatpickr_opts, {
                'facet_name': facet_name,
                'element': $this
            });
            new flatpickr(this, opts);
            $this.addClass('ready');
        });
    });

    /* ======== Dropdown ======== */

    wp.hooks.addAction('facetwp/refresh/dropdown', function($this, facet_name) {
        var val = $this.find('.facetwp-dropdown').val();
        FWP.facets[facet_name] = val ? [val] : [];
    });

    wp.hooks.addFilter('facetwp/selections/dropdown', function(output, params) {
        return params.el.find('.facetwp-dropdown option:selected').text();
    });

    $(document).on('change', '.facetwp-type-dropdown select', function() {
        var $facet = $(this).closest('.facetwp-facet');
        var facet_name = $facet.attr('data-name');

        if ('' !== $facet.find(':selected').val()) {
            FWP.frozen_facets[facet_name] = 'soft';
        }
        FWP.autoload();
    });

    /* ======== fSelect ======== */

    wp.hooks.addAction('facetwp/refresh/fselect', function($this, facet_name) {
        var val = $this.find('select').val();
        if (null === val || '' === val) {
            val = [];
        }
        else if (false === $.isArray(val)) {
            val = [val];
        }
        FWP.facets[facet_name] = val;
    });

    wp.hooks.addFilter('facetwp/selections/fselect', function(output, params) {
        var choices = [];
        $.each(params.selected_values, function(idx, val) {
            var choice = params.el.find('.facetwp-dropdown option[value="' + val + '"]').text();
            choices.push({
                value: val,
                label: choice.replace(/{{(.*?)}}/, '')
            });
        });
        return choices;
    });

    $(document).on('facetwp-loaded', function() {
        $('.facetwp-type-fselect select:not(.ready)').each(function() {
            var facet_name = $(this).closest('.facetwp-facet').attr('data-name');
            var settings = FWP.settings[facet_name];
            var opts = wp.hooks.applyFilters('facetwp/set_options/fselect', {
                placeholder: settings.placeholder,
                overflowText: settings.overflowText,
                searchText: settings.searchText,
                optionFormatter: function(row) {
                    row = row.replace(/{{/g, '<span class="facetwp-counter">');
                    row = row.replace(/}}/g, '<span>');
                    return row;
                }
            }, { 'facet_name': facet_name });

            $(this).fSelect(opts);
            $(this).addClass('ready');
        });

        // unfreeze choices
        $('.fs-wrap.fs-disabled').removeClass('fs-disabled');
    });

    $(document).on('fs:changed', function(e, wrap) {
        if (wrap.classList.contains('multiple')) {
            var facet_name = $(wrap).closest('.facetwp-facet').attr('data-name');

            if ('or' === FWP.settings[facet_name]['operator']) {
                FWP.frozen_facets[facet_name] = 'soft';

                // freeze choices
                if (FWP.auto_refresh) {
                    $(wrap).addClass('fs-disabled');
                }
            }

            FWP.autoload();
        }
    });

    $(document).on('fs:closed', function(e, wrap) {
        if (! wrap.classList.contains('multiple')) {
            FWP.autoload();
        }
    });

    /* ======== Hierarchy ======== */

    wp.hooks.addAction('facetwp/refresh/hierarchy', function($this, facet_name) {
        var selected_values = [];
        $this.find('.facetwp-link.checked').each(function() {
            selected_values.push($(this).attr('data-value'));
        });
        FWP.facets[facet_name] = selected_values;
    });

    wp.hooks.addFilter('facetwp/selections/hierarchy', function(output, params) {
        return params.el.find('.facetwp-link.checked').text();
    });

    $(document).on('click', '.facetwp-facet .facetwp-link', function() {
        $(this).closest('.facetwp-facet').find('.facetwp-link').removeClass('checked');
        if ('' !== $(this).attr('data-value')) {
            $(this).addClass('checked');
        }
        FWP.autoload();
    });

    $(document).on('click', '.facetwp-type-hierarchy .facetwp-toggle', function() {
        var $parent = $(this).closest('.facetwp-facet');
        $parent.find('.facetwp-toggle').toggleClass('facetwp-hidden');
        $parent.find('.facetwp-overflow').toggleClass('facetwp-hidden');
    });

    /* ======== Number Range ======== */

    wp.hooks.addAction('facetwp/refresh/number_range', function($this, facet_name) {
        var min = $this.find('.facetwp-number-min').val() || '';
        var max = $this.find('.facetwp-number-max').val() || '';
        FWP.facets[facet_name] = ('' !== min || '' !== max) ? [min, max] : [];
    });

    wp.hooks.addFilter('facetwp/selections/number_range', function(output, params) {
        return params.selected_values[0] + ' - ' + params.selected_values[1];
    });

    $(document).on('click', '.facetwp-type-number_range .facetwp-submit', function() {
        FWP.refresh();
    });

    /* ======== Proximity ======== */

    $(document).on('facetwp-loaded', function() {
        var $locations = $('.facetwp-location');

        if ($locations.length < 1) {
            return;
        }

        $locations.each(function(idx, el) {
            var $input = $(this);

            if ($input.closest('.location-wrap').length < 1) {

                // Select the first choice
                (function pacSelectFirst(input) {
                    var _addEventListener = input.addEventListener;

                    function addEventListenerWrapper(type, listener) {
                        if ('keydown' === type) {
                            var orig_listener = listener;
                            listener = function(event) {
                                if (13 === event.which && 0 === $('.pac-container .pac-item-selected').length) {
                                    var simulated_downarrow = $.Event('keydown', {keyCode: 40, which: 40});
                                    orig_listener.apply(input, [simulated_downarrow]); // down arrow
                                }
                                orig_listener.apply(input, [event]); // original event
                            }
                        }
                        _addEventListener.apply(input, [type, listener]);
                    }
                    input.addEventListener = addEventListenerWrapper;

                    var options = FWP_JSON['proximity']['autocomplete_options'];
                    var autocomplete = new google.maps.places.Autocomplete(input, options);

                    google.maps.event.addListener(autocomplete, 'place_changed', function() {
                        var place = autocomplete.getPlace();
                        if ('undefined' !== typeof place.geometry) {
                            var $facet = $(input).closest('.facetwp-facet');
                            $facet.find('.facetwp-lat').val(place.geometry.location.lat());
                            $facet.find('.facetwp-lng').val(place.geometry.location.lng());
                            FWP.autoload();
                        }
                    });
                })($input[0]);

                // Preserve CSS IDs
                if (0 === idx) {
                    $input.attr('id', 'facetwp-location');
                    $input.closest('.facetwp-facet').find('.facetwp-radius').attr('id', 'facetwp-radius');
                }

                // Add the "Locate me" icon
                $input.wrap('<span class="location-wrap"></span>');
                $input.before('<i class="locate-me"></i>');
            }

            $input.trigger('keyup');
        });
    });

    $(document).on('click', '.facetwp-type-proximity .locate-me', function(e) {
        var $this = $(this);
        var $facet = $this.closest('.facetwp-facet');
        var $input = $facet.find('.facetwp-location');
        var $lat = $facet.find('.facetwp-lat');
        var $lng = $facet.find('.facetwp-lng');

        // reset
        if ($this.hasClass('f-reset')) {
            $lat.val('');
            $lat.val('');
            $input.val('');
            FWP.autoload();
            return;
        }

        // loading icon
        $this.addClass('f-loading');

        // HTML5 geolocation
        navigator.geolocation.getCurrentPosition(function(position) {
            var lat = position.coords.latitude;
            var lng = position.coords.longitude;

            $lat.val(lat);
            $lng.val(lng);

            var geocoder = new google.maps.Geocoder();
            var latlng = {lat: parseFloat(lat), lng: parseFloat(lng)};
            geocoder.geocode({'location': latlng}, function(results, status) {
                if (status === google.maps.GeocoderStatus.OK) {
                    $input.val(results[0].formatted_address);
                }
                else {
                    $input.val('Your location');
                }
                $this.addClass('f-reset');
                FWP.autoload();
            });

            $this.removeClass('f-loading');
        },
        function() {
            $this.removeClass('f-loading');
        });
    });

    $(document).on('keyup', '.facetwp-location', function() {
        var $facet = $(this).closest('.facetwp-facet');
        $facet.find('.locate-me').toggleClass('f-reset', ('' !== $(this).val()));
    });

    $(document).on('change', '.facetwp-radius', function() {
        var $facet = $(this).closest('.facetwp-facet');
        if ('' !== $facet.find('.facetwp-location').val()) {
            FWP.autoload();
        }
    });

    $(document).on('input', '.facetwp-radius-slider', function(e) {
        var $facet = $(this).closest('.facetwp-facet');
        $facet.find('.facetwp-radius-dist').text(e.target.value);
    });

    wp.hooks.addAction('facetwp/refresh/proximity', function($this, facet_name) {
        var lat = $this.find('.facetwp-lat').val();
        var lng = $this.find('.facetwp-lng').val();
        var radius = $this.find('.facetwp-radius').val();
        var location = encodeURIComponent($this.find('.facetwp-location').val());
        FWP.frozen_facets[facet_name] = 'hard';
        FWP.facets[facet_name] = ('' !== lat && 'undefined' !== typeof lat) ?
            [lat, lng, radius, location] : [];
    });

    wp.hooks.addFilter('facetwp/selections/proximity', function(label, params) {
        return FWP_JSON['proximity']['clearText'];
    });

    /* ======== Search ======== */

    FWP.logic.search = {
        set_button: function($facet) {
            var val = $facet.find('.facetwp-search').val();
            $facet.find('.facetwp-btn').toggleClass('f-reset', ('' !== val));
        },
        delay_refresh: FWP.helper.debounce(function(facet_name) {
            FWP.frozen_facets[facet_name] = 'soft';
            FWP.autoload();
        }, 250)
    };

    wp.hooks.addAction('facetwp/refresh/search', function($this, facet_name) {
        var val = $this.find('.facetwp-search').val() || '';
        FWP.facets[facet_name] = val;
    });

    $(document).on('facetwp-loaded', function() {
        $('.facetwp-facet .facetwp-search').each(function() {
            var $facet = $(this).closest('.facetwp-facet');
            FWP.logic.search['set_button']($facet);
        });
    });

    $(document).on('keyup', '.facetwp-facet .facetwp-search', function(e) {
        var $facet = $(this).closest('.facetwp-facet');
        var facet_name = $facet.attr('data-name');

        FWP.logic.search['set_button']($facet);

        if ('undefined' !== typeof FWP.settings[facet_name]) {
            if ('yes' === FWP.settings[facet_name]['auto_refresh']) {
                FWP.logic.search['delay_refresh'](facet_name);
            }
            else if (13 === e.keyCode) {
                if ('' === $facet.find('.facetwp-search').val()) {
                    $facet.find('.facetwp-btn').click();
                }
                else {
                    FWP.autoload();
                }
            }
        }
    });

    $(document).on('click', '.facetwp-type-search .facetwp-btn', function(e) {
        var $this = $(this);
        var $facet = $this.closest('.facetwp-facet');
        var facet_name = $facet.attr('data-name');

        if ($this.hasClass('f-reset') || '' === $facet.find('.facetwp-search').val()) {
            $facet.find('.facetwp-search').val('');
            FWP.facets[facet_name] = [];
            FWP.set_hash();
            FWP.fetch_data();
        }
    });

    /* ======== Slider ======== */

    wp.hooks.addAction('facetwp/refresh/slider', function($this, facet_name) {
        FWP.facets[facet_name] = [];

        // settings have already been loaded
        if ('undefined' !== typeof FWP.frozen_facets[facet_name]) {
            if ('undefined' !== typeof $this.find('.facetwp-slider')[0].noUiSlider) {
                FWP.facets[facet_name] = $this.find('.facetwp-slider')[0].noUiSlider.get();
            }
        }
    });

    wp.hooks.addAction('facetwp/set_label/slider', function($this) {
        var facet_name = $this.attr('data-name');
        var min = FWP.settings[facet_name]['lower'];
        var max = FWP.settings[facet_name]['upper'];
        var format = FWP.settings[facet_name]['format'];
        var opts = {
            decimal_separator: FWP.settings[facet_name]['decimal_separator'],
            thousands_separator: FWP.settings[facet_name]['thousands_separator']
        };

        if ( min === max ) {
            var label = FWP.settings[facet_name]['prefix']
                + nummy(min).format(format, opts)
                + FWP.settings[facet_name]['suffix'];
        }
        else {
            var label = FWP.settings[facet_name]['prefix']
                + nummy(min).format(format, opts)
                + FWP.settings[facet_name]['suffix']
                + ' &mdash; '
                + FWP.settings[facet_name]['prefix']
                + nummy(max).format(format, opts)
                + FWP.settings[facet_name]['suffix'];
        }
        $this.find('.facetwp-slider-label').html(label);
    });

    wp.hooks.addFilter('facetwp/selections/slider', function(output, params) {
        return params.el.find('.facetwp-slider-label').text();
    });

    $(document).on('facetwp-loaded', function() {
        $('.facetwp-slider:not(.ready)').each(function() {
            var $parent = $(this).closest('.facetwp-facet');
            var facet_name = $parent.attr('data-name');
            var opts = FWP.settings[facet_name];

            // on first load, check for slider URL variable
            if (false !== FWP.helper.get_url_var(facet_name)) {
                FWP.frozen_facets[facet_name] = 'hard';
            }

            // fail on slider already initialized
            if ('undefined' !== typeof $(this).data('options')) {
                return;
            }

            // fail if start values are null
            if (null === FWP.settings[facet_name].start[0]) {
                return;
            }

            // fail on invalid ranges
            if (parseFloat(opts.range.min) >= parseFloat(opts.range.max)) {
                FWP.settings[facet_name]['lower'] = opts.range.min;
                FWP.settings[facet_name]['upper'] = opts.range.max;
                wp.hooks.doAction('facetwp/set_label/slider', $parent);
                return;
            }

            // custom slider options
            var slider_opts = wp.hooks.applyFilters('facetwp/set_options/slider', {
                range: opts.range,
                start: opts.start,
                step: parseFloat(opts.step),
                connect: true
            }, { 'facet_name': facet_name });


            var slider = $(this)[0];
            noUiSlider.create(slider, slider_opts);
            slider.noUiSlider.on('update', function(values, handle) {
                FWP.settings[facet_name]['lower'] = values[0];
                FWP.settings[facet_name]['upper'] = values[1];
                wp.hooks.doAction('facetwp/set_label/slider', $parent);
            });
            slider.noUiSlider.on('set', function() {
                FWP.frozen_facets[facet_name] = 'hard';
                FWP.autoload();
            });

            $(this).addClass('ready');
        });

        // hide reset buttons
        $('.facetwp-type-slider').each(function() {
            var name = $(this).attr('data-name');
            var $button = $(this).find('.facetwp-slider-reset');
            $.isEmptyObject(FWP.facets[name]) ? $button.hide() : $button.show();
        });
    });

    $(document).on('click', '.facetwp-slider-reset', function() {
        var facet_name = $(this).closest('.facetwp-facet').attr('data-name');
        FWP.reset(facet_name);
    });

})(jQuery);