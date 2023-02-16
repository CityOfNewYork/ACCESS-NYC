var otgs_wp_installer_recommended_plugin = {

    init: function () {
        jQuery('.js-install-recommended').click(otgs_wp_installer_recommended_plugin.install_and_activate);
    },

    install_and_activate: function () {
        var pluginElement = jQuery(this).parent();
        var activate = 1;
        var data = {
            action: 'installer_download_plugin',
            data: jQuery(this).val(),
            activate: activate
        };

        var spinner = pluginElement.find('.spinner');

        spinner.css('visibility', 'visible');
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: data,
            success: function (ret) {
                    jQuery.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'installer_activate_plugin',
                            plugin_id: ret.plugin_id,
                            nonce: ret.nonce
                        },
                        success: function (ret) {
                            jQuery.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    action: 'installer_recommendation_success',
                                    pluginData: pluginElement.find('#originalPluginData').val(),
                                    nonce: pluginElement.find('#recommendation_success_nonce').val()
                                },
                                success:
                                    function () {
                                        location.reload();
                                    }
                            });
                        }
                    });
            }
        });

        return false;
    }
};

jQuery(document).ready(otgs_wp_installer_recommended_plugin.init);
