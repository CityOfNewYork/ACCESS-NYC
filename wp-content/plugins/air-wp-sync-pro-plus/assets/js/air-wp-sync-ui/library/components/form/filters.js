import { jsx, jsxs, Fragment } from 'react/jsx-runtime';
import Button from './button.js';
import classnames from 'classnames';
import Select, { SelectAsync } from './select.js';
import { useMemo, useState, useEffect, useId } from 'react';
import Input, { InputType } from './input.js';
import SvgCircleTrash from '../graphics/icons/CircleTrash.tsx.js';
import SvgChecked from '../graphics/icons/Checked.tsx.js';
import '../../ButtonBase.js';
import 'react-select';
import 'react-select/async';
import '../graphics/icons/DropdownIndicator.tsx.js';
import '../graphics/icons/CircleChecked.tsx.js';
import '../graphics/icons/Info.tsx.js';

var FilterPropsOperators;
(function (FilterPropsOperators) {
    FilterPropsOperators["GreaterThan"] = ">";
    FilterPropsOperators["Is"] = "=";
    FilterPropsOperators["IsNot"] = "!=";
    FilterPropsOperators["GreaterOrEqualThan"] = ">=";
    FilterPropsOperators["LesserThan"] = "<";
    FilterPropsOperators["LesserOrEqualThan"] = "<=";
    FilterPropsOperators["Contains"] = "contains";
    FilterPropsOperators["DoesNotContain"] = "doesNotContain";
    FilterPropsOperators["IsEmpty"] = "isEmpty";
    FilterPropsOperators["IsNotEmpty"] = "isNotEmpty";
    FilterPropsOperators["IsWithin"] = "isWithin";
    FilterPropsOperators["IsAnyOf"] = "isAnyOf";
    FilterPropsOperators["IsNoneOf"] = "isNoneOf";
    FilterPropsOperators["HasAnyOf"] = "|";
    FilterPropsOperators["HasAllOf"] = "&";
    FilterPropsOperators["HasNoneOf"] = "hasNoneOf";
    FilterPropsOperators["Filename"] = "filename";
    FilterPropsOperators["Filetype"] = "filetype";
})(FilterPropsOperators || (FilterPropsOperators = {}));
var PeriodSelectValues;
(function (PeriodSelectValues) {
    PeriodSelectValues["PastWeek"] = "pastWeek";
    PeriodSelectValues["PastMonth"] = "pastMonth";
    PeriodSelectValues["PastYear"] = "pastYear";
    PeriodSelectValues["NextWeek"] = "nextWeek";
    PeriodSelectValues["NextMonth"] = "nextMonth";
    PeriodSelectValues["NextYear"] = "nextYear";
    PeriodSelectValues["CalendarWeek"] = "calendarWeek";
    PeriodSelectValues["CalendarMonth"] = "calendarMonth";
    PeriodSelectValues["CalendarYear"] = "calendarYear";
    PeriodSelectValues["NextNumberOfDays"] = "nextNumberOfDays";
    PeriodSelectValues["PastNumberOfDays"] = "pastNumberOfDays";
    PeriodSelectValues["ExactDate"] = "exactDate";
    PeriodSelectValues["Today"] = "today";
    PeriodSelectValues["Tomorrow"] = "tomorrow";
    PeriodSelectValues["Yesterday"] = "yesterday";
    PeriodSelectValues["OneWeekAgo"] = "oneWeekAgo";
    PeriodSelectValues["OneWeekFromNow"] = "oneWeekFromNow";
    PeriodSelectValues["OneMonthAgo"] = "oneMonthAgo";
    PeriodSelectValues["OneMonthFromNow"] = "oneMonthFromNow";
    PeriodSelectValues["DaysAgo"] = "daysAgo";
    PeriodSelectValues["DaysFromNow"] = "daysFromNow";
})(PeriodSelectValues || (PeriodSelectValues = {}));
var FileTypes;
(function (FileTypes) {
    FileTypes["Image"] = "image";
    FileTypes["Text"] = "text";
})(FileTypes || (FileTypes = {}));

