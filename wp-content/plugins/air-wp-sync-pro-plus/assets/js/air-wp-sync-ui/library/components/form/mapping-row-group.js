import { jsxs, jsx } from 'react/jsx-runtime';
import { forwardRef } from 'react';

const MappingRowTemplate = forwardRef(({ label, children, ...props }, ref) => {
    return jsxs("tbody", { className: "airwpsync-c-mapping-row-group", ...props, ref: ref, children: [jsx("tr", { children: jsx("th", { className: "airwpsync-c-mapping-row-group__heading", colSpan: 4, children: label }) }), children] });
});

export { MappingRowTemplate as default };
//# sourceMappingURL=mapping-row-group.js.map
