var FWP = {};

(function($) {
    $(function() {

        var row_count = 0;

        // Load
        $.post(ajaxurl, {
            action: 'facetwp_load'
        }, function(response) {
            $.each(response.facets, function(idx, obj) {
                var $row = $('.clone-facet .facetwp-row').clone();
                $row.attr('data-id', row_count);
                $row.attr('data-type', obj.type);
                $row.find('.facet-fields').html(FWP_Clone[obj.type]);
                $row.find('.facet-label').val(obj.label);
                $row.find('.facet-name').text(obj.name);
                $row.find('.facet-type').val(obj.type);

                // Facet load hook
                wp.hooks.doAction('facetwp/load/' + obj.type, $row, obj);

                // UI for code-based facets
                if ('undefined' !== typeof obj['_code']) {
                    $row.addClass('in-code');
                }

                $('.facetwp-region-facets .facetwp-content').append($row);
                $('.facetwp-region-facets .facetwp-cards').append(FWP.build_card({
                    card: 'facet',
                    id: row_count,
                    label: obj.label,
                    name: obj.name,
                    type: obj.type
                }));
                row_count++;
            });

            $.each(response.templates, function(idx, obj) {
                var $row = $('.clone-template .facetwp-row').clone();
                $row.attr('data-id', row_count);
                $row.find('.template-label').val(obj.label);
                $row.find('.template-name').text(obj.name);
                $row.find('.template-query').val(obj.query);
                $row.find('.template-template').val(obj.template);

                // UI for code-based templates
                if ('undefined' !== typeof obj['_code']) {
                    $row.addClass('in-code');
                }

                $('.facetwp-region-templates .facetwp-content').append($row);
                $('.facetwp-region-templates .facetwp-cards').append(FWP.build_card({
                    card: 'template',
                    id: row_count,
                    label: obj.label,
                    name: obj.name
                }));
                row_count++;
            });

            $.each(response.settings, function(key, val) {
                var $this = $('.facetwp-setting[data-name=' + key + ']');
                $this.val(val);
            });

            // Initialize the Query Builder
            $('.qb-area').queryBuilder({
                post_types: FWP.builder.post_types,
                taxonomies: FWP.builder.taxonomies,
                refresh: function(el) {
                    var json = JSON.stringify(el.data('query_args'), null, 2);
                    json = "<?php\nreturn " + json + ';';
                    json = json.replace(/[\{\(\[]/g, 'array(');
                    json = json.replace(/[\}\]]/g, ')');
                    json = json.replace(/:/g, ' =>');
                    $('.qb-results').val(json);
                }
            });

            // Initialize fSelect
            $('.qb-post-type').fSelect({
                placeholder: FWP.i18n['All post types']
            });

            // Hide the preloader
            $('.facetwp-loading').hide();
            $('.facetwp-header-nav a:first').click();
        }, 'json');


        FWP.build_card = function(params) {
            var output = '<li data-id="' + params.id + '">';
            output += '<div class="facetwp-card">';
            output += '<div class="card-delete"></div>';
            output += '<div class="card-label">' + params.label + '</div>';
            if ('facet' == params.card) {
                output += '<div class="card-type">' + params.type + '</div>';
            }
            output += '<div class="card-shortcode">[facetwp ' + params.card + '="<span class="card-name">' + params.name + '</span>"]</div>';
            output += '</div>';
            output += '</li>';
            return output;
        }


        // Is the indexer running?
        FWP.get_progress = function() {
            $.post(ajaxurl, {
                'action': 'facetwp_heartbeat'
            }, function(response) {

                // Remove extra spaces added by some themes
                var response = response.trim();

                if ('-1' == response) {
                    $('.facetwp-response').html(FWP.i18n['Indexing complete']);
                }
                else if ($.isNumeric(response)) {
                    $('.facetwp-response').html(FWP.i18n['Indexing'] + '... ' + response + '%');
                    $('.facetwp-response').show();
                    setTimeout(function() {
                        FWP.get_progress();
                    }, 5000);
                }
                else {
                    $('.facetwp-response').html(response);
                }
            });
        }
        FWP.get_progress();


        // Topnav
        $(document).on('click', '.facetwp-tab', function() {
            var tab = $(this).attr('rel');
            $('.facetwp-tab').removeClass('active');
            $(this).addClass('active');
            $('.facetwp-region').removeClass('active');
            $('.facetwp-region-' + tab).addClass('active');
        });


        // Conditionals based on facet type
        $(document).on('change', '.facet-type', function() {
            var val = $(this).val();
            var $facet = $(this).closest('.facetwp-row');
            $facet.find('.facetwp-show').show();

            if (val != $facet.attr('data-type')) {
                $facet.find('.facet-fields').html(FWP_Clone[val]);
                $facet.attr('data-type', val);
            }

            wp.hooks.doAction('facetwp/change/' + val, $(this));

            // Update the card
            var id = $facet.attr('data-id');
            $('.facetwp-cards li[data-id="'+ id +'"] .card-type').text(val);

            // Trigger .facet-source
            $facet.find('.facet-source').trigger('change');
        });


        // Conditionals based on facet source
        $(document).on('change', '.facet-source', function() {
            var $facet = $(this).closest('.facetwp-row');
            var facet_type = $facet.find('.facet-type').val();
            var display = (-1 < $(this).val().indexOf('tax/')) ? 'table-row' : 'none';

            if ('checkboxes' == facet_type) {
                $facet.find('.facet-parent-term').closest('tr').css({ 'display' : display });
                $facet.find('.facet-hierarchical').closest('tr').css({ 'display' : display });
            }
            else if ('dropdown' == facet_type) {
                $facet.find('.facet-parent-term').closest('tr').css({ 'display' : display });
                $facet.find('.facet-hierarchical').closest('tr').css({ 'display' : display });
            }
        });


        // Add item
        $(document).on('click', '.facetwp-add', function() {
            var $parent = $(this).closest('.facetwp-region');
            var type = $parent.hasClass('facetwp-region-facets') ? 'facet' : 'template';
            var label = ('facet' == type) ? 'New facet' : 'New template';
            var name = ('facet' == type) ? 'new_facet' : 'new_template';

            var $row = $('.clone-' + type + ' .facetwp-row').clone();
            $row.attr('data-id', row_count);

            $parent.find('.facetwp-content').append($row);
            $parent.find('.facetwp-cards').append(FWP.build_card({
                card: type,
                id: row_count,
                label: label,
                name: name,
                type: 'checkboxes'
            }));

            // Simulate a click
            $parent.find('.facetwp-cards li:last .facetwp-card').trigger('click');

            row_count++;
        });


        // Remove item
        $(document).on('click', '.card-delete', function(e) {
            if (confirm(FWP.i18n['Are you sure?'])) {
                var id = $(this).closest('li').attr('data-id');
                $(this).closest('.facetwp-region').find('.facetwp-content .facetwp-row[data-id="' + id + '"]').remove();
                $(this).closest('li').remove();
            }
            e.stopPropagation();
        });


        // Edit item
        $(document).on('click', '.facetwp-card', function(e) {
            if ('' != window.getSelection().toString()) {
                return;
            }

            var id = $(this).closest('li').attr('data-id');
            var $parent = $(this).closest('.facetwp-region');
            var type = $parent.hasClass('facetwp-region-facets') ? 'facets' : 'templates';

            $parent.find('.facetwp-cards').hide();
            $parent.find('.facetwp-content').show();
            $parent.find('.facetwp-back').closest('.btn-wrap').show();
            $parent.find('.facetwp-add').closest('.btn-wrap').hide();
            $parent.find('.facetwp-row').hide();
            $parent.find('.facetwp-row[data-id="' + id + '"]').show();

            // Trigger conditional settings
            if ('facets' == type) {
                $parent.find('.facetwp-row[data-id=' + id + '] .facet-type').trigger('change');
            }

            // Set the active row
            FWP.active_row = id;
        });


        // Back button
        $(document).on('click', '.facetwp-back', function() {
            $(this).closest('.facetwp-region').find('.facetwp-cards').show();
            $(this).closest('.facetwp-region').find('.facetwp-content').hide();
            $(this).closest('.facetwp-region').find('.facetwp-add').closest('.btn-wrap').show();
            $(this).closest('.btn-wrap').hide();
        });


        // Change the sidebar link label
        $(document).on('keyup', '.facet-label, .template-label', function() {
            var label = $(this).val();
            var type = $(this).hasClass('facet-label') ? 'facet' : 'template';
            var $row = $(this).closest('.facetwp-row');
            var id = $row.attr('data-id');

            var val = $.trim(label).toLowerCase();
            val = val.replace(/[^\w- ]/g, ''); // strip invalid characters
            val = val.replace(/[- ]/g, '_'); // replace space and hyphen with underscore
            val = val.replace(/[_]{2,}/g, '_'); // strip consecutive underscores

            // Update the input field
            $(this).siblings('.' + type + '-name').text(val);

            // Update the card
            $('.facetwp-cards li[data-id="'+ id +'"] .card-label').text(label);
            $('.facetwp-cards li[data-id="'+ id +'"] .card-name').text(val);
        });


        // Open modal window
        $(document).on('click', '.open-builder', function() {
            $('.media-modal').show();
            $('.media-modal-backdrop').show();
        });


        // Send Query Builder arguments to the active editor
        $(document).on('click', '.qb-send', function() {
            var args = $('.modal-content-wrap').find('.qb-results').val();
            $('.facetwp-row[data-id="' + FWP.active_row + '"] .template-query').val(args);
            $('.media-modal-close').trigger('click');
        });


        // Close modal window
        $(document).on('click', '.media-modal-close', function() {
            $('.media-modal').hide();
            $('.media-modal-backdrop').hide();
        });


        // Code unlock
        $(document).on('click', '.dashicons-unlock', function() {
            $(this).closest('.facetwp-row').removeClass('in-code');
        });


        // Save
        $(document).on('click', '.facetwp-save', function() {
            $('.facetwp-response').html(FWP.i18n['Saving'] + '...');
            $('.facetwp-response').show();

            var data = {
                'facets': [],
                'templates': [],
                'settings': {}
            };

            $('.facetwp-region-facets .facetwp-row:not(.in-code)').each(function() {
                var $this = $(this);
                var type = $this.find('.facet-type').val();

                var obj = {
                    'label': $this.find('.facet-label').val(),
                    'name': $this.find('.facet-name').text(),
                    'type': $this.find('.facet-type').val()
                };

                // Facet save hook
                obj = wp.hooks.applyFilters('facetwp/save/' + obj.type, $this, obj);
                data.facets.push(obj);
            });

            $('.facetwp-region-templates .facetwp-row:not(.in-code)').each(function() {
                var $this = $(this);
                data.templates.push({
                    'label': $this.find('.template-label').val(),
                    'name': $this.find('.template-name').text(),
                    'query': $this.find('.template-query').val(),
                    'template': $this.find('.template-template').val()
                });
            });

            $('.facetwp-region-settings .facetwp-setting').each(function() {
                var name = $(this).attr('data-name');
                data.settings[name] = $(this).val();
            });

            $.post(ajaxurl, {
                'action': 'facetwp_save',
                'data': JSON.stringify(data)
            }, function(response) {
                $('.facetwp-response').html(response);
            });
        });


        // Export
        $(document).on('click', '.export-submit', function() {
                $('.export-code').show();
                $('.export-code').val('');
                $.post(ajaxurl, {
                    action: 'facetwp_migrate',
                    action_type: 'export',
                    items: $('.export-items').val()
                },
                function(response) {
                    $('.export-code').val(response);
                });
        });


        // Import
        $(document).on('click', '.import-submit', function() {
            $('.facetwp-response').show();
            $('.facetwp-response').html(FWP.i18n['Importing'] + '...');
            $.post(ajaxurl, {
                action: 'facetwp_migrate',
                action_type: 'import',
                import_code: $('.import-code').val(),
                overwrite: $('.import-overwrite').is(':checked') ? 1 : 0
            },
            function(response) {
                $('.facetwp-response').html(response);
            });
        });


        // Rebuild index
        $(document).on('click', '.facetwp-rebuild', function() {
            $.post(ajaxurl, { action: 'facetwp_rebuild_index' });
            $('.facetwp-response').html(FWP.i18n['Indexing'] + '...');
            $('.facetwp-response').show();
            setTimeout(function() {
                FWP.get_progress();
            }, 5000);
        });


        // Activation
        $(document).on('click', '.facetwp-activate', function() {
            $('.facetwp-activation-status').html(FWP.i18n['Activating'] + '...');
            $.post(ajaxurl, {
                action: 'facetwp_license',
                license: $('.facetwp-license').val()
            }, function(response) {
                $('.facetwp-activation-status').html(response.message);
            }, 'json');
        });


        // Tooltips
        $(document).on('mouseover', '.facetwp-tooltip', function() {
            if ('undefined' == typeof $(this).data('powertip')) {
                var content = $(this).find('.facetwp-tooltip-content').html();
                $(this).data('powertip', content);
                $(this).powerTip({
                    placement: 'e',
                    mouseOnToPopup: true
                });
                $.powerTip.show(this);
            }
        });
    });
})(jQuery);