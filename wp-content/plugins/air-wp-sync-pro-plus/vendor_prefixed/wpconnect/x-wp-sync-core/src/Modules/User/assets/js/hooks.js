/**
 * Validation: required email field mapping
 */
wp.hooks.addFilter('airwpsync.getErrorMessages', 'wpconnect/airwpsync/errors/requiredUserEmail', function(messages, value, rules, airWpSync) {
    if (rules.indexOf('requiredUserEmail') > -1 && airWpSync.config.module === 'user' && Array.isArray(value)) {
        var hasEmail = value.reduce(function(result, row) {
            return row.wordpress === 'user::user_email' ? true : result;
        }, false);

        if (!hasEmail) {
            messages.push(window.airWpSyncL10n.requiredUserEmailErrorMessage || 'It is mandatory to map the user e-mail address.');
        }
    }
    return messages;
});

/**
 * Validation: user login field mapping
 */
wp.hooks.addFilter('airwpsync.getErrorMessages', 'wpconnect/airwpsync/errors/requiredUserLogin', function(messages, value, rules, airWpSync) {
    if (rules.indexOf('requiredUserEmail') > -1 && airWpSync.config.module === 'user' && Array.isArray(value)) {
        var hasUserLogin = value.reduce(function(result, row) {
            return row.wordpress === 'user::user_login' ? true : result;
        }, false);

        if (!hasUserLogin) {
            messages.push(window.airWpSyncL10n.requiredUserLoginErrorMessage || 'It is mandatory to map the Username field.');
        }
    }
    return messages;
});
