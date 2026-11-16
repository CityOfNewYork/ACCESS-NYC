import { jsxs, jsx } from 'react/jsx-runtime';
import { useId } from 'react';

const SvgCircleLoading = ({ title, titleId, ...props }) => {
    const clipPathId = useId();
    return jsxs("svg", { xmlns: "http://www.w3.org/2000/svg", width: 20, height: 20, viewBox: "0 0 20 20", fill: "none", "aria-labelledby": titleId, ...props, children: [title ? jsx("title", { id: titleId, children: title }) : null, jsxs("g", { fill: "currentColor", clipPath: `url(#${clipPathId})`, children: [jsx("circle", { cx: 10, cy: 2.375, r: 1.875, fillOpacity: 0.1 }), jsx("circle", { cx: 10, cy: 18.625, r: 1.875, fillOpacity: 0.65, opacity: 0.45 }), jsx("circle", { cx: 1.875, cy: 10.5, r: 1.875, fillOpacity: 0.85 }), jsx("circle", { cx: 18.125, cy: 10.5, r: 1.875, fillOpacity: 0.35 }), jsx("circle", { cx: 16.25, cy: 4.875, r: 1.875, fillOpacity: 0.25 }), jsx("circle", { cx: 16.25, cy: 16.125, r: 1.875, fillOpacity: 0.45 }), jsx("circle", { cx: 3.75, cy: 16.125, r: 1.875, fillOpacity: 0.75 }), jsx("circle", { cx: 3.75, cy: 4.875, r: 1.875 })] }), jsx("defs", { children: jsx("clipPath", { id: clipPathId, children: jsx("path", { fill: "#fff", d: "M0 .5h20v20H0z" }) }) })] });
};

export { SvgCircleLoading as default };
//# sourceMappingURL=CircleLoading.tsx.js.map
