import { jsxs, jsx } from 'react/jsx-runtime';

const SvgStepChecked = ({ title, titleId, ...props }) => (jsxs("svg", { xmlns: "http://www.w3.org/2000/svg", width: 42, height: 42, viewBox: "0 0 42 42", fill: "none", "aria-labelledby": titleId, ...props, children: [title ? jsx("title", { id: titleId, children: title }) : null, jsx("circle", { cx: 21, cy: 21, r: 19, fill: "currentColor", stroke: "currentColor", strokeWidth: 2 }), jsx("path", { fill: "#fff", d: "M19.578 25.142 29.22 15.5l1.28 1.28-10.922 10.922-5.079-5.076 1.28-1.28z" })] }));

export { SvgStepChecked as default };
//# sourceMappingURL=StepChecked.tsx.js.map
