window.FWP = window.FWP || {};

(function($) {

    function isset(obj) {
        return 'undefined' !== typeof obj;
    }

    var defaults = {
        'facets': {},
        'template': null,
        'settings': {},
        'is_reset': false,
        'is_refresh': false,
        'is_bfcache': false,
        'auto_refresh': true,
        'soft_refresh': false,
        'frozen_facets':{},
        'facet_type': {},
        'loaded': false,
        'jqXHR': false,
        'extras': {},
        'helper': {},
        'paged': 1
    };

    for (var prop in defaults) {
        if (! isset(FWP[prop])) {
            FWP[prop] = defaults[prop];
        }
    }

    // Safari popstate fix
    $(window).on('load', function() {
        setTimeout(function() {
            $(window).on('popstate', function() {

                // Detect browser "back-foward" cache
                if (FWP.is_bfcache) {
                    FWP.loaded = false;
                }

                if ((FWP.loaded || FWP.is_bfcache) && ! FWP.is_refresh) {
                    FWP.is_popstate = true;
                    FWP.refresh();
                    FWP.is_popstate = false;
                }
            });
        }, 0);
    });


    FWP.helper.get_url_var = function(name) {
        var name = FWP_JSON.prefix + name;
        var query_string = FWP.build_query_string();
        var url_vars = query_string.split('&');
        for (var i = 0; i < url_vars.length; i++) {
            var item = url_vars[i].split('=');
            if (item[0] === name) {
                return item[1];
            }
        }
        return false;
    }


    FWP.helper.debounce = function(func, wait) {
        var timeout;
        return function() {
            var context = this;
            var args = arguments;
            var later = function() {
                timeout = null;
                func.apply(context, args);
            }
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        }
    }


    FWP.helper.serialize = function(obj, prefix) {
        var str = [];
        var prefix = isset(prefix) ? prefix : '';
        for (var p in obj) {
            if ('' != obj[p]) { // This must be "!=" instead of "!=="
                str.push(prefix + encodeURIComponent(p) + '=' + encodeURIComponent(obj[p]));
            }
        }
        return str.join('&');
    }


    FWP.helper.escape_html = function(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; }).trim();
    }


    FWP.helper.detect_loop = function(node) {
        var curNode = null;
        var iterator = document.createNodeIterator(node, NodeFilter.SHOW_COMMENT, FWP.helper.node_filter, false);
        while (curNode = iterator.nextNode()) {
            if (8 === curNode.nodeType && 'fwp-loop' === curNode.nodeValue) {
                return curNode.parentNode;
            }
        }

        return false;
    }


    FWP.helper.node_filter = function() {
        return NodeFilter.FILTER_ACCEPT;
    }


    // Refresh on each facet interaction?
    FWP.autoload = function() {
        if (FWP.auto_refresh && ! FWP.is_refresh) {
            FWP.refresh();
        }
    }


    FWP.refresh = function() {
        FWP.is_refresh = true;

        // Load facet DOM values
        if (! FWP.is_reset) {
            FWP.parse_facets();
        }

        // Check the URL on pageload
        if (! FWP.loaded) {
            FWP.load_from_hash();
        }

        // Fire a notification event
        $(document).trigger('facetwp-refresh');

        // Trigger window.onpopstate
        if (FWP.loaded && ! FWP.is_popstate) {
            FWP.set_hash();
        }

        // Preload?
        if (! FWP.loaded && ! FWP.is_bfcache && isset(FWP_JSON.preload_data)) {
            FWP.render(FWP_JSON.preload_data);
        }
        else {
            FWP.fetch_data();
        }

        // Unfreeze any soft-frozen facets
        $.each(FWP.frozen_facets, function(name, freeze_type) {
            if ('hard' !== freeze_type) {
                delete FWP.frozen_facets[name];
            }
        });

        // Cleanup
        FWP.paged = 1;
        FWP.soft_refresh = false;
        FWP.is_refresh = false;
        FWP.is_reset = false;
    }


    FWP.parse_facets = function() {
        FWP.facets = {};

        $('.facetwp-facet').each(function() {
            var $this = $(this);
            var facet_name = $this.attr('data-name');
            var facet_type = $this.attr('data-type');

            // Store the facet type
            FWP.facet_type[facet_name] = facet_type;

            // Plugin hook
            wp.hooks.doAction('facetwp/refresh/' + facet_type, $this, facet_name);

            // Support custom loader
            var do_loader = true;
            if (FWP.loaded) {
                if (FWP.soft_refresh || isset(FWP.frozen_facets[facet_name])) {
                    do_loader = false;
                }
            }

            if (do_loader) {
                FWP.loading_handler({
                    'element': $this,
                    'facet_name': facet_name,
                    'facet_type': facet_type
                });
            }
        });

        // Add pagination to the URL hash
        if (1 < FWP.paged) {
            FWP.facets['paged'] = FWP.paged;
        }

        // Add "per page" to the URL hash
        if (FWP.extras.per_page && 'default' !== FWP.extras.per_page) {
            FWP.facets['per_page'] = FWP.extras.per_page;
        }

        // Add sorting to the URL hash
        if (FWP.extras.sort && 'default' !== FWP.extras.sort) {
            FWP.facets['sort'] = FWP.extras.sort;
        }
    }


    FWP.loading_handler = function(args) {
        if ('fade' == FWP_JSON.loading_animation) {
            if (! FWP.loaded) {
                var $el = args.element;
                $(document).on('facetwp-refresh', function() {
                    $el.prepend('<div class="facetwp-overlay">');
                    $el.find('.facetwp-overlay').css({
                        width: $el.width(),
                        height: $el.height()
                    });
                });

                $(document).on('facetwp-loaded', function() {
                    $el.find('.facetwp-overlay').remove();
                });
            }
        }
        else if ('' == FWP_JSON.loading_animation) {
            args.element.html('<div class="facetwp-loading"></div>');
        }
    }


    FWP.build_query_string = function() {
        var query_string = '';

        // Non-FacetWP URL variables
        var hash = [];
        var get_str = window.location.search.replace('?', '').split('&');
        $.each(get_str, function(idx, val) {
            var param_name = val.split('=')[0];
            if (0 !== param_name.indexOf(FWP_JSON.prefix)) {
                hash.push(val);
            }
        });
        hash = hash.join('&');

        // FacetWP URL variables
        var fwp_vars = FWP.helper.serialize(FWP.facets, FWP_JSON.prefix);

        if ('' !== hash) {
            query_string += hash;
        }
        if ('' !== fwp_vars) {
            query_string += ('' !== hash ? '&' : '') + fwp_vars;
        }

        return query_string;
    }


    FWP.set_hash = function() {
        var query_string = FWP.build_query_string();

        if ('' !== query_string) {
            query_string = '?' + query_string;
        }

        if (history.pushState) {
            history.pushState(null, null, window.location.pathname + query_string);
        }

        // Update FWP_HTTP.get
        FWP_HTTP.get = {};
        window.location.search.replace('?', '').split('&').forEach(function(el) {
            var item = el.split('=');
            FWP_HTTP.get[item[0]] = item[1];
        });
    }


    FWP.load_from_hash = function() {
        var hash = [];
        var get_str = window.location.search.replace('?', '').split('&');
        $.each(get_str, function(idx, val) {
            var param_name = val.split('=')[0];
            if (0 === param_name.indexOf(FWP_JSON.prefix)) {
                hash.push(val.replace(FWP_JSON.prefix, ''));
            }
        });
        hash = hash.join('&');

        // Reset facet values
        $.each(FWP.facets, function(f) {
            FWP.facets[f] = [];
        });

        FWP.paged = 1;
        FWP.extras.sort = 'default';

        if ('' !== hash) {
            hash = hash.split('&');
            $.each(hash, function(idx, chunk) {
                var obj = chunk.split('=')[0];
                var val = chunk.split('=')[1];

                if ('paged' === obj) {
                    FWP.paged = val;
                }
                else if ('per_page' === obj || 'sort' === obj) {
                    FWP.extras[obj] = val;
                }
                else if ('' !== val) {
                    var type = isset(FWP.facet_type[obj]) ? FWP.facet_type[obj] : '';
                    if ('search' === type || 'autocomplete' === type) {
                        FWP.facets[obj] = decodeURIComponent(val);
                    }
                    else {
                        FWP.facets[obj] = decodeURIComponent(val).split(',');
                    }
                }
            });
        }
    }


    FWP.build_post_data = function() {
        return {
            'facets': JSON.stringify(FWP.facets),
            'frozen_facets': FWP.frozen_facets,
            'http_params': FWP_HTTP,
            'template': FWP.template,
            'extras': FWP.extras,
            'soft_refresh': FWP.soft_refresh ? 1 : 0,
            'is_bfcache': FWP.is_bfcache ? 1 : 0,
            'first_load': FWP.loaded ? 0 : 1,
            'paged': FWP.paged
        };
    }


    FWP.fetch_data = function() {
        // Abort pending requests
        if (FWP.jqXHR && FWP.jqXHR.readyState !== 4) {
            FWP.jqXHR.abort();
        }

        var endpoint = ('wp' === FWP.template) ? document.URL : FWP_JSON.ajaxurl;

        var settings = {
            type: 'POST',
            dataType: 'text', // for better JSON error handling
            data: {
                action: 'facetwp_refresh',
                data: FWP.build_post_data()
            },
            success: function(response) {
                try {
                    var json_object = $.parseJSON(response);
                    FWP.render(json_object);
                }
                catch(e) {
                    var pos = response.indexOf('{"facets');
                    if (-1 < pos) {
                        var error = response.substr(0, pos);
                        var json_object = $.parseJSON(response.substr(pos));
                        FWP.render(json_object);

                        // Log the error
                        console.log(error);
                    }
                    else {
                        $('.facetwp-template').text('FacetWP was unable to auto-detect the post listing');

                        // Log the error
                        console.log(response);
                    }
                }
            }
        };

        settings = wp.hooks.applyFilters('facetwp/ajax_settings', settings );
        FWP.jqXHR = $.ajax(endpoint, settings);
    }


    FWP.render = function(response) {

        // Don't render CSS-based (or empty) templates on pageload
        // The template has already been pre-loaded
        if (('wp' === FWP.template || '' === response.template) && ! FWP.loaded && ! FWP.is_bfcache) {
            var inject = false;
        }
        else {
            var inject = response.template;

            if ('wp' === FWP.template) {
                var $tpl = $(response.template).find('.facetwp-template');

                if (1 > $tpl.length) {
                    var wrap = document.createElement('div');
                    wrap.innerHTML = response.template;
                    var loop = FWP.helper.detect_loop(wrap);

                    if (loop) {
                        $tpl = $(loop).addClass('facetwp-template');
                    }
                }

                if (0 < $tpl.length) {
                    var inject = $tpl.html();
                }
                else {
                    // Fallback until "loop_no_results" action is added to WP core
                    var inject = FWP_JSON['no_results_text'];
                }
            }
        }

        if (false !== inject) {
            if (! wp.hooks.applyFilters('facetwp/template_html', false, { 'response': response, 'html': inject })) {
                $('.facetwp-template').html(inject);
            }
        }

        // Populate each facet box
        $.each(response.facets, function(name, val) {
            $('.facetwp-facet-' + name).html(val);
        });

        // Populate the counts
        if (isset(response.counts)) {
            $('.facetwp-counts').html(response.counts);
        }

        // Populate the pager
        if (isset(response.pager)) {
            $('.facetwp-pager').html(response.pager);
        }

        // Populate the "per page" box
        if (isset(response.per_page)) {
            $('.facetwp-per-page').html(response.per_page);
            if ('default' !== FWP.extras.per_page) {
                $('.facetwp-per-page-select').val(FWP.extras.per_page);
            }
        }

        // Populate the sort box
        if (isset(response.sort)) {
            $('.facetwp-sort').html(response.sort);
            $('.facetwp-sort-select').val(FWP.extras.sort);
        }

        // Populate the settings object (iterate to preserve static facet settings)
        $.each(response.settings, function(key, val) {
            FWP.settings[key] = val;
        });

        // WP Playlist support
        if ('function' === typeof WPPlaylistView) {
            $('.facetwp-template .wp-playlist').each(function() {
                return new WPPlaylistView({ el: this });
            });
        }

        // Fire a notification event
        $(document).trigger('facetwp-loaded');

        // Allow final actions
        wp.hooks.doAction('facetwp/loaded');

        // Detect "back-forward" cache
        FWP.is_bfcache = true;

        // Done loading?
        FWP.loaded = true;
    }


    FWP.reset = function(facet_name, facet_value) {
        FWP.parse_facets();

        if (isset(facet_name)) {
            var values = FWP.facets[facet_name];
            if (isset(facet_value) && values.length > 1) {
                var arr_idx = values.indexOf(facet_value);
                if (-1 < arr_idx) {
                    values.splice(arr_idx, 1);
                    FWP.facets[facet_name] = values;
                }
            }
            else {
                FWP.facets[facet_name] = [];
                delete FWP.frozen_facets[facet_name];
            }
        }
        else {
            $.each(FWP.facets, function(f) {
                FWP.facets[f] = [];
            });

            FWP.extras.sort = 'default';
            FWP.frozen_facets = {};
        }

        wp.hooks.doAction('facetwp/reset');

        FWP.is_reset = true;
        FWP.refresh();
    }


    FWP.init = function() {
        if (0 < $('.facetwp-sort').length) {
            FWP.extras.sort = 'default';
        }

        if (0 < $('.facetwp-pager').length) {
            FWP.extras.pager = true;
        }

        if (0 < $('.facetwp-per-page').length) {
            FWP.extras.per_page = 'default';
        }

        if (0 < $('.facetwp-counts').length) {
            FWP.extras.counts = true;
        }

        if (0 < $('.facetwp-selections').length) {
            FWP.extras.selections = true;
        }

        // Make sure there's a template
        var has_template = $('.facetwp-template').length > 0;

        if (! has_template) {
            var has_loop = FWP.helper.detect_loop(document.body);

            if (has_loop) {
                $(has_loop).addClass('facetwp-template');
            }
            else {
                return;
            }
        }

        var $div = $('.facetwp-template:first');
        FWP.template = $div.is('[data-name]') ? $div.attr('data-name') : 'wp';

        // Facets inside the template?
        if (0 < $div.find('.facetwp-facet').length) {
            console.error('Facets should not be inside the "facetwp-template" container');
        }

        wp.hooks.doAction('facetwp/ready');

        // Generate the user selections
        if (FWP.extras.selections) {
            wp.hooks.addAction('facetwp/loaded', function() {
                var selections = '';
                $.each(FWP.facets, function(key, val) {
                    if (val.length < 1 || ! isset(FWP.settings.labels[key])) {
                        return true; // skip this facet
                    }

                    var choices = val;
                    var facet_type = $('.facetwp-facet-' + key).attr('data-type');
                    choices = wp.hooks.applyFilters('facetwp/selections/' + facet_type, choices, {
                        'el': $('.facetwp-facet-' + key),
                        'selected_values': choices
                    });

                    if ('string' === typeof choices) {
                        choices = [{ value: '', label: choices }];
                    }
                    else if (! isset(choices[0].label)) {
                        choices = [{ value: '', label: choices[0] }];
                    }

                    var values = '';
                    $.each(choices, function(idx, choice) {
                        values += '<span class="facetwp-selection-value" data-value="' + choice.value + '">' + FWP.helper.escape_html(choice.label) + '</span>';
                    });

                    selections += '<li data-facet="' + key + '"><span class="facetwp-selection-label">' + FWP.settings.labels[key] + ':</span> ' + values + '</li>';
                });

                if ('' !== selections) {
                    selections = '<ul>' + selections + '</ul>';
                }

                $('.facetwp-selections').html(selections);
            });
        }

        // Click on a user selection
        $(document).on('click', '.facetwp-selections .facetwp-selection-value', function() {
            if (FWP.is_refresh) {
                return;
            }

            var facet_name = $(this).closest('li').attr('data-facet');
            var facet_value = $(this).attr('data-value');

            if ('' != facet_value) {
                FWP.reset(facet_name, facet_value);
            }
            else {
                FWP.reset(facet_name);
            }
        });

        // Pagination
        $(document).on('click', '.facetwp-page', function() {
            $('.facetwp-page').removeClass('active');
            $(this).addClass('active');

            FWP.paged = $(this).attr('data-page');
            FWP.soft_refresh = true;
            FWP.refresh();
        });

        // Per page
        $(document).on('change', '.facetwp-per-page-select', function() {
            FWP.extras.per_page = $(this).val();
            FWP.soft_refresh = true;
            FWP.autoload();
        });

        // Sorting
        $(document).on('change', '.facetwp-sort-select', function() {
            FWP.extras.sort = $(this).val();
            FWP.soft_refresh = true;
            FWP.autoload();
        });

        FWP.refresh();
    }


    $(function() {
        FWP.init();
    });
})(jQuery);
