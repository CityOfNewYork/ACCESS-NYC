import { jsxs, jsx } from 'react/jsx-runtime';

const SvgDropdownIndicator = ({ title, titleId, ...props }) => (jsxs("svg", { xmlns: "http://www.w3.org/2000/svg", width: 12, height: 8, viewBox: "0 0 12 8", fill: "none", "aria-labelledby": titleId, ...props, children: [title ? jsx("title", { id: titleId, children: title }) : null, jsx("path", { fill: "currentColor", d: "M10.59.59 6 5.17 1.41.59 0 2l6 6 6-6z" })] }));

export { SvgDropdownIndicator as default };
//# sourceMappingURL=DropdownIndicator.tsx.js.map
