import { jsxs, jsx } from 'react/jsx-runtime';
import { useId } from 'react';

const SvgCircleInfo = ({ title, titleId, ...props }) => {
    const clipPathId = useId();
    return jsxs("svg", { xmlns: "http://www.w3.org/2000/svg", width: 20, height: 20, viewBox: "0 0 20 20", fill: "none", "aria-labelledby": titleId, ...props, children: [title ? jsx("title", { id: titleId, children: title }) : null, jsxs("g", { fill: "currentColor", clipPath: `url(#${clipPathId})`, children: [jsx("path", { d: "M10 7.5A1.25 1.25 0 1 0 10 5a1.25 1.25 0 0 0 0 2.5m1.563 5.625h-.626V9.687A.94.94 0 0 0 10 8.75H8.75a.94.94 0 0 0-.938.938.94.94 0 0 0 .938.937h.313v2.5h-.626a.94.94 0 0 0-.937.938.94.94 0 0 0 .938.937h3.124a.938.938 0 0 0 0-1.875Z" }), jsx("path", { d: "M10 0C4.477 0 0 4.477 0 10s4.477 10 10 10 10-4.477 10-10S15.523 0 10 0m0 5a1.25 1.25 0 1 1 0 2.5A1.25 1.25 0 0 1 10 5m1.563 10H8.437a.938.938 0 0 1 0-1.875h.626v-2.5H8.75a.938.938 0 0 1 0-1.875H10c.518 0 .938.42.938.938v3.437h.624a.938.938 0 0 1 0 1.875Z", opacity: 0.4 })] }), jsx("defs", { children: jsx("clipPath", { id: clipPathId, children: jsx("path", { fill: "#fff", d: "M0 0h20v20H0z" }) }) })] });
};

export { SvgCircleInfo as default };
//# sourceMappingURL=CircleInfo.tsx.js.map
