/**
 *
 * @param {MappingGroupOptions} defaultMappingOptions Default mapping options.
 * @return WordPressField[]
 */
function defaultMappingOptionsToWordPressField(defaultMappingOptions) {
    return Object.keys(defaultMappingOptions).reduce(function (wordPressFields, mappingOptionKey) {
        const mappingOption = defaultMappingOptions[mappingOptionKey];
        return (wordPressFields.concat(mappingOption.options));
    }, []);
}
function getAirtableFieldById(airtableId, fields) {
    return fields.find(function (field) {
        return field.id === airtableId;
    });
}
function getWordpressFieldById(wordPressId, fields) {
    return fields.find(function (field) {
        return field.value === wordPressId;
    });
}
/**
 * Filter out invalid mapping fields.
 *
 * @param Mapping[] mapping Fields mapping.
 * @param AirtableField[] fields Airtable fields.
 * @returns Mapping[]
 */
function checkValidAirtableFields(mapping, fields) {
    return mapping.filter((field) => {
        return !!getAirtableFieldById(field.airtable, fields);
    });
}
/**
 * Filter out invalid mapping fields.
 *
 * @param Mapping[] mapping Fields mapping.
 * @param WordPressField[] fields WordPress fields.
 * @returns Mapping[]
 */
function checkValidWordPressFields(mapping, fields) {
    return mapping.filter((field) => {
        return field.wordpress === '' || !!getWordpressFieldById(field.wordpress, fields);
    });
}

export { checkValidAirtableFields, checkValidWordPressFields, defaultMappingOptionsToWordPressField, getAirtableFieldById, getWordpressFieldById };
//# sourceMappingURL=helpers.ts.js.map
