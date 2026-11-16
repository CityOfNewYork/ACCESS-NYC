/**
 * Filter available options depending on post type features
 */
wp.hooks.addFilter('airwpsync.isOptionAvailable', 'wpconnect/airwpsync/isOptionAvailable', function(available, value, airWpSync) {
    if (airWpSync.config.module === 'post') {
        var featuresByPostType = window.airWpSync.post.extraConfig.featuresByPostType || {};

        var postType = airWpSync.config.post_type || '';
        if (postType === 'custom') {
            var newPostType = airWpSync.config.post_type_slug || '';
            if (newPostType && featuresByPostType.hasOwnProperty(newPostType)) {
                postType = newPostType;
            }
        }

        var group = value.substring(0, value.indexOf('::'));
        var feature = value.substring(value.indexOf('::') + 2);

        // Check if feature is available for post type
        if (featuresByPostType.hasOwnProperty(postType) && featuresByPostType[postType].hasOwnProperty(group) && Array.isArray(featuresByPostType[postType][group])) {
            available = featuresByPostType[postType][group].indexOf(feature) > -1;
        }
    }
    return available;
});

/**
 * Alert for declared CPT on delete
 */
wp.hooks.addFilter( 'airwpsync.deleteConnection', 'wpconnect/airwpsync/deleteConnection', function(returnValue, airWpSync) {
    var postType = airWpSyncGetConfig().post_type || '';
    if (postType === 'custom') {
        if (confirm(window.airWpSyncL10n.deleteActionConfirmation || 'You have a Custom Post Type declared using this connection. Are you sure to delete it?')) {
            returnValue = true;
        }
        else {
            returnValue = false;
        }
    }
    return returnValue;
});

/**
 * Validation: slug field
 */
wp.hooks.addFilter('airwpsync.getErrorMessages', 'wpconnect/airwpsync/errors/slug', function(messages, value, rules) {
    if (rules.indexOf('slug') > -1 && value.length > 0 && !value.match(/^[a-z0-9-_]+$/)) {
        messages.push(window.airWpSyncL10n.slugErrorMessage || 'Only lowercase alphanumeric characters, dashes, and underscores are allowed.');
    }
    return messages;
});

/**
 * Validation: allowed CPT slug
 */
var originalPostTypeSlug = airWpSyncGetConfig().post_type_slug || '';
wp.hooks.addFilter('airwpsync.getErrorMessages', 'wpconnect/airwpsync/errors/allowedCptSlug', function(messages, value, rules) {
    var reservedSlugs = window.airWpSync.post.extraConfig.reservedCptSlugs || Array();
    if (rules.indexOf('allowedCptSlug') > -1 && value !== originalPostTypeSlug && reservedSlugs.indexOf(value) > -1) {
        messages.push(window.airWpSyncL10n.allowedCptSlugErrorMessage || 'This slug is already in use, please choose another.');
    }
    return messages;
});


/**
 * Validation: post type slug length
 */
wp.hooks.addFilter('airwpsync.getErrorMessages', 'wpconnect/airwpsync/errors/slugLength', function (messages, value, rules) {
	if (rules.indexOf('slugLength') > -1 && value.length > 20) {
		messages.push(window.airWpSyncL10n.slugLengthErrorMessage || 'This slug is exceeding 20 characters. Please use a sorter one.');
	}
	return messages;
});
