import { jsxs, jsx } from 'react/jsx-runtime';
import { useId } from 'react';

const SvgTriangleExclamation = ({ title, titleId, ...props }) => {
    const clipPathId = useId();
    return jsxs("svg", { xmlns: "http://www.w3.org/2000/svg", width: 20, height: 20, fill: "none", "aria-labelledby": titleId, ...props, children: [title ? jsx("title", { id: titleId, children: title }) : null, jsxs("g", { fill: "currentColor", clipPath: `url(#${clipPathId})`, children: [jsx("path", { d: "M10 12.5c.52 0 .938-.418.938-.937v-5c0-.518-.42-.938-.903-.938-.482 0-.972.422-.972.938v5c0 .519.421.937.937.937m0 1.293a1.228 1.228 0 1 0 0 2.457 1.228 1.228 0 0 0 0-2.457" }), jsx("path", { d: "M19.778 16.29 11.445 2.07c-.638-1.093-2.247-1.093-2.89 0L.228 16.29c-.64 1.089.158 2.46 1.443 2.46h16.664c1.28 0 2.08-1.367 1.444-2.46M9.063 6.562a.938.938 0 0 1 1.875 0v5c0 .517-.42.937-.903.937-.482 0-.972-.418-.972-.937zM10 16.25a1.228 1.228 0 1 1 0-2.457 1.228 1.228 0 0 1 0 2.457", opacity: 0.4 })] }), jsx("defs", { children: jsx("clipPath", { id: clipPathId, children: jsx("path", { fill: "#fff", d: "M0 0h20v20H0z" }) }) })] });
};

export { SvgTriangleExclamation as default };
//# sourceMappingURL=TriangleExclamation.tsx.js.map
