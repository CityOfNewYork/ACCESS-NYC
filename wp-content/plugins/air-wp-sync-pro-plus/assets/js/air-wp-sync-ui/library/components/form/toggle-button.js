import { jsxs, jsx } from 'react/jsx-runtime';
import { useId } from 'react';
import SvgCircleChecked from '../graphics/icons/CircleChecked.tsx.js';
import classnames from 'classnames';

/**
 * ToggleButton.
 * Display a toggle button (html checkbox). Accepts other props from React `<input>` component.
 *
 * @param {boolean} checked Checked?
 * @param {boolean} disabled Disabled checkbox prop.
 * @param {object} ...props Other HTML checkbox props.
 *
 * @constructor
 */
const ToggleButton = ({ label, checked = false, disabled = false, ...props }) => {
    const id = useId();
    return jsxs("div", { className: classnames({
            'airwpsync-c-toggle-button': true,
            'airwpsync-c-toggle-button--checked': checked,
            'airwpsync-c-toggle-button--unchecked': !checked,
            'airwpsync-c-toggle-button--disabled': disabled,
        }), children: [jsx("input", { type: "checkbox", id: id, className: `airwpsync-c-toggle-button__input screen-reader-text `, disabled: disabled, checked: checked, ...props }), jsxs("label", { htmlFor: id, className: `airwpsync-c-toggle-button__label-wrapper `, children: [jsxs("div", { className: `airwpsync-c-toggle-button__checked-indicator `, children: [jsx("div", { className: "airwpsync-c-toggle-button__checked-indicator__pusher" }), jsx(SvgCircleChecked, { className: "airwpsync-c-toggle-button__checked-indicator__icon" })] }), jsx("div", { className: `airwpsync-c-toggle-button__label `, children: label })] })] });
};

export { ToggleButton as default };
//# sourceMappingURL=toggle-button.js.map
