import { jsxs, jsx } from 'react/jsx-runtime';

const SvgCircleCross = ({ title, titleId, ...props }) => (jsxs("svg", { xmlns: "http://www.w3.org/2000/svg", width: 18, height: 17, viewBox: "0 0 18 17", fill: "none", "aria-labelledby": titleId, ...props, children: [title ? jsx("title", { id: titleId, children: title }) : null, jsx("path", { fill: "currentColor", d: "M9 .167A8.326 8.326 0 0 0 .667 8.5 8.326 8.326 0 0 0 9 16.834 8.326 8.326 0 0 0 17.333 8.5 8.326 8.326 0 0 0 9 .167m4.167 11.325-1.175 1.175L9 9.675l-2.992 2.992-1.175-1.175L7.825 8.5 4.833 5.51l1.175-1.175L9 7.325l2.992-2.991 1.175 1.175L10.175 8.5z" })] }));

export { SvgCircleCross as default };
//# sourceMappingURL=CircleCross.tsx.js.map
