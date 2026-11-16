import { jsx } from 'react/jsx-runtime';
import { b as buttonPropsModifier } from '../../ButtonBase.js';

/**
 * ButtonLink.
 * Display a link ("<a>" tag) with a default classname. Accepts other props from React `<a>` component.
 *
 * @param {React.ReactNode} children Children
 * @param {string} classModifier CSS class modifier (e.g "airwpsync-c-button--icon")
 * @param {('primary' | 'secondary' | 'link')} buttonType Button style type.
 * @param {underlined} Underlined? (default true if buttonType is "link")
 * @param {object} props Other HTML button props.
 *
 * @constructor
 */
const ButtonLink = ({ children, classModifier, underlined, fontSize, buttonType = 'primary', ...props }) => {
    const buttonBaseProps = buttonPropsModifier({ children, classModifier, underlined, buttonType, fontSize });
    return (jsx("a", { ...buttonBaseProps, ...props, children: children }));
};

export { ButtonLink as default };
//# sourceMappingURL=button-link.js.map
