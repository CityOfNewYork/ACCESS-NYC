import { jsxs, jsx } from 'react/jsx-runtime';

const SvgOpenExternal = ({ title, titleId, ...props }) => (jsxs("svg", { xmlns: "http://www.w3.org/2000/svg", width: 14, height: 16, viewBox: "0 0 14 16", fill: "none", "aria-labelledby": titleId, ...props, children: [title ? jsx("title", { id: titleId, children: title }) : null, jsx("path", { fill: "currentColor", d: "M8 2a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v4a.999.999 0 1 1-2 0V4.416l-5.294 5.29a.998.998 0 1 1-1.412-1.412L10.584 3H9a1 1 0 0 1-1-1" }), jsx("path", { fill: "currentColor", d: "M0 4a2 2 0 0 1 2-2h3a1 1 0 1 1 0 2H2v9h9v-3a.999.999 0 1 1 2 0v3c0 1.103-.897 2-2 2H2a2 2 0 0 1-2-2z", opacity: 0.4 })] }));

export { SvgOpenExternal as default };
//# sourceMappingURL=OpenExternal.tsx.js.map
