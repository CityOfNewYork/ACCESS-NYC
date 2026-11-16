import { jsx } from 'react/jsx-runtime';

/**
 * Spacer.
 *
 * @param {(10 | 16 | 24 | 32 | 64)} size Space size
 * @constructor
 */
function Spacer({ size }) {
    return jsx("div", { className: `airwpsync-c-spacer airwpsync-c-spacer--size-${size}` });
}

export { Spacer as default };
//# sourceMappingURL=spacer.js.map
