/**
 * Validation: required name field mapping
 */
wp.hooks.addFilter('airwpsync.getErrorMessages', 'wpconnect/airwpsync/errors/requiredTermName', function (messages, value, rules, airWpSync) {
    if (rules.indexOf('requiredTermName') > -1 && airWpSync.config.module === 'term' && Array.isArray(value)) {
        var hasName = value.reduce(function (result, row) {
            return row.wordpress === 'term::name' ? true : result;
        }, false);

        if (!hasName) {
            messages.push(window.airWpSyncL10n.requiredTermNameErrorMessage || 'It is mandatory to map the term name.');
        }
    }
    return messages;
});
