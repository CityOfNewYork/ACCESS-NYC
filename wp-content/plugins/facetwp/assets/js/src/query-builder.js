(function($) {

    $.fn.queryBuilder = function(options) {

        var settings = $.extend({
            post_types: {'post': 'Posts', 'page': 'Pages'},
            taxonomies: {'category': 'Category', 'post_tag': 'Tag'},
            refresh: function(el) {}
        }, options);

        function buildOptions() {
            var output = {};

            var html = '';
            html += '<select class="qb-post-type" multiple="multiple">';
            $.each(settings.post_types, function(name, label) {
                html += '<option value="' + name + '">' + label + '</option>';
            });
            html += '</select>';
            output.post_types = html;

            html = '';
            html += '<select class="qb-taxonomy">';
            $.each(settings.taxonomies, function(name, label) {
                html += '<option value="' + name + '">' + label + '</option>';
            });
            html += '</select>';
            output.taxonomies = html;

            html = '';
            html += '<select class="qb-orderby">';
            html += '<option value="date">Post Date</option>';
            html += '<option value="title">Title</option>';
            html += '<option value="name">Slug</option>';
            html += '<option value="ID">ID</option>';
            html += '<option value="modified">Last Modified</option>';
            html += '</select>';
            output.orderby = html;

            html = '';
            html += '<select class="qb-order">';
            html += '<option value="DESC">descending</option>';
            html += '<option value="ASC">ascending</option>';
            html += '</select>';
            output.order = html;

            html = '';
            html += '<select class="qb-cf-compare">';
            html += '<option value="=">=</option>';
            html += '<option value=">">&gt;</option>';
            html += '<option value="<">&lt;</option>';
            html += '<option value=">=">&gt;=</option>';
            html += '<option value="<=">&lt;=</option>';
            html += '<option value="IN">IN</option>';
            html += '<option value="NOT IN">NOT IN</option>';
            html += '<option value="EXISTS">EXISTS</option>';
            html += '<option value="NOT EXISTS">NOT EXISTS</option>';
            html += '</select>';
            output.cf_compare = html;

            return output;
        }

        function buildInterface($this) {
            var opts = buildOptions();

            var output = '';
            output += '<div class="qb-wrap">';
            output += '  <div class="qb-row">Fetch ' + opts.post_types + '</div>';
            output += '  <div class="qb-row">Sort by ' + opts.orderby + ' in ' + opts.order + ' order</div>';
            output += '  <div class="qb-row">Show <input type="text" class="qb-posts-per-page" value="10" /> posts per page</div>';
            output += '  <div class="qb-row qb-tax-items"></div>';
            output += '  <div class="qb-row qb-cf-items"></div>';
            output += '  <div class="qb-buttons">';
            output += '    <button class="button qb-tax-btn">Add term criteria</button>';
            output += '    <button class="button qb-cf-btn">Add custom field criteria</button>';
            output += '  </div>';
            output += '  <div class="hidden qb-tax-clone">';
            output += '    <div>' + opts.taxonomies + ' term slug is <input type="text" class="qb-tax-slug" title="Comma-separated list of term slugs" value="" placeholder="uncategorized" /> <span class="qb-remove"></span></div>';
            output += '  </div>';
            output += '  <div class="hidden qb-cf-clone">';
            output += '    <div><input type="text" class="qb-cf-key" value="" placeholder="field_name" /> ' + opts.cf_compare + ' <input type="text" class="qb-cf-value" title="Comma-separated list of values" value="" placeholder="value" /> <span class="qb-remove"></span></div>';
            output += '  </div>';
            output += '</div>';

            $this.html(output);
        }

        function rebuildJson($this) {
            var post_type = $this.find('.qb-post-type').val();
            post_type = (null === post_type) ? 'any' : post_type;
            if ('object' == typeof post_type && 1 == post_type.length) {
                post_type = post_type[0];
            }

            var json = {
                post_type: post_type,
                post_status: 'publish',
                orderby: $this.find('.qb-orderby').val(),
                order: $this.find('.qb-order').val(),
                posts_per_page: parseInt($this.find('.qb-posts-per-page').val())
            };

            // Taxonomy handler
            var tax_query = [];
            $this.find('.qb-tax-slug').each(function(i, el) {
                var slug = $(el).val().replace(/ /g, '');
                if ('' != slug) {
                    slug = (-1 < slug.indexOf(',')) ? slug.split(',') : slug;

                    tax_query.push({
                        'taxonomy': $(el).closest('div').find('.qb-taxonomy').val(),
                        'field': 'slug',
                        'terms': slug
                    });
                }
            });

            if (0 < tax_query.length) {
                json.tax_query = tax_query;
            }

            // Custom field handler
            var meta_query = [];
            $this.find('.qb-cf-key').each(function(i, el) {
                var key = $(el).val();

                if ('' != key) {
                    var compare = $(el).closest('div').find('.qb-cf-compare').val();
                    var val = $(el).closest('div').find('.qb-cf-value').val();

                    // Array whitespace cleanup
                    if (-1 < val.indexOf(',')) {
                        val = val.replace(/[ ]*,[ ]*/g, ',').split(',');
                    }

                    var meta_row = {'key': key};

                    // Add the "value" key if the compare isn't "EXISTS" or "NOT EXISTS"
                    if (-1 === compare.indexOf('EXISTS')) {
                        meta_row['value'] = val;
                    }

                    // The compare isn't needed when "="
                    if ('=' != compare) {
                        meta_row['compare'] = compare;
                    }

                    meta_query.push(meta_row);
                }
            });

            if (0 < meta_query.length) {
                json.meta_query = meta_query;
            }

            // Save to DOM element
            $this.data('query_args', json);
            settings.refresh($this);
        }

        return this.each(function() {
            var $this = $(this);
            buildInterface($this);
            rebuildJson($this);

            $this.on('change', 'select', function() {
                rebuildJson($this);
            });

            $this.on('keyup', 'input', function() {
                rebuildJson($this);
            });

            $this.on('click', '.qb-tax-btn', function() {
                var $parent = $(this).closest('.qb-wrap');
                var html = $parent.find('.qb-tax-clone').html();
                $parent.find('.qb-tax-items').append(html);
            });

            $this.on('click', '.qb-cf-btn', function() {
                var $parent = $(this).closest('.qb-wrap');
                var html = $parent.find('.qb-cf-clone').html();
                $parent.find('.qb-cf-items').append(html);
            });

            $this.on('change', '.qb-cf-compare', function() {
                var val = $(this).val();
                var $val_el = $(this).closest('div').find('.qb-cf-value');
                (-1 < val.indexOf('EXISTS')) ? $val_el.hide() : $val_el.show();
            });

            $this.on('click', '.qb-remove', function() {
                $(this).closest('div').remove();
                rebuildJson($this);
            });
        });
    };

})(jQuery);