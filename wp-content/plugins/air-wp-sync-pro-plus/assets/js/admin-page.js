function airWpSyncSettingsHandler() {
    const $ = jQuery;
    return {
        config: airWpSyncGetConfig(),
        bases: [],
        tables: [],
        current_object: undefined,
        views: function () {
            let views = [];
            const tableId = this.config.table;
            if (tableId) {
                const table = this.tables.find(function (table) {
                    return table.id == tableId;
                });
                if (table) {
                    views = table.views;
                }
            }
            return views;
        },
        fields: function () {
            let fields = [];
            const tableId = this.config.table;
            if (tableId) {
                const table = this.tables.find(function (table) {
                    return table.id == tableId;
                });
                if (table) {
                    fields = table.fields;
                }
            }
            return fields;
        },
        filters: function () {
            let filters = [];
            const tableId = this.config.table;
            if (tableId) {
                const table = this.tables.find(function (table) {
                    return table.id == tableId;
                });
                if (table) {
                    filters = table.filters;
                }
            }
            return filters;
        },
        originalConfigJson: JSON.stringify(airWpSyncGetConfig()),
        loadingBases: false,
        loadingTables: false,
        serverValidation: {},
        localValidation: {},
        inputElements: [],
        validationNotice: null,
        nonce: document.getElementById('airwpsync-ajax-nonce').value,
        mappingOptions: {},
        init() {
            const settingsHandler = this;

            this.loadAirtableBases();

            this.$nextTick(() => this.updateErrorMessages());

            this.validationNotice = document.getElementById('airwpsync-validation-notice');

            // Validate form when publish button clicked
            const alpineContainer = document.getElementById('airwpsync-alpine-container');
            document.getElementById('publish').addEventListener('click', function (e) {
                const event = new CustomEvent('validate', {
                    detail: {
                        originalEvent: e,
                    }
                });
                alpineContainer.dispatchEvent(event);
            });

            // Update mapping from React
            $(document).on('airwpsync/mapping-updated', function (e) {
                settingsHandler.config = {
                    ...settingsHandler.config,
                    mapping: e.detail
                };
            });

            // Update filters from React
            $(document).on('airwpsync/filters-updated', function (e) {
                settingsHandler.config = {
                    ...settingsHandler.config,
                    filters: e.detail
                };
            });
        },
        updateErrorMessages: function () {
            this.localValidation = {};
            this.inputElements = [...this.$el.querySelectorAll('[data-rules]')];
            this.inputElements.map((input) => {
                const name = input.dataset.name || input.name;
                let value = input.dataset.value;
                if (value === 'config.mapping') {
                    value = this.config.mapping;
                } else if (value) {
                    value = input.dataset.value.split('.').reduce((a, b) => a[b], this);
                } else {
                    value = input._x_model.get() || '';
                }
                const rules = input.dataset.rules;

                if (!this.localValidation[name]) {
                    this.localValidation[name] = {
                        errorMessages: [],
                        blurred: false,
                    }
                }
                this.localValidation[name].errorMessages = wp.hooks.applyFilters('airwpsync.getErrorMessages', [], value, rules, this);

            });
        },
        getErrorMessages: function (name, shouldBeBlurred = true) {
            return this.localValidation[name] && (this.localValidation[name].blurred || !shouldBeBlurred) ? this.localValidation[name].errorMessages : [];
        },
        hasErrors: function (name) {
            return this.getErrorMessages(name).length > 0;
        },
        change: function (event) {
            this.updateErrorMessages();
            if (!this.localValidation[event.target.name]) {
                return false;
            }
            if (event.type === "focusout") {
                this.localValidation[event.target.name].blurred = true;
            }
        },
        submit: function (event) {
            let isValid = true;
            this.updateErrorMessages();
            this.refreshFilterUI();
            this.refreshMetaboxMapping();
            this.inputElements.map((input) => {
                const name = input.dataset.name || input.name;
                this.localValidation[name].blurred = true;
            });
            for (const name in this.localValidation) {
                if (this.localValidation[name].errorMessages.length > 0) {
                    isValid = false;
                }
            }
            if (!isValid) {
                event.detail.originalEvent.preventDefault();
                this.validationNotice.style.display = 'block';
            }
        },
        getValidationCssClass(key) {
            let cssClass = '';
            if (this.serverValidation[key]) {
                if (this.serverValidation[key].valid === true) {
                    cssClass = 'dashicons-before dashicons-yes-alt';
                }
                if (this.serverValidation[key].valid === false) {
                    cssClass = 'dashicons-before dashicons-dismiss';
                }
            }
            return cssClass;
        },
        configHasChanged() {
            return JSON.stringify(this.config) !== this.originalConfigJson;
        },
        loadAirtableBases() {
            const settingsHandler = this;
            if (this.config.api_key) {
                this.loadingBases = true;
                this.loadingTables = true;
                const data = {
                    'action': 'air_wp_sync_get_airtable_bases',
                    'nonce': this.nonce,
                    'apiKey': this.config.api_key,
                };
                jQuery.post(window.ajaxurl, data, function (response) {
                    if (response.success) {
                        settingsHandler.bases = response.data.bases;

                        // Preselect first base and load tables
                        if (!settingsHandler.config.app_id && settingsHandler.bases.length > 0) {
                            settingsHandler.config.app_id = settingsHandler.bases[0].id;
                        }
                        // Mark api key as valid
                        settingsHandler.serverValidation.apiKey = {
                            valid: true,
                            message: '',
                        };
                        // Now load tables from base
                        settingsHandler.loadAirtableTables();
                    }
                    else {
                        // Mark api key as invalid
                        settingsHandler.serverValidation.apiKey = {
                            valid: false,
                            message: response.data.error,
                        };
                        // Empty bases and tables
                        settingsHandler.bases = [];
                        settingsHandler.tables = [];
                    }
                    settingsHandler.loadingBases = false;
                    settingsHandler.loadingTables = false;
                });
            }
        },
        loadAirtableTables() {
            const settingsHandler = this;
            if (this.config.api_key && this.config.app_id) {
                this.loadingTables = true;
                const data = {
                    'action': 'air_wp_sync_get_airtable_tables',
                    'nonce': this.nonce,
                    'apiKey': this.config.api_key,
                    'appId': this.config.app_id,
                    'options': JSON.stringify({
                        'enable_link_to_another_record': 'yes' === this.config.enable_link_to_another_record,
                    })
                };
                jQuery.post(window.ajaxurl, data, function (response) {
                    if (response.success) {
                        settingsHandler.tables = response.data.tables;
                    }
                    else {
                        settingsHandler.tables = [];
                    }
                    // check if config.table matches one of the tables
                    let currentTable = null;
                    if (settingsHandler.config.table && settingsHandler.tables.length > 0) {
                        currentTable = settingsHandler.tables.find(function (table) {
                            return table.id == settingsHandler.config.table;
                        });
                    }
                    // Preselect first table if no match
                    if (!currentTable && settingsHandler.tables.length > 0) {
                        settingsHandler.config.table = settingsHandler.tables[0].id;
                        settingsHandler.config.view = null;
                        settingsHandler.config.mapping = [];
                    }
                    else {
                        // Migrate from before meta API
                        settingsHandler.config.mapping.forEach(function (mapping, i) {
                            if (settingsHandler.fields().length > 0) {
                                settingsHandler.fields().forEach(function (f) {
                                    if (f.name === mapping.airtable) {
                                        settingsHandler.config.mapping[i].airtable = f.id;
                                    }
                                });
                            }
                        });

                    }
                    settingsHandler.loadingTables = false;
                    settingsHandler.checkFormulaFilter();
                    settingsHandler.updateWordPressOptions();
                });
            }
        },
        onTableChange() {
            this.config.view = null;
            this.config.mapping = [];
            this.checkFormulaFilter();

            this.updateWordPressOptions();
        },
        checkFormulaFilter() {
            this.serverValidation.formulaFilter = {};

            const settingsHandler = this;
            if (this.config.api_key && this.config.app_id && this.config.table && this.config.formula_filter) {
                const data = {
                    'action': 'air_wp_sync_check_formula_filter',
                    'nonce': this.nonce,
                    'apiKey': this.config.api_key,
                    'appId': this.config.app_id,
                    'table': this.config.table,
                    'view': this.config.view,
                    'formulaFilter': this.config.formula_filter,
                };
                jQuery.post(window.ajaxurl, data, function (response) {
                    if (response.success) {
                        // Mark formula filter as invalid
                        settingsHandler.serverValidation.formulaFilter = {
                            valid: true,
                        };
                    }
                    else {
                        // Mark formula filter as invalid
                        settingsHandler.serverValidation.formulaFilter = {
                            valid: false,
                            message: response.data.error,
                        };
                    }
                });
            }
        },
        refreshFilterUI() {
            const settingsHandler = this;
            if (!settingsHandler.config.table) {
                $('#airwpsync-filters').empty();
                return;
            }
            window.airWPSyncRenderFilters({
                id: 'airwpsync-filters',
                i18n: wp.i18n,
                airtableFilterOptions: [...this.filters()],
                fetchFn: (key, formData) => {
                    return new Promise((resolve) => {
                        if ('airtable-search-users' === key) {
                            const data = {
                                'action': 'air_wp_sync_get_airtable_table_users',
                                'nonce': this.nonce,
                                'apiKey': this.config.api_key,
                                'appId': this.config.app_id,
                                'table': this.config.table,
                                'userFieldName': formData.get('field_name'),
                            };
                            if (formData.get('search[]')) {
                                data['search[]'] = formData.get('search[]');
                            } else {
                                data['search'] = formData.get('search');
                            }
                            $.post(window.ajaxurl, data, resolve);
                        } else if ('airtable-search-records' === key) {
                            const data = {
                                'action': 'air_wp_sync_get_airtable_table_records',
                                'nonce': this.nonce,
                                'apiKey': this.config.api_key,
                                'appId': this.config.app_id,
                                'table': this.config.table,
                                'recordFieldName': formData.get('field_name'),
                            };
                            if (formData.get('search[]')) {
                                data['search[]'] = formData.get('search[]');
                            } else {
                                data['search'] = formData.get('search');
                            }
                            $.post(window.ajaxurl, data, resolve);
                        } else {
                            resolve({ success: false, error: 'unmanaged key ' + key });
                        }
                    });

                },
                initFiltersValue: { ...this.config.filters }
            })
        },
        updateWordPressOptions() {
            this.updateErrorMessages();
            this.refreshFilterUI();
            this.refreshMetaboxMapping();
        },
        refreshMetaboxMapping() {
            const settingsHandler = this;
            if (!settingsHandler.config.table) {
                $('#airwpsync-metabox-mapping').empty();
                return;
            }

            window.airWPSyncRenderMetaboxMapping({
                id: 'airwpsync-metabox-mapping',
                i18n: wp.i18n,
                mappingInit: [...settingsHandler.config.mapping],
                defaultMappingOptions: window.airWpSync[settingsHandler.config.module].mappingOptions,
                isOptionAvailable(value) {
                    return wp.hooks.applyFilters('airwpsync.isOptionAvailable', true, value, settingsHandler);
                },
                fields: settingsHandler.fields(),
                config: {
                    post_type: settingsHandler.config.post_type,
                    post_type_slug: settingsHandler.config.post_type_slug,
                },
                localValidation: settingsHandler.localValidation && settingsHandler.localValidation['mapping'] ? settingsHandler.localValidation['mapping'] : {
                    errorMessages: [],
                    blurred: false,
                }
            });
        },
        showNoticeHandler(noticeKey) {
            const settingsHandler = this;
            return function () {
                settingsHandler.config.notices[noticeKey] = true;
            }
        },
        hideNoticeHandler(noticeKey) {
            const settingsHandler = this;
            return function () {
                settingsHandler.config.notices[noticeKey] = false;
            }
        },
    }
}


