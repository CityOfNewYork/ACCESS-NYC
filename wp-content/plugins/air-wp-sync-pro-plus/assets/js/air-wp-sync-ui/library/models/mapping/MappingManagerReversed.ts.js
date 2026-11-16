import { arrayMoveImmutable } from 'array-move';
import { v4 } from 'uuid';

const isWordPressOptionEnabled = (option, wordPressFieldsSelected, airtableFieldsSelected) => {
    return option.enabled && (wordPressFieldsSelected.indexOf(option.value) === -1 || option.allow_multiple);
};
const isAirtableOptionEnabled = (option, wordPressFieldsSelected, airtableFieldsSelected) => {
    return true;
};
class MappingManagerReversed {
    fields;
    wordPressFields;
    setMapping;
    mapping;
    allSupportedAirtableTypes;
    _airtableSelectFieldGroupsOptions;
    _wordPressSelectFieldsOptions;
    indexedWordPressFields;
    indexedAirtablesFields;
    template;
    constructor(mappingInit, setMapping, fields, wordPressFields, defaultMappingOptions, isOptionAvailable, template = null, wordPressOptionEnablingStrategy = isWordPressOptionEnabled, airtableOptionEnablingStrategy = isAirtableOptionEnabled) {
        const self = this;
        this.fields = fields;
        this.wordPressFields = wordPressFields;
        this.setMapping = setMapping;
        this.mapping = mappingInit.map((m) => {
            if (m.key) {
                return m;
            }
            return { ...m, key: v4() };
        });
        this.template = template;
        this.allSupportedAirtableTypes = Object.keys(defaultMappingOptions).reduce((result, groupName) => {
            result = result.concat(defaultMappingOptions[groupName].options.reduce((supported_sources, option) => {
                supported_sources = supported_sources.concat(option.supported_sources);
                return supported_sources;
            }, []));
            return result;
        }, []);
        this.indexedWordPressFields = Object.keys(defaultMappingOptions).reduce(function (result, groupName) {
            defaultMappingOptions[groupName].options.forEach((field) => {
                result[field.value] = field;
            }, []);
            return result;
        }, {});
        this.indexedAirtablesFields = fields.reduce(function (result, airtableField) {
            result[airtableField.id] = airtableField;
            return result;
        }, {});
        const wordPressFieldsSelected = this.mapping.map((fieldMapping) => {
            return fieldMapping.wordpress;
        }, []);
        const airtableFieldsSelected = this.mapping.map((fieldMapping) => {
            return fieldMapping.airtable;
        }, []);
        this._airtableSelectFieldGroupsOptions = this.mapping.map((fieldMapping) => {
            return fields.reduce(function (result, field) {
                const wordPressField = self.getWordPressFieldById(fieldMapping.wordpress);
                if (wordPressField && wordPressField.supported_sources.indexOf(field.type) === -1) {
                    return result;
                }
                const group = field.group;
                if (!(group in result)) {
                    result[group] = {
                        label: group,
                        options: []
                    };
                }
                result[group].options.push({
                    ...field,
                    enabled: airtableOptionEnablingStrategy(field, wordPressFieldsSelected, airtableFieldsSelected)
                });
                return result;
            }, {});
        }, []);
        this._wordPressSelectFieldsOptions = Object.keys(defaultMappingOptions).reduce((mappingOptions, groupName) => {
            const group = { ...defaultMappingOptions[groupName] };
            const groupOptions = group.options.filter(function (option) {
                return isOptionAvailable(option.value);
            });
            if (groupOptions.length === 0) {
                return mappingOptions;
            }
            mappingOptions[groupName] = group;
            group.options = group.options.map(function (option) {
                return {
                    ...option,
                    enabled: wordPressOptionEnablingStrategy(option, wordPressFieldsSelected, airtableFieldsSelected)
                };
            });
            return mappingOptions;
        }, {});
    }
    getAirtableFirstOption() {
        return this.fields.length > 0 ? this.fields[0].id : '';
    }
    getWordPressFirstOption() {
        return this.wordPressFields.length > 0 ? this.wordPressFields[0].value : '';
    }
    addMappingRow() {
        this.getWordPressFirstOption();
        this.setMapping([
            ...this.mapping,
            {
                airtable: '',
                wordpress: '',
                options: {},
                key: v4()
            }
        ]);
    }
    ;
    updateAirtableField(index, airtableFieldId) {
        this.setMapping(this.mapping.map((el, i) => {
            if (i === index) {
                return {
                    ...el,
                    airtable: airtableFieldId
                };
            }
            return el;
        }));
    }
    ;
    updateWordPressField(index, wordPressFieldId) {
        this.setMapping(this.mapping.map((el, i) => {
            if (i === index) {
                let airtable = el.airtable;
                // Check if we can automatically map to an airtable field.
                if (wordPressFieldId && airtable === '' && this.template && this.template[wordPressFieldId]) {
                    airtable = this.template[wordPressFieldId].airtable;
                }
                return {
                    ...el,
                    wordpress: wordPressFieldId,
                    airtable
                };
            }
            return el;
        }));
    }
    ;
    updateFieldOption(index, optionName, optionValue) {
        this.setMapping(this.mapping.map((el, i) => {
            if (i === index) {
                return {
                    ...el,
                    options: {
                        ...el.options,
                        [optionName]: optionValue
                    }
                };
            }
            return el;
        }));
    }
    ;
    removeMappingRow(index) {
        this.setMapping(this.mapping.filter((el, i) => i !== index));
    }
    ;
    moveMappingRow(oldIndex, newIndex) {
        this.setMapping(arrayMoveImmutable(this.mapping, oldIndex, newIndex));
    }
    ;
    getWordPressFieldById(wordPressFieldId) {
        return this.indexedWordPressFields[wordPressFieldId];
    }
    ;
    getAirtableFieldById(airtableFieldId) {
        return this.indexedAirtablesFields[airtableFieldId];
    }
    ;
    get airtableSelectFieldGroupsOptions() {
        return this._airtableSelectFieldGroupsOptions;
    }
    get wordPressSelectFieldsOptions() {
        return this._wordPressSelectFieldsOptions;
    }
}

export { MappingManagerReversed as default };
//# sourceMappingURL=MappingManagerReversed.ts.js.map
