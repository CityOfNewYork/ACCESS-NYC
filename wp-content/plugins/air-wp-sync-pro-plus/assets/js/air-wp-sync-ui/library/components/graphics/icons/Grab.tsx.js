import { jsxs, jsx } from 'react/jsx-runtime';

const SvgGrab = ({ title, titleId, ...props }) => (jsxs("svg", { xmlns: "http://www.w3.org/2000/svg", width: 16, height: 6, fill: "none", "aria-labelledby": titleId, ...props, children: [title ? jsx("title", { id: titleId, children: title }) : null, jsx("path", { fill: "currentColor", d: "M0 6V4h16v2zm0-4V0h16v2z" })] }));

export { SvgGrab as default };
//# sourceMappingURL=Grab.tsx.js.map
