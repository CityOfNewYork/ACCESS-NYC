import { jsx } from 'react/jsx-runtime';
import { b as buttonPropsModifier } from '../../ButtonBase.js';

/**
 * Button.
 * Display a button with a default classname. Accepts other props from React `<button>` component.
 *
 * @param {React.ReactNode} children Children
 * @param {string} classModifier CSS class modifier (e.g "airwpsync-c-button--icon")
 * @param {boolean} disabled Disabled button prop.
 * @param {('primary' | 'secondary' | 'link')} buttonType Button style type.
 * @param {underlined} Underlined? (default true if buttonType is "link")
 * @param {object} props Other HTML button props.
 *
 * @constructor
 */
const Button = ({ children, classModifier, underlined, fontSize, disabled = false, buttonType = 'primary', ...props }) => {
    const buttonBaseProps = buttonPropsModifier({ children, classModifier, underlined, buttonType, fontSize });
    return (jsx("button", { ...buttonBaseProps, ...props, disabled: disabled, children: children }));
};

export { Button as default };
//# sourceMappingURL=button.js.map
