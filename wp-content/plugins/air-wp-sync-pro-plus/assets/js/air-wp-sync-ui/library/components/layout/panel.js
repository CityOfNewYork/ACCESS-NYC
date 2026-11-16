import { jsx } from 'react/jsx-runtime';

/**
 * Panel.
 * @param {React.ReactNode} children Children
 * @constructor
 */
const Panel = ({ children }) => {
    return (jsx("div", { className: `airwpsync-c-panel `, children: children }));
};

export { Panel as default };
//# sourceMappingURL=panel.js.map
