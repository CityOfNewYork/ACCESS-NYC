import { jsx } from 'react/jsx-runtime';

/**
 *
 * @param {React.ReactNode} children Children
 * @param {(10 | 16 | 24)} gap Gap size
 * @constructor
 */
const ButtonGroup = ({ children, gap = 10 }) => {
    return (jsx("div", { className: `airwpsync-c-button-group airwpsync-c-button-group--gap-${gap}`, children: children }));
};

export { ButtonGroup as default };
//# sourceMappingURL=button-group.js.map
