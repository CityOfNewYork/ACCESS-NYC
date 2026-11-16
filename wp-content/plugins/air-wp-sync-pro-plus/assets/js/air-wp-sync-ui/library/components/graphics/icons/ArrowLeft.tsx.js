import { jsxs, jsx } from 'react/jsx-runtime';
import { useId } from 'react';

const SvgArrowLeft = ({ title, titleId, ...props }) => {
    const clipPathId = useId();
    return jsxs("svg", { xmlns: "http://www.w3.org/2000/svg", width: 15, height: 16, viewBox: "0 0 15 16", fill: "none", "aria-labelledby": titleId, ...props, children: [title ? jsx("title", { id: titleId, children: title }) : null, jsxs("g", { fill: "currentColor", clipPath: `url(#${clipPathId})`, children: [jsx("path", { d: "M6.018 2a1 1 0 0 1 .709 1.707L2.42 8l4.306 4.294a1 1 0 0 1 0 1.414c-.392.39-1.026.39-1.418 0l-5.015-5a1 1 0 0 1 0-1.414l5.015-5A1 1 0 0 1 6.018 2" }), jsx("path", { d: "M3.423 7h9.615a1 1 0 1 1 0 2H3.423L2.42 8z", opacity: 0.4 })] }), jsx("defs", { children: jsx("clipPath", { id: clipPathId, children: jsx("path", { fill: "#fff", d: "M14.041 16H0V0h14.041z" }) }) })] });
};

export { SvgArrowLeft as default };
//# sourceMappingURL=ArrowLeft.tsx.js.map
