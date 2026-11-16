import { jsx } from 'react/jsx-runtime';

/**
 * ContentWrapper.
 *
 * Wrapper for content base components. Limit the width.
 *
 * @param {React.ReactNode} children Children
 * @return {Element}
 */
const ContentWrapper = ({ children }) => {
    return (jsx("div", { className: `airwpsync-c-content-wrapper `, children: children }));
};

export { ContentWrapper as default };
//# sourceMappingURL=content-wrapper.js.map
