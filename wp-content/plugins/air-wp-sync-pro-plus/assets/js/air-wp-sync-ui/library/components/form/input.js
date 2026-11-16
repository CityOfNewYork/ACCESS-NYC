import { jsx, jsxs, Fragment } from 'react/jsx-runtime';
import { useId } from 'react';
import classnames from 'classnames';
import SvgCircleChecked from '../graphics/icons/CircleChecked.tsx.js';
import SvgInfo from '../graphics/icons/Info.tsx.js';

var InputType;
(function (InputType) {
    InputType["Text"] = "text";
    InputType["Password"] = "password";
    InputType["Number"] = "number";
    InputType["Date"] = "date";
})(InputType || (InputType = {}));
/**
 * Input. <br />
 * Versatile input component. Displays a controlled html input with a label.
 *
 * @param {React.ReactNode} label
 * @param {string} id
 * @param {string} name
 * @param {string} value
 * @param {string} placeholder
 * @param {React.ReactNode} instructions
 * @param {('idle' | 'valid' | 'invalid')} status
 * @param {boolean} disabled
 * @param {('text' | 'password')} type
 * @param {object} ...props Other props from `<input />` React component.
 *
 * @constructor
 */
const Input = ({ label, labelHidden, id, name, value, placeholder, instructions, errorMessage, status = 'idle', disabled = false, type = InputType.Text, ...props }) => {
    const altId = useId();
    if (!id) {
        id = altId;
    }
    let icon = null;
    if ('valid' === status) {
        icon = jsx(SvgCircleChecked, {});
    }
    else if ('invalid' === status) {
        icon = jsx(SvgInfo, {});
    }
    return (jsxs(Fragment, { children: [jsx("label", { className: classnames({
                    'airwpsync-c-input__label': true,
                    'screen-reader-text': labelHidden,
                }), htmlFor: id, children: label }), jsxs("div", { className: `airwpsync-c-input__input-wrapper`, children: [icon ? jsx("div", { className: classnames({
                            'airwpsync-c-input__input-state-icon': true,
                            'airwpsync-c-input__input-state-icon--is-valid': 'valid' === status,
                            'airwpsync-c-input__input-state-icon--is-invalid': 'invalid' === status,
                            [`airwpsync-c-input__input--${type}`]: true
                        }), children: icon }) : null, jsx("input", { id: id, name: name, className: classnames({
                            'airwpsync-c-input__input': true,
                            typeClassModifier: true,
                            'airwpsync-c-input__input--is-valid': 'valid' === status,
                            'airwpsync-c-input__input--is-invalid': 'invalid' === status,
                        }), type: type, disabled: disabled, value: value, placeholder: placeholder, ...props }), instructions ? jsx("p", { className: `airwpsync-c-input__instructions`, children: instructions }) : null, errorMessage ? jsx("p", { className: `airwpsync-c-input__error-message`, children: errorMessage }) : null] })] }));
};

export { InputType, Input as default };
//# sourceMappingURL=input.js.map
