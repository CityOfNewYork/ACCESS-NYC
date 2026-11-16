import { jsxs, jsx } from 'react/jsx-runtime';
import { useId } from 'react';

const SvgArrowRight = ({ title, titleId, ...props }) => {
    const clipPathId = useId();
    return jsxs("svg", { xmlns: "http://www.w3.org/2000/svg", width: 15, height: 17, viewBox: "0 0 15 17", fill: "none", "aria-labelledby": titleId, ...props, children: [title ? jsx("title", { id: titleId, children: title }) : null, jsxs("g", { fill: "currentColor", clipPath: `url(#${clipPathId})`, children: [jsx("path", { d: "M8.023 14.977a1 1 0 0 1-.709-1.707l4.307-4.293-4.306-4.293a1 1 0 0 1 0-1.414 1.005 1.005 0 0 1 1.418 0l5.015 5a1 1 0 0 1 0 1.414l-5.015 5a1 1 0 0 1-.71.293" }), jsx("path", { d: "M10.618 9.978H1.003a1 1 0 1 1 0-2h9.615l1.003 1z", opacity: 0.4 })] }), jsx("defs", { children: jsx("clipPath", { id: clipPathId, children: jsx("path", { fill: "#fff", d: "M0 .978h14.041v16H0z" }) }) })] });
};

export { SvgArrowRight as default };
//# sourceMappingURL=ArrowRight.tsx.js.map
