import { arrayMoveImmutable } from 'array-move';
import { v4 } from 'uuid';
import { getAirtableFieldById } from './mapping/helpers.ts.js';

class MappingManager {
    fields;
    setMapping;
    mapping;
    allSupportedAirtableTypes;
    _airtableSelectFieldGroupsOptions;
    _wordPressSelectFieldsOptions;
    indexedWordPressFields;
    indexedAirtablesFields;
    constructor(mappingInit, setMapping, fields, defaultMappingOptions, isOptionAvailable) {
        const self = this;
        this.fields = fields;
        this.setMapping = setMapping;
        this.mapping = mappingInit.map((m) => {
            if (m.key) {
                return m;
            }
            return { ...m, key: v4() };
        });
        this.allSupportedAirtableTypes = Object.keys(defaultMappingOptions).reduce((result, groupName) => {
            result = result.concat(defaultMappingOptions[groupName].options.reduce((supported_sources, option) => {
                supported_sources = supported_sources.concat(option.supported_sources);
                return supported_sources;
            }, []));
            return result;
        }, []);
        this._airtableSelectFieldGroupsOptions = fields.reduce(function (result, field) {
            if (self.allSupportedAirtableTypes.indexOf(field.type) === -1) {
                return result;
            }
            const group = field.group;
            if (!(group in result)) {
                result[group] = {
                    label: group,
                    options: []
                };
            }
            result[group].options.push(field);
            return result;
        }, {});
        this._wordPressSelectFieldsOptions = this.mapping.map((field) => {
            const mappingOptions = {};
            const airtableField = getAirtableFieldById(field.airtable, fields);
            // Filter options by post type
            for (const groupName in defaultMappingOptions) {
                const group = defaultMappingOptions[groupName];
                const groupOptions = group.options.filter(function (option) {
                    return isOptionAvailable(option.value);
                });
                if (groupOptions.length > 0) {
                    mappingOptions[groupName] = { ...group, options: groupOptions };
                }
            }
            // Filter options by supported types
            let airtableType = airtableField ? airtableField.type : '';
            for (const groupName in mappingOptions) {
                const group = mappingOptions[groupName];
                group.options = group.options.filter(function (option) {
                    return option.supported_sources.indexOf(airtableType) > -1;
                });
                if (group.options.length === 0) {
                    delete mappingOptions[groupName];
                }
            }
            const rowValue = field.wordpress ?? null;
            // Check if some options must be disabled
            for (const groupName in mappingOptions) {
                const group = mappingOptions[groupName];
                group.options = group.options.map(function (option) {
                    return {
                        ...option,
                        enabled: option.enabled && (option.value === rowValue || !self.isOptionDisabled(option))
                    };
                });
            }
            return mappingOptions;
        });
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
    }
    isOptionDisabled(option) {
        if (option.allow_multiple) {
            return false;
        }
        return this.mapping.reduce(function (result, current) {
            return current.wordpress && current.wordpress === option.value ? true : result;
        }, false);
    }
    getAirtableFirstOption() {
        return this.fields.length > 0 ? this.fields[0].id : '';
    }
    addMappingRow() {
        const airtableFirstOption = this.getAirtableFirstOption();
        this.setMapping([
            ...this.mapping,
            {
                airtable: airtableFirstOption,
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
                return {
                    ...el,
                    wordpress: wordPressFieldId
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

export { MappingManager as default };
//# sourceMappingURL=mapping.js.map