const getFiltersComparisonOptions = (texts) => {
    return {
        'string': [
            { value: FilterPropsOperators.Contains, label: texts.compare.contains },
            { value: FilterPropsOperators.DoesNotContain, label: texts.compare.does_not_contains },
            { value: FilterPropsOperators.Is, label: texts.compare.is },
            { value: FilterPropsOperators.IsNot, label: texts.compare.is_not },
            { value: FilterPropsOperators.IsEmpty, label: texts.compare.is_empty, hideInput: true },
            { value: FilterPropsOperators.IsNotEmpty, label: texts.compare.is_not_empty, hideInput: true, },
        ],
        'number': [
            { value: FilterPropsOperators.Is, label: '=', inputType: InputType.Number },
            { value: FilterPropsOperators.IsNot, label: '≠', inputType: InputType.Number },
            { value: FilterPropsOperators.LesserThan, label: '<', inputType: InputType.Number },
            { value: FilterPropsOperators.GreaterThan, label: '>', inputType: InputType.Number },
            { value: FilterPropsOperators.LesserOrEqualThan, label: '≤', inputType: InputType.Number },
            { value: FilterPropsOperators.GreaterOrEqualThan, label: '≥', inputType: InputType.Number },
            { value: FilterPropsOperators.IsEmpty, label: texts.compare.is_empty, hideInput: true },
            { value: FilterPropsOperators.IsNotEmpty, label: texts.compare.is_not_empty, hideInput: true, },
        ],
        'date': [
            { value: FilterPropsOperators.Is, label: texts.compare.is, inputType: 'period' },
            { value: FilterPropsOperators.IsWithin, label: texts.compare.is_within, inputType: 'period' },
            { value: FilterPropsOperators.LesserThan, label: texts.compare.is_before, inputType: 'period' },
            { value: FilterPropsOperators.GreaterThan, label: texts.compare.is_after, inputType: 'period' },
            { value: FilterPropsOperators.LesserOrEqualThan, label: texts.compare.is_on_or_before, inputType: 'period' },
            { value: FilterPropsOperators.GreaterOrEqualThan, label: texts.compare.is_on_or_after, inputType: 'period' },
            { value: FilterPropsOperators.IsNot, label: texts.compare.is_not, inputType: 'period' },
        ],
        'user': [
            { value: FilterPropsOperators.Is, label: texts.compare.is, inputType: 'user' },
            { value: FilterPropsOperators.IsNot, label: texts.compare.is_not, inputType: 'user' },
            { value: FilterPropsOperators.IsAnyOf, label: texts.compare.is_any_of, inputType: 'multi_user' },
            { value: FilterPropsOperators.IsNoneOf, label: texts.compare.is_none_of, inputType: 'multi_user' },
            { value: FilterPropsOperators.IsEmpty, label: texts.compare.is_empty, hideInput: true },
            { value: FilterPropsOperators.IsNotEmpty, label: texts.compare.is_not_empty, hideInput: true },
        ],
        'select': [
            { value: FilterPropsOperators.Is, label: texts.compare.is, inputType: 'select' },
            { value: FilterPropsOperators.IsNot, label: texts.compare.is, inputType: 'select' },
            { value: FilterPropsOperators.IsAnyOf, label: texts.compare.is_any_of, inputType: 'multi_select' },
            { value: FilterPropsOperators.IsNoneOf, label: texts.compare.is_none_of, inputType: 'multi_select' },
            { value: FilterPropsOperators.IsEmpty, label: texts.compare.is_empty, hideInput: true },
            { value: FilterPropsOperators.IsNotEmpty, label: texts.compare.is_not_empty, hideInput: true },
        ],
        'multi_select': [
            { value: FilterPropsOperators.HasAnyOf, label: texts.compare.has_any_of, inputType: 'multi_select' },
            { value: FilterPropsOperators.HasAllOf, label: texts.compare.has_all_of, inputType: 'multi_select' },
            { value: FilterPropsOperators.Is, label: texts.compare.is_exactly, inputType: 'multi_select' },
            { value: FilterPropsOperators.HasNoneOf, label: texts.compare.has_none_of, inputType: 'multi_select' },
            { value: FilterPropsOperators.IsEmpty, label: texts.compare.is_empty, hideInput: true },
            { value: FilterPropsOperators.IsNotEmpty, label: texts.compare.is_not_empty, hideInput: true },
        ],
        'link_to_another_record': [
            { value: FilterPropsOperators.HasAnyOf, label: texts.compare.has_any_of, inputType: 'link_to_another_record' },
            { value: FilterPropsOperators.HasAllOf, label: texts.compare.has_all_of, inputType: 'link_to_another_record' },
            { value: FilterPropsOperators.Is, label: texts.compare.is_exactly, inputType: 'link_to_another_record' },
            { value: FilterPropsOperators.IsNoneOf, label: texts.compare.has_none_of, inputType: 'link_to_another_record' },
            { value: FilterPropsOperators.Contains, label: texts.compare.contains },
            { value: FilterPropsOperators.DoesNotContain, label: texts.compare.does_not_contains },
            { value: FilterPropsOperators.IsEmpty, label: texts.compare.is_empty, hideInput: true },
            { value: FilterPropsOperators.IsNotEmpty, label: texts.compare.is_not_empty, hideInput: true },
        ],
        'attachment': [
            { value: FilterPropsOperators.Filename, label: texts.compare.filenames_contains },
            // We can't search by file type right now with the formula filter
            //{ value: FilterPropsOperators.Filetype, label: texts.compare.has_file_type, inputType: 'filetype' },
            { value: FilterPropsOperators.IsEmpty, label: texts.compare.is_empty, hideInput: true },
            { value: FilterPropsOperators.IsNotEmpty, label: texts.compare.is_not_empty, hideInput: true },
        ],
        'checkbox': [
            { value: FilterPropsOperators.Is, label: texts.compare.is, inputType: 'checkbox' },
        ]
    };
};
const specificDateOperator = [FilterPropsOperators.Is, FilterPropsOperators.LesserThan, FilterPropsOperators.GreaterThan, FilterPropsOperators.LesserOrEqualThan, FilterPropsOperators.GreaterOrEqualThan, FilterPropsOperators.IsNot];
const rangeDateOperator = [FilterPropsOperators.IsWithin];
const getPeriodsOptions = (texts, operator) => {
    const periodOptions = [
        {
            value: PeriodSelectValues.PastWeek,
            label: texts.periods.past_week,
            hideInput: true,
            supportedOperators: rangeDateOperator
        },
        {
            value: PeriodSelectValues.PastMonth,
            label: texts.periods.past_month,
            hideInput: true,
            supportedOperators: rangeDateOperator
        },
        {
            value: PeriodSelectValues.PastYear,
            label: texts.periods.past_year,
            hideInput: true,
            supportedOperators: rangeDateOperator
        },
        {
            value: PeriodSelectValues.NextWeek,
            label: texts.periods.next_week,
            hideInput: true,
            supportedOperators: rangeDateOperator
        },
        {
            value: PeriodSelectValues.NextMonth,
            label: texts.periods.next_month,
            hideInput: true,
            supportedOperators: rangeDateOperator
        },
        {
            value: PeriodSelectValues.NextYear,
            label: texts.periods.next_year,
            hideInput: true,
            supportedOperators: rangeDateOperator
        },
        {
            value: PeriodSelectValues.CalendarWeek,
            label: texts.periods.calendar_week,
            hideInput: true,
            supportedOperators: rangeDateOperator
        },
        {
            value: PeriodSelectValues.CalendarMonth,
            label: texts.periods.calendar_month,
            hideInput: true,
            supportedOperators: rangeDateOperator
        },
        {
            value: PeriodSelectValues.CalendarYear,
            label: texts.periods.calendar_year,
            hideInput: true,
            supportedOperators: rangeDateOperator
        },
        {
            value: PeriodSelectValues.NextNumberOfDays,
            label: texts.periods.next_number_of_days,
            inputType: 'number',
            supportedOperators: rangeDateOperator
        },
        {
            value: PeriodSelectValues.PastNumberOfDays,
            label: texts.periods.past_number_of_days,
            inputType: 'number',
            supportedOperators: rangeDateOperator
        },
        {
            value: PeriodSelectValues.ExactDate,
            label: texts.periods.exact_date,
            inputType: 'date',
            supportedOperators: specificDateOperator
        },
        {
            value: PeriodSelectValues.Today,
            label: texts.periods.today,
            hideInput: true,
            supportedOperators: specificDateOperator
        },
        {
            value: PeriodSelectValues.Tomorrow,
            label: texts.periods.tomorrow,
            hideInput: true,
            supportedOperators: specificDateOperator
        },
        {
            value: PeriodSelectValues.Yesterday,
            label: texts.periods.yesterday,
            hideInput: true,
            supportedOperators: specificDateOperator
        },
        {
            value: PeriodSelectValues.OneWeekAgo,
            label: texts.periods.one_week_ago,
            hideInput: true,
            supportedOperators: specificDateOperator
        },
        {
            value: PeriodSelectValues.OneWeekFromNow,
            label: texts.periods.one_week_from_now,
            hideInput: true,
            supportedOperators: specificDateOperator
        },
        {
            value: PeriodSelectValues.OneMonthAgo,
            label: texts.periods.one_month_ago,
            hideInput: true,
            supportedOperators: specificDateOperator
        },
        {
            value: PeriodSelectValues.OneMonthFromNow,
            label: texts.periods.one_month_from_now,
            hideInput: true,
            supportedOperators: specificDateOperator
        },
        {
            value: PeriodSelectValues.DaysAgo,
            label: texts.periods.number_of_days_ago,
            inputType: 'number',
            supportedOperators: specificDateOperator
        },
        {
            value: PeriodSelectValues.DaysFromNow,
            label: texts.periods.number_of_days_from_now,
            inputType: 'number',
            supportedOperators: specificDateOperator
        }
    ];
    const filteredPeriodOptions = Object.values(periodOptions).filter((periodOption) => {
        return !periodOption.supportedOperators || periodOption.supportedOperators.indexOf(operator) > -1;
    });
    return filteredPeriodOptions.reduce((carry, periodOption) => {
        carry[periodOption.value] = periodOption;
        return carry;
    }, {});
};
const getConjunctionOptions = (texts) => ({
    and: { value: 'and', label: texts.and },
    or: { value: 'or', label: texts.or }
});
const getFileTypes = (texts) => ({
    [FileTypes.Image]: { value: 'image', label: texts.file_types.image },
    [FileTypes.Text]: { value: 'text', label: texts.file_types.text }
});

