import { jsx } from 'react/jsx-runtime';

/**
 * Paragraph.
 * @param {('bold' | 'medium')} weight Strong weight.
 * @param {('xs' | 'sm' | 'base' | 'lg' | 'xl')} fontSize Font size.
 * @param string color A color from the theme (e.g "primary-50", "black")
 * @param {React.ReactNode} children Children
 * @constructor
 */
const Paragraph = ({ children, style, color = 'primary', weight = 'medium', fontSize = 'base' }) => {
    return (jsx("p", { className: `airwpsync-c-paragraph airwpsync-c-paragraph--weight-${weight} airwpsync-t-font-size--${fontSize}`, style: { ...(style ?? {}), color: `var(--airwpsync--color--${color}` }, children: children }));
};

export { Paragraph as default };
//# sourceMappingURL=paragraph.js.map
