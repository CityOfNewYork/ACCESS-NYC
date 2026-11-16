import { jsxs, jsx } from 'react/jsx-runtime';

const SvgLoading = ({ title, titleId, ...props }) => (jsxs("svg", { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 30 6", "aria-labelledby": titleId, ...props, children: [title ? jsx("title", { id: titleId, children: title }) : null, jsx("path", { className: "airwpsync-i-loading__shape-1", d: "m3,0h0c1.66,0,3,1.34,3,3h0c0,1.66-1.34,3-3,3h0c-1.66,0-3-1.34-3-3h0C0,1.34,1.34,0,3,0Z", fill: "currentColor" }), jsx("path", { className: "airwpsync-i-loading__shape-2", d: "m27,0h0c1.66,0,3,1.34,3,3h0c0,1.66-1.34,3-3,3h0c-1.66,0-3-1.34-3-3h0c0-1.66,1.34-3,3-3Z", fill: "currentColor" })] }));

export { SvgLoading as default };
//# sourceMappingURL=Loading.tsx.js.map
