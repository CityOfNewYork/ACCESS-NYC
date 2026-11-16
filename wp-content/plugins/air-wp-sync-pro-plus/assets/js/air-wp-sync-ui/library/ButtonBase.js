/**
 * Apply button base props.
 *
 * @param {React.ReactNode} children Children
 * @param {string} classModifier CSS class modifier (e.g "airwpsync-c-button--icon")
 * @param {('primary' | 'secondary' | 'link')} buttonType Button style type.
 * @param {underlined} Underlined? (default true if buttonType is "link")
 * @param {('xs' | 'sm' | 'base' | 'lg' | 'xl')} fontSize Font size.
 * @param {object} ...props Other HTML tag props.
 *
 * @constructor
 */
function buttonPropsModifier({ children, classModifier, buttonType, underlined, fontSize = 'base', ...props }) {
    let className = `airwpsync-b-button-base airwpsync-c-button airwpsync-b-button-base--${buttonType} airwpsync-t-font-size--${fontSize} `;
    if (typeof underlined === 'undefined') {
        underlined = 'link' === buttonType;
    }
    if (underlined) {
        className += 'airwpsync-b-button-base--underlined ';
    }
    className += (classModifier ?? '');
    return {
        className,
        children,
        ...props
    };
}

export { buttonPropsModifier as b };
//# sourceMappingURL=ButtonBase.js.map
