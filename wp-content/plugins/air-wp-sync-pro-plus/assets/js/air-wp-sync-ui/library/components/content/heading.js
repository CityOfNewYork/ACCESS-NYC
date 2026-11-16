import { jsx } from 'react/jsx-runtime';

/**
 * Heading.
 *
 * `semanticLevel` follows `level` parameter if not defined.
 *
 * @param {React.ReactNode} children Children
 * @param {(1 | 2 | 3 | 4 | 5 | 6)} semanticLevel Semantic level (h1, h2, ...)
 * @param {(1 | 2 | 3 | 4 | 5 | 6)} level Level (visual)
 * @param {string} emoji Emoji that will be displayed in front of the title.
 * @return {Element}
 */
const Heading = ({ children, semanticLevel, emoji, level = 2 }) => {
    if (!semanticLevel) {
        semanticLevel = level;
    }
    let Tag = `h${semanticLevel}`;
    return (jsx(Tag, { className: `airwpsync-c-heading airwpsync-c-heading--lvl-${level} `, "data-emoji": emoji, children: children }));
};

export { Heading as default };
//# sourceMappingURL=heading.js.map
