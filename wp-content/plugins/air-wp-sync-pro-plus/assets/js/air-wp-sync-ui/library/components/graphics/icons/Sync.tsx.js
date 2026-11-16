import { jsxs, jsx } from 'react/jsx-runtime';
import { useId } from 'react';

const SvgSync = ({ title, titleId, ...props }) => {
    const clipPathId = useId();
    return jsxs("svg", { xmlns: "http://www.w3.org/2000/svg", width: 16, height: 16, viewBox: "0 0 14 14", fill: "none", "aria-labelledby": titleId, ...props, children: [title ? jsx("title", { id: titleId, children: title }) : null, jsxs("g", { fill: "currentColor", clipPath: 'url(#' + clipPathId + ')', children: [jsx("path", { d: "M12.302 1.093 10.976 2.42A5.9 5.9 0 0 0 7 .875a5.91 5.91 0 0 0-5.644 4.107.874.874 0 1 0 1.665.539A4.169 4.169 0 0 1 9.74 3.659L8.507 4.867c-.438.46-.118 1.242.527 1.258h4.017a.524.524 0 0 0 .511-.522V1.615a.738.738 0 0 0-1.26-.522" }), jsx("path", { d: "M12.644 9.018c-.793 2.458-3.063 4.107-5.669 4.107A5.9 5.9 0 0 1 3 11.58l-1.327 1.327c-.44.464-1.235.136-1.235-.545V8.397c0-.284.227-.514.51-.522h4.017a.737.737 0 0 1 .503 1.258L4.26 10.34a4.168 4.168 0 0 0 6.719-1.859.874.874 0 0 1 1.102-.563c.459.123.713.64.563 1.099", opacity: 0.4 })] }), jsx("defs", { children: jsx("clipPath", { id: clipPathId, children: jsx("path", { fill: "#fff", d: "M0 0h14v14H0z" }) }) })] });
};

export { SvgSync as default };
//# sourceMappingURL=Sync.tsx.js.map