const updateFilterAt = (filters, index, newFilter) => {
    return [...filters.slice(0, index), newFilter, ...filters.slice(index + 1)];
};
const removeFilterAt = (filters, index) => {
    return [...filters.slice(0, index), ...filters.slice(index + 1)];
};
const getFilterById = (airtableFilterOptions, filterId) => {
    return airtableFilterOptions.find((fieldOption) => fieldOption.id === filterId);
};
const getFilterTypeFiltersComparisonOptions = (filtersComparisonOptions, filterType) => {
    if (!filterType || !filtersComparisonOptions[filterType]) {
        return [];
    }
    return filtersComparisonOptions[filterType];
};
const getComparisonOptionByOperator = (comparisonOptions, operator) => {
    return comparisonOptions.find((comparisonOption) => comparisonOption.value === operator);
};
const collaboratorToSelectOption = (collaborator) => {
    return {
        value: collaborator.name,
        label: collaborator.name,
    };
};
const isPeriodValue = (value) => {
    return typeof value === 'object' && !('mode' in value);
};
const linkToAnotherRecordToSelectOption = (linkToAnotherRecord) => {
    return {
        value: linkToAnotherRecord.id,
        label: linkToAnotherRecord.name,
    };
};
const createCheckFilterProps = (texts, airtableFilterOptions, filtersComparisonOptions) => {
    return (newFilterProps) => {
        const newFilter = getFilterById(airtableFilterOptions, newFilterProps.columnId);
        const newComparisonOptions = getFilterTypeFiltersComparisonOptions(filtersComparisonOptions, newFilter?.type);
        let newComparisonOptionValue = getComparisonOptionByOperator(newComparisonOptions, newFilterProps.operator);
        // If the operator is not available for the new filter, take the first one from the new list.
        if (!newComparisonOptionValue) {
            newComparisonOptionValue = newComparisonOptions[0];
            newFilterProps.operator = newComparisonOptionValue.value;
        }
        const newInputType = newComparisonOptionValue?.inputType;
        // Check if the period is available too.
        if ('period' === newInputType) {
            const newPeriodOptions = getPeriodsOptions(texts, newFilterProps.operator);
            const periodValue = !isPeriodValue(newFilterProps.value)
                ? { numberOfDays: '', input: '', mode: Object.values(newPeriodOptions)[0].value }
                : newFilterProps.value;
            const periodOption = newPeriodOptions[periodValue.mode];
            if (!periodOption || (periodOption.supportedOperators && periodOption.supportedOperators.indexOf(newFilterProps.operator) === -1)) {
                periodValue.mode = Object.values(newPeriodOptions)[0].value;
            }
            newFilterProps.value = periodValue;
        }
        else if ('multi_user' === newInputType) {
            if (!Array.isArray(newFilterProps.value)) {
                newFilterProps.value = [];
            }
        }
        else if ('multi_select' === newInputType) {
            if (!Array.isArray(newFilterProps.value)) {
                newFilterProps.value = [];
            }
        }
        else if ('link_to_another_record' === newInputType) {
            if (!Array.isArray(newFilterProps.value)) {
                newFilterProps.value = [];
            }
        }
        else if ('filetype' === newInputType) {
            if (typeof newFilterProps.value !== 'string') {
                newFilterProps.value = '';
            }
            if (FilterPropsOperators.Filetype === newFilterProps.operator && !isFileType(newFilterProps.value)) {
                newFilterProps.value = 'image';
            }
        }
        else if (typeof newFilterProps.value !== 'string') {
            // If it's not a period, reset the value to an empty string.
            newFilterProps.value = '';
        }
        return newFilterProps;
    };
};
const isFileType = (value) => {
    return typeof value === 'string' && Object.values(FileTypes).includes(value);
};

