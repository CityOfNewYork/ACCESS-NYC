import { jsxs, jsx } from 'react/jsx-runtime';
import { v4 } from 'uuid';

function MappingRow({ texts, index, airtableFieldId, wordPressFieldId, fieldOptions, mappingManager, ...props }) {
    const airtableFieldDisabled = Object.keys(mappingManager.airtableSelectFieldGroupsOptions).length === 0; // || loadingDatabasesAndPages;
    let wordPressFieldConfig = {};
    if (wordPressFieldId) {
        wordPressFieldConfig = mappingManager.getWordPressFieldById(wordPressFieldId) ?? {};
    }
    const airtableFieldChangedHandler = (e) => {
        const target = e?.target;
        mappingManager.updateAirtableField(index, target.value);
    };
    const wordPressFieldChangedHandler = (e) => {
        const target = e?.target;
        mappingManager.updateWordPressField(index, target.value);
    };
    const customFieldOptionChangedHandler = (e) => {
        const target = e?.target;
        mappingManager.updateFieldOption(index, 'name', target.value);
    };
    const removeMappingRowHandler = () => {
        mappingManager.removeMappingRow(index);
    };
    const renderCustomFieldOptions = () => {
        return jsxs("div", { className: "airwpsync-field form-required", children: [jsxs("label", { htmlFor: "customfield_" + airtableFieldId, children: [jsx("span", { children: texts.custom_field }), jsx("span", { className: "airwpsync-required", "aria-hidden": "true", children: " *" }), jsx("span", { className: "screen-reader-text", children: texts.required_field_indicator })] }), jsx("input", { id: "customfield_" + airtableFieldId, value: fieldOptions.name ?? '', type: "text", name: "customfield[" + airtableFieldId + "]", className: "airwpsync-input regular-text ltr", onChange: customFieldOptionChangedHandler })] });
    };
    return jsxs("tr", { ...props, children: [jsx("td", { children: jsxs("div", { className: "airwpsync-field form-required", children: [jsxs("label", { children: [jsx("span", { children: texts.airtable_field }), jsx("span", { className: "airwpsync-required", "aria-hidden": "true", children: "*" }), jsx("span", { className: "screen-reader-text", children: texts.required_field_indicator })] }), jsx("select", { name: "airtable[]", className: "airwpsync-input regular-text ltr", value: airtableFieldId, disabled: airtableFieldDisabled, onChange: airtableFieldChangedHandler, children: Object.keys(mappingManager.airtableSelectFieldGroupsOptions).map((groupKey) => {
                                const group = mappingManager.airtableSelectFieldGroupsOptions[groupKey];
                                const groupLabel = group.label ?? texts.fields;
                                return jsx("optgroup", { label: groupLabel, children: group.options.map((f) => {
                                        return jsx("option", { value: f.id, children: f.name }, v4());
                                    }) }, v4());
                            }) })] }) }), jsxs("td", { children: [jsxs("div", { className: "airwpsync-field form-required", children: [jsxs("label", { children: [jsx("span", { children: texts.import_as }), jsx("span", { className: "airwpsync-required", "aria-hidden": "true", children: "*" }), jsx("span", { className: "screen-reader-text", children: texts.required_field_indicator })] }), jsxs("select", { name: "wordpress[]", className: "airwpsync-input regular-text ltr", value: wordPressFieldId, onChange: wordPressFieldChangedHandler, children: [jsx("option", { value: "" }, "default"), Object.keys(mappingManager.wordPressSelectFieldsOptions[index]).map((groupKey) => {
                                        const group = mappingManager.wordPressSelectFieldsOptions[index][groupKey];
                                        return jsx("optgroup", { label: group.label, children: group.options.map((option) => {
                                                return jsx("option", { value: option.value, disabled: !option.enabled, children: option.label }, v4());
                                            }) }, v4());
                                    })] })] }), wordPressFieldConfig.notice ? jsx("small", { children: wordPressFieldConfig.notice }) : null, wordPressFieldId && wordPressFieldId.split('::')[1] === 'custom_field' ? renderCustomFieldOptions() : null] }), jsxs("td", { className: "airwpsync-c-mapping-row__actions", children: [jsx("div", { className: "airwpsync-c-mapping-row__btn-sort btn btn-sort dashicons-before dashicons-menu", children: jsx("span", { className: "screen-reader-text", children: texts.sort }) }), jsxs("button", { type: "button", className: "airwpsync-c-mapping-row__btn-remove btn btn-remove", onClick: removeMappingRowHandler, children: [jsx("span", { className: "btn-remove-close-icon", "aria-hidden": "true", children: "\u00D7" }), jsx("span", { className: "screen-reader-text", children: texts.remove })] })] })] });
}

export { MappingRow as default };
//# sourceMappingURL=mapping-row.js.map
