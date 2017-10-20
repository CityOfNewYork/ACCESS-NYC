var FWP = FWP || {};

(function($) {

    var defaults = {
        'facets': {},
        'template': null,
        'settings': {},
        'is_reset': false,
        'is_refresh': false,
        'is_bfcache': false,
        'auto_refresh': true,
        'soft_refresh': false,
        'static_facet': null,
        'used_facets': {},
        'loaded': false,
        'jqXHR': false,
        'extras': {},
        'helper': {},
        'paged': 1
    };

    for (var prop in defaults) {
        if ('undefined' === typeof FWP[prop]) {
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


    FWP.helper.serialize = function(obj, prefix) {
        var str = [];
        var prefix = ('undefined' !== typeof prefix) ? prefix : '';
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
        if (! FWP.loaded && ! FWP.is_bfcache && 'undefined' !== typeof FWP_JSON.preload_data) {
            FWP.render(FWP_JSON.preload_data);
        }
        else {
            FWP.fetch_data();
        }

        // Cleanup
        FWP.paged = 1;
        FWP.static_facet = null;
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

            // Plugin hook
            wp.hooks.doAction('facetwp/refresh/' + facet_type, $this, facet_name);

            // Support custom loader
            var do_loader = true;
            if (FWP.loaded) {
                if (FWP.soft_refresh || facet_name === FWP.static_facet || 'undefined' !== typeof FWP.used_facets[facet_name]) {
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
            $.each(hash, function(idx, val) {
                var pieces = val.split('=');

                if ('paged' === pieces[0]) {
                    FWP.paged = pieces[1];
                }
                else if ('per_page' === pieces[0]) {
                    FWP.extras.per_page = pieces[1];
                }
                else if ('sort' === pieces[0]) {
                    FWP.extras.sort = pieces[1];
                }
                else if ('' !== pieces[1]) {
                    FWP.facets[pieces[0]] = decodeURIComponent(pieces[1]).split(',');
                }
            });
        }
    }


    FWP.fetch_data = function() {
        // Abort pending requests
        if (FWP.jqXHR && FWP.jqXHR.readyState !== 4) {
            FWP.jqXHR.abort();
        }

        var endpoint = ('wp' === FWP.template) ? document.URL : FWP_JSON.ajaxurl;

        // dataType is "text" for better JSON error handling
        FWP.jqXHR = $.ajax(endpoint, {
            type: 'POST',
            dataType: 'text',
            data: {
                action: 'facetwp_refresh',
                data: {
                    'facets': JSON.stringify(FWP.facets),
                    'static_facet': FWP.static_facet,
                    'used_facets': FWP.used_facets,
                    'http_params': FWP_HTTP,
                    'template': FWP.template,
                    'extras': FWP.extras,
                    'soft_refresh': FWP.soft_refresh ? 1 : 0,
                    'is_bfcache': FWP.is_bfcache ? 1 : 0,
                    'first_load': FWP.loaded ? 0 : 1,
                    'paged': FWP.paged
                }
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
        });
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
        if ('undefined' !== typeof response.counts) {
            $('.facetwp-counts').html(response.counts);
        }

        // Populate the pager
        if ('undefined' !== typeof response.pager) {
            $('.facetwp-pager').html(response.pager);
        }

        // Populate the "per page" box
        if ('undefined' !== typeof response.per_page) {
            $('.facetwp-per-page').html(response.per_page);
            if ('default' !== FWP.extras.per_page) {
                $('.facetwp-per-page-select').val(FWP.extras.per_page);
            }
        }

        // Populate the sort box
        if ('undefined' !== typeof response.sort) {
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


    FWP.reset = function(facet_name) {
        FWP.parse_facets();

        if ('undefined' !== typeof facet_name) {
            FWP.facets[facet_name] = [];

            if ('undefined' !== typeof FWP.used_facets) {
                delete FWP.used_facets[facet_name];
            }
        }
        else {
            $.each(FWP.facets, function(f) {
                FWP.facets[f] = [];
            });

            FWP.extras.sort = 'default';
            FWP.used_facets = {};
        }

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
                    if (val.length < 1 || 'undefined' === typeof FWP.settings.labels[key]) {
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
                    else if ('undefined' === typeof choices[0].label) {
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

            FWP.parse_facets();
            FWP.is_reset = true;

            if ('' != facet_value) {
                var arr = FWP.facets[facet_name];
                var arr_idx = arr.indexOf(facet_value);
                if (-1 < arr_idx) {
                    arr.splice(arr_idx, 1);
                    FWP.facets[facet_name] = arr;
                }
            }
            else {
                FWP.facets[facet_name] = [];
            }

            if ('undefined' !== typeof FWP.used_facets) {
                delete FWP.used_facets[facet_name]; // slider support
            }

            delete FWP.facets['paged']; // remove "paged" from URL
            FWP.refresh();
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
