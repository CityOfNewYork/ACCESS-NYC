import { jsxs, Fragment, jsx } from 'react/jsx-runtime';
import ReactSelect, { components } from 'react-select';
import AsyncSelect from 'react-select/async';
import SvgDropdownIndicator from '../graphics/icons/DropdownIndicator.tsx.js';
import { useId } from 'react';
import classnames from 'classnames';

/**
 * Select. <br />
 * Display a dropdown list. Accepts other props from <a href="https://react-select.com/props#statemanager-props" target="_blank">React Select</a> component appart from components, styles and theme.
 *
 * @param {React.ReactNode} label
 * @param {React.ReactNode} instructions
 * @param {('idle' | 'valid' | 'invalid')} status
 * @param {object} ...reactSelectProps Other props from React Select component.
 *
 * @constructor
 */
function Select({ label, labelHidden, instructions, status, ...reactSelectProps }) {
    const inputId = useId();
    if ('undefined' === typeof reactSelectProps.isSearchable) {
        reactSelectProps.isSearchable = false;
    }
    return jsxs(Fragment, { children: [jsx("label", { className: classnames({
                    'airwpsync-c-input__label': true,
                    'screen-reader-text': labelHidden,
                }), htmlFor: inputId, children: label }), jsxs("div", { className: `airwpsync-c-select__select-wrapper`, children: [jsx(ReactSelect, { ...reactSelectProps, ...selectPropsModifier({ status }) }), instructions ? jsx("p", { className: `airwpsync-c-input__instructions`, children: instructions }) : null] })] });
}
function SelectAsync({ label, instructions, status, ...reactSelectProps }) {
    const inputId = useId();
    if ('undefined' === typeof reactSelectProps.isSearchable) {
        reactSelectProps.isSearchable = false;
    }
    return jsxs(Fragment, { children: [jsx("label", { className: "airwpsync-c-input__label", htmlFor: inputId, children: label }), jsxs("div", { className: `airwpsync-c-select__select-wrapper`, children: [jsx(AsyncSelect, { ...reactSelectProps, ...selectAsyncPropsModifier({ status }) }), instructions ? jsx("p", { className: `airwpsync-c-input__instructions`, children: instructions }) : null] })] });
}
const DropdownIndicator = (props) => {
    return (jsx(components.DropdownIndicator, { ...props, children: jsx(SvgDropdownIndicator, {}) }));
};
const Option = (props) => {
    return (jsxs(components.Option, { ...props, children: [props.children, 
            // @ts-ignore `description` is not part of the default option typescript definition
            props.data?.description ? jsx("div", { className: "airwpsync-c-select__option-description", children: props.data.description }) : null] }));
};
const selectPropsModifier = ({ status }) => {
    return {
        classNamePrefix: "airwpsync-c-select",
        components: {
            DropdownIndicator,
            Option
        },
        // menuIsOpen: true, // useful for debug
        styles: {
            container: (baseStyles, state) => {
                return {
                    ...baseStyles,
                    maxWidth: '768px',
                    fontSize: '1rem',
                };
            },
            control: (baseStyles, state) => {
                let borderColor = 'var(--airwpsync--color--primary-100)';
                if (state.isFocused) {
                    borderColor = 'var(--airwpsync--color--primary-400)';
                }
                else if ('invalid' === status) {
                    borderColor = 'var(--airwpsync--color--error)';
                }
                return {
                    ...baseStyles,
                    borderColor,
                    borderBottomLeftRadius: state.menuIsOpen ? 0 : '',
                    borderBottomRightRadius: state.menuIsOpen ? 0 : '',
                    boxShadow: 'none',
                };
            },
            placeholder: (baseStyles) => ({
                ...baseStyles,
                color: 'var(--airwpsync--color--primary-200)'
            }),
            indicatorSeparator: (baseStyles, state) => {
                return {
                    ...baseStyles,
                    width: 0
                };
            },
            dropdownIndicator: (baseStyles, state) => {
                return {
                    ...baseStyles,
                    color: 'var(--airwpsync--color--primary)',
                    transform: state.selectProps.menuIsOpen ? 'scaleY(-1)' : '',
                    ':hover': {
                        color: 'var(--airwpsync--color--primary-900)',
                    }
                };
            },
            clearIndicator: (baseStyles, state) => {
                return {
                    ...baseStyles,
                    color: 'var(--airwpsync--color--primary)',
                    ':hover': {
                        color: 'var(--airwpsync--color--primary-900)',
                    }
                };
            },
            menuList: (baseStyles, state) => {
                return {
                    ...baseStyles,
                    paddingTop: '12px',
                    paddingBottom: '12px'
                };
            },
            menu: (baseStyles, state) => {
                return {
                    ...baseStyles,
                    marginTop: '-1px',
                    borderTopLeftRadius: 0,
                    borderTopRightRadius: 0
                };
            },
            option: (baseStyles, state) => {
                return {
                    ...baseStyles,
                    color: state.isSelected ?
                        '#FFF'
                        : (state.isDisabled ?
                            'var(--airwpsync--color--primary-200)'
                            : 'var(--airwpsync--color--primary-400)'),
                };
            }
        },
        theme: (theme) => ({
            ...theme,
            borderRadius: 4,
            colors: {
                ...theme.colors,
                primary25: 'var(--airwpsync--color--primary-50)',
                primary50: 'var(--airwpsync--color--primary-200)',
                primary75: 'var(--airwpsync--color--primary-300)',
                primary: 'var(--airwpsync--color--primary)',
            },
        })
    };
};
const selectAsyncPropsModifier = (props) => {
    return selectPropsModifier(props);
};

export { SelectAsync, Select as default };
//# sourceMappingURL=select.js.map
