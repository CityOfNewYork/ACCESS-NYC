import { jsxs, jsx } from 'react/jsx-runtime';
import { useId } from 'react';

const SvgVerify = ({ title, titleId, ...props }) => {
    const clipPathId = useId();
    return jsxs("svg", { xmlns: "http://www.w3.org/2000/svg", width: 14, height: 16, viewBox: "0 0 14 16", fill: "none", "aria-labelledby": titleId, ...props, children: [title ? jsx("title", { id: titleId, children: title }) : null, jsxs("g", { fill: "#EFF9F7", clipPath: `url(#${clipPathId})`, children: [jsx("path", { d: "M13.706 5.294a1 1 0 0 1 0 1.412l-8 8a1 1 0 0 1-1.412 0l-4.001-4a1 1 0 0 1 1.414-1.412l3.265 3.29 7.322-7.29a1 1 0 0 1 1.412 0" }), jsx("path", { d: "M10.706 1.293a1 1 0 0 1 0 1.414l-5 5a1 1 0 0 1-1.412 0l-2.501-2.5a.998.998 0 1 1 1.413-1.413l1.766 1.79 4.322-4.291a1 1 0 0 1 1.412 0", opacity: 0.4 })] }), jsx("defs", { children: jsx("clipPath", { id: clipPathId, children: jsx("path", { fill: "#fff", d: "M0 0h14v16H0z" }) }) })] });
};

export { SvgVerify as default };
//# sourceMappingURL=Verify.tsx.js.map
