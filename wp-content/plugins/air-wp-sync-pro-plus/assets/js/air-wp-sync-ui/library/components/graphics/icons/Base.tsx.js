import { jsxs, jsx } from 'react/jsx-runtime';
import { useId } from 'react';

const SvgBase = ({ title, titleId, ...props }) => {
    const clipPathId = useId();
    return jsxs("svg", { xmlns: "http://www.w3.org/2000/svg", width: 16, height: 20, viewBox: "0 0 16 20", fill: "none", "aria-labelledby": titleId, ...props, children: [title ? jsx("title", { id: titleId, children: title }) : null, jsxs("g", { fill: "currentColor", clipPath: `url(#${clipPathId})`, children: [jsx("path", { d: "M0 7.504V5.429c0 1.578 3.582 2.857 8 2.857s8-1.279 8-2.857v2.075c-.532.418-1.246.757-1.957 1.021-1.604.572-3.74.904-6.043.904s-4.44-.332-6.041-.904C1.215 8.261.533 7.922 0 7.504m0 5.714v-2.075C0 12.722 3.582 14 8 14s8-1.278 8-2.857v2.075c-.532.418-1.246.757-1.957 1.021-1.604.572-3.74.904-6.043.904s-4.44-.332-6.041-.904C1.215 13.975.533 13.636 0 13.218" }), jsx("path", { d: "M16 3.715v1.714c0 1.578-3.582 2.857-8 2.857S0 7.007 0 5.429V3.715C0 2.137 3.582.857 8 .857s8 1.28 8 2.858m-1.957 4.81c.71-.264 1.425-.603 1.957-1.021v3.64C16 12.721 12.418 14 8 14s-8-1.278-8-2.857v-3.64c.533.419 1.215.758 1.959 1.022 1.6.572 3.737.904 6.041.904s4.44-.332 6.043-.904M1.959 14.24c1.6.571 3.737.903 6.041.903s4.44-.332 6.043-.903c.71-.265 1.425-.604 1.957-1.022v3.068c0 1.579-3.582 2.857-8 2.857s-8-1.278-8-2.857v-3.068c.533.418 1.215.757 1.959 1.022", opacity: 0.4 })] }), jsx("defs", { children: jsx("clipPath", { id: clipPathId, children: jsx("path", { fill: "#fff", d: "M0 .857h16v18.286H0z" }) }) })] });
};

export { SvgBase as default };
//# sourceMappingURL=Base.tsx.js.map