function airWpSyncGetConfig() {
    const config = window.airWpSyncImporterData || {};
    if (!config.hasOwnProperty('mapping')) {
        config.mapping = [];
    }

    if (!config.hasOwnProperty('scheduled_sync')) {
        config.scheduled_sync = {
            type: 'manual',
            recurrence: '',
        };
    }

    for (let i = 0; i < config.mapping.length; i++) {
        if (!config.mapping[i].hasOwnProperty('options')) {
            config.mapping[i].options = {};
        }
    }

    if (!config.hasOwnProperty('notices')) {
        config.notices = {};
    }

    // Set default "enable_link_to_another_record" option value.
    if (!config.hasOwnProperty('enable_link_to_another_record')) {
        config.enable_link_to_another_record = 'no';
    } else if ('yes' === config.enable_link_to_another_record && !config.notices.hasOwnProperty('link-to-another-record-warning')) {
        // Show warning if it has not been shown yet.
        config.notices['link-to-another-record-warning'] = true;
    }

    if (!config.filters) {
        config.filters = {
            conjunction: 'and',
            filters: []
        };
    }
    if (!config.hasOwnProperty('use_filter_ui')) {
        // If there is a formula set, make this option default value to false.
        if (config.formula_filter) {
            config.use_filter_ui = false;
        } else {
            config.use_filter_ui = true;
        }
    }

    return config;
}