/**
 * Filters. <br />
 * Filters based on Airtable's ones. Can be nested with groups (up to 3 levels). <br />
 *
 *
 * @constructor
 */
const Filters = ({ conjunction, filters, texts, onChange, airtableFilterOptions, fetchFn }) => {
    const filtersComparisonOptions = getFiltersComparisonOptions(texts);
    return jsx("div", { className: "airwpsync-c-filters", children: jsx(FiltersGroup, { ...{ conjunction, filters, texts, onChange, airtableFilterOptions, filtersComparisonOptions, fetchFn } }) });
};
const FiltersGroup = ({ conjunction, filters, texts, onChange, airtableFilterOptions, filtersComparisonOptions, fetchFn, depth = 0 }) => {
    const firstFieldOption = airtableFilterOptions[0];
    const checkFilterProps = createCheckFilterProps(texts, airtableFilterOptions, filtersComparisonOptions);
    return jsxs("div", { className: classnames({
            'airwpsync-c-filters-group': true,
            'airwpsync-c-filters-group--alt': depth % 2 > 0,
        }), children: [filters.length > 0 ?
                jsx("div", { className: "airwpsync-c-filters-group__filters", children: filters.map((filterProps, index) => {
                        return renderFiltersGroupFilter({
                            conjunction,
                            filters,
                            texts,
                            onChange,
                            airtableFilterOptions,
                            filtersComparisonOptions,
                            depth,
                            fetchFn
                        }, filterProps, index);
                    }) })
                : null, jsx(Button, { type: "button", buttonType: "secondary", onClick: () => {
                    onChange({
                        conjunction,
                        filters: [...filters, checkFilterProps({
                                operator: filtersComparisonOptions[firstFieldOption.type][0].value,
                                columnId: firstFieldOption.id,
                                columnName: firstFieldOption.name,
                                value: ''
                            })]
                    });
                }, children: texts.addFilterBtnText }), jsx(Button, { type: "button", buttonType: "secondary", disabled: depth >= 2, title: depth >= 2 ? texts.maxNestedFilterGroupReached : '', onClick: () => {
                    onChange({
                        conjunction,
                        filters: [...filters, { conjunction: 'and', filters: [] }]
                    });
                }, children: texts.addFilterGroupBtnText })] });
};
const renderFiltersGroupFilter = (groupFilterProps, filterProps, index) => {
    const { conjunction, texts, onChange, filters, airtableFilterOptions, filtersComparisonOptions, fetchFn, depth = 0 } = groupFilterProps;
    let conjunctionElement = texts[conjunction];
    if (0 === index) {
        conjunctionElement = texts.where;
    }
    else if (1 === index) {
        const conjunctionOptions = getConjunctionOptions(texts);
        conjunctionElement = jsx(Select, { label: texts.conjunction, value: conjunctionOptions[conjunction], options: Object.values(conjunctionOptions), onChange: (newConjunction) => {
                onChange({
                    filters,
                    conjunction: newConjunction ? newConjunction.value : 'and'
                });
            } });
    }
    return jsxs("div", { className: "airwpsync-c-filters-filter", children: [jsx("div", { className: "airwpsync-c-filters-group__conjunction", children: conjunctionElement }), 'conjunction' in filterProps ?
                jsx("div", { className: "airwpsync-c-filters-group__group", children: jsx(FiltersGroup, { ...{
                            ...filterProps,
                            texts,
                            airtableFilterOptions,
                            filtersComparisonOptions,
                            fetchFn,
                            onChange: (props) => {
                                onChange({
                                    conjunction,
                                    filters: updateFilterAt(filters, index, props)
                                });
                            },
                            depth: depth + 1,
                        } }) })
                : jsx(Filter, { ...{
                        ...filterProps,
                        airtableFilterOptions,
                        filtersComparisonOptions,
                        texts,
                        fetchFn,
                        onChange: (props) => {
                            onChange({
                                conjunction,
                                filters: updateFilterAt(filters, index, props)
                            });
                        },
                        onRemove: () => {
                            onChange({
                                conjunction,
                                filters: removeFilterAt(filters, index)
                            });
                        },
                    } }), jsx("button", { type: "button", className: "airwpsync-c-filters-group__remove-btn airwpsync-u-reset-btn", onClick: () => {
                    onChange({
                        conjunction,
                        filters: removeFilterAt(filters, index)
                    });
                }, children: jsx(SvgCircleTrash, {}) })] }, index);
};
const Filter = ({ columnName, columnId, operator, value, airtableFilterOptions, filtersComparisonOptions, texts, fetchFn, onChange, onRemove }) => {
    const filterProps = { columnName, columnId, operator, value };
    const filterOptions = airtableFilterOptions.map(({ id, name }) => ({ value: id, label: name }));
    const filter = getFilterById(airtableFilterOptions, columnId);
    const fieldOptionValue = filterOptions.find((filterOption) => filterOption.value === columnId);
    const comparisonOptions = getFilterTypeFiltersComparisonOptions(filtersComparisonOptions, filter?.type);
    const comparisonOptionValue = getComparisonOptionByOperator(comparisonOptions, operator);
    const checkFilterProps = createCheckFilterProps(texts, airtableFilterOptions, filtersComparisonOptions);
    let inputType = comparisonOptionValue?.inputType;
    const children = [];
    // Add filters/fields select.
    children.push(jsx("div", { className: "airwpsync-c-filters-filter__select-field", children: jsx(Select, { label: '', options: filterOptions, placeholder: texts.selectFieldPlaceholder, value: fieldOptionValue, menuShouldScrollIntoView: false, onChange: (newValue) => {
                if (newValue) {
                    const newFilter = getFilterById(airtableFilterOptions, newValue.value);
                    if (newFilter) {
                        const newFilterProps = {
                            ...filterProps,
                            columnId: newFilter.id,
                            columnName: newFilter.name,
                        };
                        onChange(checkFilterProps(newFilterProps));
                    }
                }
            } }) }, "filter-select-field"));
    // Add comparisons select.
    children.push(jsx("div", { className: "airwpsync-c-filters-filter__select-comparison", children: comparisonOptions ?
            jsx(Select, { label: '', options: comparisonOptions, placeholder: texts.selectComparisonPlaceholder, value: comparisonOptionValue, menuShouldScrollIntoView: false, onChange: (newValue) => {
                    if (newValue) {
                        onChange(checkFilterProps({
                            ...filterProps,
                            operator: newValue.value,
                        }));
                    }
                    else {
                        onRemove();
                    }
                } })
            : null }, "filter-select-comparison"));
    if ('period' === inputType && comparisonOptionValue) {
        children.push(jsx(PeriodsSelect, { ...{
                texts,
                key: 'filter-select-period',
                periodValue: value,
                operator: comparisonOptionValue.value,
                onChange: (newPeriodValue) => {
                    onChange({
                        ...filterProps,
                        value: newPeriodValue,
                    });
                }
            } }));
    }
    else if (('user' === inputType || 'multi_user' === inputType) && (typeof value === 'string' || Array.isArray(value))) {
        children.push(jsx(UserSelect, { texts: texts, isMulti: 'multi_user' === inputType, hideInput: !!comparisonOptionValue?.hideInput, value: value, fieldName: columnName, onChange: (newValue) => {
                onChange({
                    ...filterProps,
                    value: newValue,
                });
            }, fetchFn: fetchFn }, "filter-select-user"));
    }
    else if (('select' === inputType || 'multi_select' === inputType) && (Array.isArray(value) || typeof value === 'string')) {
        children.push(jsx(InputSelect, { texts: texts, options: (Array.isArray(filter?.options) ? filter?.options : []), isMulti: 'multi_select' === inputType, hideInput: !!comparisonOptionValue?.hideInput, value: value, onChange: (newValue) => {
                onChange({
                    ...filterProps,
                    value: newValue,
                });
            } }, "filter-input-select"));
    }
    else if ('link_to_another_record' === inputType && Array.isArray(value)) {
        children.push(jsx(LinkToAnotherRecordSelect, { texts: texts, hideInput: !!comparisonOptionValue?.hideInput, value: value, fieldName: columnName, onChange: (newValue) => {
                onChange({
                    ...filterProps,
                    value: newValue,
                });
            }, fetchFn: fetchFn }, "filter-select-link-to-another-record"));
    }
    else if ('filetype' === inputType && isFileType(value)) {
        const fileTypes = getFileTypes(texts);
        children.push(jsx("div", { className: "airwpsync-c-filters-filter__select-filetype", children: jsx(Select, { label: '', options: Object.values(fileTypes), value: fileTypes[value], onChange: (newValue) => {
                    onChange({
                        ...filterProps,
                        value: newValue ? newValue.value : '',
                    });
                } }) }, "filter-filetype"));
    }
    else if ('checkbox' === inputType && typeof value === 'string') {
        children.push(jsx(FilterInputCheckbox, { texts: texts, value: value, hideInput: !!comparisonOptionValue?.hideInput, onChange: (newValue) => {
                onChange({
                    ...filterProps,
                    value: newValue
                });
            } }, "filter-input-checkbox"));
    }
    else {
        children.push(jsx(FilterInput, { texts: texts, inputType: inputType, value: value, hideInput: !!comparisonOptionValue?.hideInput, onChange: (newValue) => {
                onChange({
                    ...filterProps,
                    value: newValue
                });
            } }, "filter-input"));
    }
    return jsx(Fragment, { children: children });
};
const PeriodsSelect = ({ texts, periodValue, operator, onChange }) => {
    const periodOptions = useMemo(() => {
        return getPeriodsOptions(texts, operator);
    }, [texts, operator]);
    const periodOption = periodOptions[periodValue.mode];
    const inputPropName = periodOption.inputType === 'number' ? 'numberOfDays' : 'input';
    return jsx(Fragment, { children: jsxs("div", { className: "airwpsync-c-filters-filter__select-period", children: [jsx("div", { children: jsx(Select, { label: '', options: Object.values(periodOptions), placeholder: texts.selectComparisonPlaceholder, value: periodOptions[periodValue.mode] ?? undefined, menuShouldScrollIntoView: false, onChange: (newValue) => {
                            if (newValue) {
                                onChange({
                                    ...periodValue,
                                    mode: newValue.value
                                });
                            }
                        } }) }), jsx(FilterInput, { texts: texts, inputType: periodOption.inputType, value: (periodValue[inputPropName] ?? '').toString(), hideInput: !!periodOption?.hideInput, onChange: (newValue) => {
                        onChange({
                            ...periodValue,
                            [inputPropName]: newValue
                        });
                    } })] }) });
};
const UserSelect = ({ texts, value, fieldName, hideInput, fetchFn, onChange, isMulti = false }) => {
    const [optionsValue, setOptionsValue] = useState(false);
    const [defaultOptions, setDefaultOptions] = useState([]);
    const flatValues = Array.isArray(optionsValue) ? optionsValue.map((option) => option.value).join('|') : false;
    useEffect(() => {
        if ((Array.isArray(value) && value.join('|') === flatValues) || (value === flatValues)) {
            return;
        }
        if (value.length === 0) {
            setOptionsValue([]);
            return;
        }
        const formData = new FormData();
        formData.set('field_name', fieldName);
        if (Array.isArray(value)) {
            value.forEach((id) => {
                formData.append('search[]', id);
            });
        }
        else {
            formData.set('search', value);
        }
        fetchFn('airtable-search-users', formData).then((result) => {
            if (!result.success || !result.data || !('map' in result.data) || result.data.length === 0) {
                if (!result.success || !result.data || !('map' in result.data)) {
                    console.error('fetchFn airtable-search-users', result);
                }
                setOptionsValue([]);
                return;
            }
            const initialValue = result.data.map(collaboratorToSelectOption);
            setDefaultOptions(initialValue);
            setOptionsValue(initialValue);
        });
    }, [value, fetchFn, flatValues, fieldName]);
    let result = null;
    if (!hideInput && Array.isArray(optionsValue)) {
        result = jsx(SelectAsync, { label: '', value: isMulti ? optionsValue : optionsValue[0], defaultOptions: defaultOptions, isMulti: isMulti, isClearable: true, isSearchable: true, onChange: (newValue) => {
                if (isMulti) {
                    onChange(Array.isArray(newValue) ? newValue.map((option) => option.value) : []);
                    setOptionsValue(Array.isArray(newValue) ? newValue : []);
                }
                else {
                    const isOption = newValue !== null && typeof newValue === 'object' && 'value' in newValue;
                    onChange(isOption ? newValue.value : '');
                    setOptionsValue(isOption ? [newValue] : []);
                }
            }, loadOptions: (inputValue, callback) => {
                const formData = new FormData();
                formData.set('field_name', fieldName);
                formData.set('search', inputValue);
                fetchFn('airtable-search-users', formData).then((result) => {
                    if (!result.success || !result.data || !('map' in result.data)) {
                        return;
                    }
                    const options = result.data.map(collaboratorToSelectOption);
                    callback(options);
                });
            } });
    }
    else if (!hideInput && !Array.isArray(optionsValue)) {
        console.error('UserSelect: optionsValue should be an array', optionsValue);
    }
    return jsxs("div", { className: "airwpsync-c-filters-filter__select-user", children: [" ", result] });
};
const LinkToAnotherRecordSelect = ({ texts, value, fieldName, hideInput, fetchFn, onChange }) => {
    const [optionsValue, setOptionsValue] = useState(false);
    const [defaultOptions, setDefaultOptions] = useState([]);
    const flatValues = Array.isArray(optionsValue) ? optionsValue.map((option) => option.value).join('|') : false;
    useEffect(() => {
        if (value.join('|') === flatValues) {
            return;
        }
        if (value.length === 0) {
            setOptionsValue([]);
            return;
        }
        const formData = new FormData();
        formData.set('field_name', fieldName);
        if (Array.isArray(value)) {
            value.forEach((id) => {
                formData.append('search[]', id);
            });
        }
        else {
            formData.set('search', value);
        }
        fetchFn('airtable-search-records', formData).then((result) => {
            if (!result.success || !result.data || !('map' in result.data) || result.data.length === 0) {
                setOptionsValue([]);
                return;
            }
            const initialValue = result.data.map(linkToAnotherRecordToSelectOption);
            setDefaultOptions(initialValue);
            setOptionsValue(initialValue);
        });
    }, [value, fetchFn, flatValues]);
    let result = null;
    if (!hideInput && Array.isArray(optionsValue)) {
        result = jsx(SelectAsync, { label: '', value: optionsValue, defaultOptions: defaultOptions, isMulti: true, isClearable: true, isSearchable: true, onChange: (newValue) => {
                onChange(Array.isArray(newValue) ? newValue.map((option) => option.value) : []);
                setOptionsValue(Array.isArray(newValue) ? newValue : []);
            }, loadOptions: (inputValue, callback) => {
                const formData = new FormData();
                formData.set('field_name', fieldName);
                formData.set('search', inputValue);
                fetchFn('airtable-search-records', formData).then((result) => {
                    if (!result.success || !result.data || !('map' in result.data)) {
                        return;
                    }
                    const options = result.data.map(collaboratorToSelectOption);
                    callback(options);
                });
            } });
    }
    return jsxs("div", { className: "airwpsync-c-filters-filter__select-link-to-another-record", children: [" ", result] });
};
const InputSelect = ({ texts, value, options, isMulti, onChange }) => {
    const optionSelected = isMulti ?
        options.filter((option) => value.indexOf(option.value) > -1)
        : options.find((option) => option.value === value);
    return jsx(Fragment, { children: jsx("div", { className: "airwpsync-c-filters-filter__select", children: jsx("div", { children: jsx(Select, { label: '', options: options, isMulti: isMulti, placeholder: texts.inputValuePlaceholder, value: optionSelected, menuShouldScrollIntoView: false, onChange: (newValue) => {
                        if (newValue) {
                            onChange(Array.isArray(newValue) ?
                                newValue.map((option) => option.value)
                                // @ts-ignore
                                : newValue.value);
                        }
                        else {
                            onChange(isMulti ? [] : '');
                        }
                    } }) }) }) });
};
const FilterInputCheckbox = ({ texts, hideInput, value, onChange }) => {
    const id = useId();
    return jsx("div", { className: "airwpsync-c-filters-filter__select-value airwpsync-c-filters-filter__select-value--checkbox", children: hideInput ?
            null
            : jsxs(Fragment, { children: [jsx("input", { id: id, className: "airwpsync-c-filters-filter__select-value-checkbox", placeholder: texts.inputValuePlaceholder, value: "1", checked: '1' === value, type: "checkbox", onChange: (event) => {
                            onChange(event.target.checked ? '1' : '0');
                        } }), jsxs("label", { htmlFor: id, children: [jsx(SvgChecked, {}), jsx("span", { className: "screen-reader-text", children: texts.checked })] })] }) });
};
const FilterInput = ({ texts, hideInput, value, inputType, onChange }) => {
    return jsx("div", { className: "airwpsync-c-filters-filter__select-value", children: hideInput ?
            null
            : jsx(Input, { label: '', placeholder: texts.inputValuePlaceholder, value: value, type: inputType, onChange: (event) => {
                    onChange(event.target.value);
                } }) });
};

export { Filters as default };
//# sourceMappingURL=filters.js.map
