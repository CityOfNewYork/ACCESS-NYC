import { jsx } from 'react/jsx-runtime';

/**
 * @typedef {{ size:  (1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | 9 | 10 | 11 | 12), children: {React.ReactNode} }} ColumnProps
 */
/**
 *
 * @param {ColumnProps[]} columns Columns
 * @param {object} ...props Other props for <div> HTML element.
 * @constructor
 */
const Columns = ({ columns, ...props }) => {
    return (jsx("div", { ...props, className: `airwpsync-c-columns `, children: columns.map(({ size, children }, index) => (jsx("div", { className: `airwpsync-c-columns__column airwpsync-c-columns__column--size-${size}`, children: children }, index))) }));
};

export { Columns as default };
//# sourceMappingURL=columns.js.map