(function ($) {
    let $nonceField;
    let $importButton;
    let $cancelButton;
    let $feedback;
    let $infos;
    let originalConfigJson;
    let timeout;

    function init() {
        $nonceField = $('#airwpsync-trigger-update-nonce');
        $importButton = $('#airwpsync-import-button');
        $cancelButton = $('#airwpsync-cancel-button');
        $feedback = $('#airwpsync-import-feedback');
        $infos = $('#airwpsync-import-stats');

        originalConfigJson = JSON.stringify(airWpSyncGetConfig());

        $importButton.on('click', function () {
            const importerId = $(this).data('importer');
            triggerUpdate(importerId);
        });

        $cancelButton.on('click', function () {
            const importerId = $importButton.data('importer');
            cancelImport(importerId);
        });

        if ($importButton.hasClass('loading')) {
            $importButton.attr('disabled', 'disabled');
            const importerId = $importButton.data('importer');
            getProgress(importerId);
        }

        $(window).on('beforeunload', beforeUnload);

        $('#delete-action').on('click', function () {
            $(window).off('beforeunload', beforeUnload);
            return wp.hooks.applyFilters('airwpsync.deleteConnection', true, this);
        });

        $('#post').on('submit', function () {
            $(window).off('beforeunload', beforeUnload);
        });
    }

    function triggerUpdate(importerId) {
        clearTimeout(timeout);
        $importButton.addClass('loading').attr('disabled', 'disabled');
        $feedback.html(window.airWpSyncL10n.startingUpdate || 'In progress...').show();
        const data = {
            'action': 'air_wp_sync_trigger_update',
            'nonce': $nonceField.val(),
            'importer': importerId,
        };
        $.post(window.ajaxurl, data, function (response) {
            $feedback.html(response.data.feedback);
            if (response.success) {
                getProgress(importerId);
            }
            else {
                $importButton.removeClass('loading').removeAttr('disabled');
                $infos.html(response.data.infosHtml);
                timeout = setTimeout(function () {
                    $feedback.fadeOut();
                }, 6000);
            }
        }).fail(function () {
            $importButton.removeClass('loading').removeAttr('disabled');
        });
    }

    function cancelImport(importerId) {
        clearTimeout(timeout);
        $importButton.removeClass('loading').removeAttr('disabled');
        $cancelButton.addClass('loading').attr('disabled', 'disabled');
        $feedback.html(window.airWpSyncL10n.canceling || 'Canceling...').show();
        const data = {
            'action': 'air_wp_sync_cancel_import',
            'nonce': $nonceField.val(),
            'importer': importerId,
        };
        $.post(window.ajaxurl, data, function (response) {
            $feedback.html(response.data.feedback);
            $cancelButton.removeClass('loading').removeAttr('disabled').hide();
            $infos.html(response.data.infosHtml);
            timeout = setTimeout(function () {
                $feedback.fadeOut();
            }, 6000);
        }).fail(function () {
            $cancelButton.removeClass('loading').removeAttr('disabled');
        });
    }

    function getProgress(importerId) {
        if (!$importButton.hasClass('loading')) {
            return;
        }
        $cancelButton.show();
        const data = {
            'action': 'air_wp_sync_get_progress',
            'nonce': $nonceField.val(),
            'importer': importerId,
        };
        $.post(window.ajaxurl, data, function (response) {
            $feedback.html(response.data.feedback);
            if (response.data.infosHtml || !response.success) {
                $importButton.removeClass('loading').removeAttr('disabled');
                $cancelButton.removeClass('loading').removeAttr('disabled').hide();
                $infos.html(response.data.infosHtml);
                timeout = setTimeout(function () {
                    $feedback.fadeOut();
                }, 6000);
            }
            else {
                setTimeout(function () {
                    getProgress(importerId);
                }, 3000);
            }

        }).fail(function () {
            $importButton.removeClass('loading').removeAttr('disabled');
        });
    }

    function beforeUnload() {
        if (originalConfigJson !== $('[name="content"]').val()) {
            return window.airWpSyncL10n.unsavedChanges || "You have unsaved changes.";
        }
    }

    $(init);
})(jQuery);

(function ($) {
    function init() {
        $(document).tooltip({
            items: '.airwpsync-tooltip',
            tooltipClass: 'arrow-bottom',
            content: function () {
                return $(this).attr('aria-label');
            },
            position: {
                my: 'center bottom',
                at: 'center-3 top-11',
            },
            open: function (event, ui) {
                if (typeof (event.originalEvent) === 'undefined') {
                    return false;
                }

                const $id = ui.tooltip.attr('id');
                $('div.ui-tooltip').not('#' + $id).remove();
            },
            close: function (event, ui) {
                ui.tooltip.hover(function () {
                    $(this).stop(true).fadeTo(400, 1);
                },
                    function () {
                        $(this).fadeOut('500', function () {
                            $(this).remove();
                        });
                    });
            }
        });
    }

    $(init);
})(jQuery);


/**
 * Validation: required field rule
 */
wp.hooks.addFilter('airwpsync.getErrorMessages', 'wpconnect/airwpsync/errors/required', function (messages, value, rules) {
    if (rules.indexOf('required') > -1 && value.length === 0) {
        messages.push(window.airWpSyncL10n.requiredField || 'This field is required');
    }
    return messages;
});

/**
 * Validation: mappingRequired field rule
 */
wp.hooks.addFilter('airwpsync.getErrorMessages', 'wpconnect/airwpsync/errors/mappingRequired', function (messages, value, rules) {
    if (rules.indexOf('mappingRequired') > -1 && value.length > 0) {
        const oneFieldIsEmpty = value.reduce(function (result, mapping) {
            if ('' === mapping.wordpress) {
                result = true;
            }
            return result;
        }, false);
        if (oneFieldIsEmpty) {
            messages.push(window.airWpSyncL10n.emptyField || 'Please select an option in the "Import As" column for all mappings');
        }

        const oneCustomFieldEmpty = value.reduce(function (result, mapping) {
            if (mapping.wordpress && mapping.wordpress.split('::')[1] === 'custom_field' && mapping.options && (!mapping.options.name || mapping.options.name === '')) {
                result = true;
            }
            return result;
        }, false);
        if (oneCustomFieldEmpty) {
            messages.push(window.airWpSyncL10n.emptyCustomField || '"Custom Field" fields can\'t be empty.');
        }
    }
    return messages;
});

/**
 * Validation: Selected base is missing
 */
wp.hooks.addFilter('airwpsync.getErrorMessages', 'wpconnect/airwpsync/errors/selectedBaseIsMissing', function (messages, value, rules, airWpSync) {
    if (rules.indexOf('selectedBaseIsMissing') > -1 && airWpSync.config.app_id && !airWpSync.loadingBases && !airWpSync.bases.find(base => base.id === airWpSync.config.app_id)) {
        messages.push(window.airWpSyncL10n.databaseNotAvailable || 'The base you have previously selected is not available anymore, please check your Airtable token access');
    }
    return messages;
});