import { jsxs, jsx } from 'react/jsx-runtime';
import { useId } from 'react';

const SvgCircleQuestion = ({ title, titleId, ...props }) => {
    const clipPathId = useId();
    return jsxs("svg", { xmlns: "http://www.w3.org/2000/svg", width: 17, height: 16, viewBox: "0 0 17 16", fill: "none", "aria-labelledby": titleId, ...props, children: [title ? jsx("title", { id: titleId, children: title }) : null, jsxs("g", { fill: "currentColor", clipPath: `url(#${clipPathId})`, children: [jsx("path", { d: "M8.5 10.5c-.563 0-1 .438-1 1s.41 1 1 1c.534 0 1-.41 1-1 0-.563-.466-1-1-1M9.534 4H7.937C6.691 4 5.75 4.94 5.75 6.188c0 .378.344.75.75.75a.76.76 0 0 0 .75-.75c0-.375.284-.688.66-.688h1.596c.403 0 .744.313.744.688 0 .25-.125.44-.344.565l-1.781 1.09a.77.77 0 0 0-.375.657V9c0 .406.344.75.75.75A.76.76 0 0 0 9.25 9v-.063l1.41-.874a2.23 2.23 0 0 0 1.062-1.876C11.75 4.941 10.753 4 9.534 4" }), jsx("path", { d: "M8.5 0a8 8 0 0 0-8 8 8 8 0 0 0 8 8 8 8 0 0 0 8-8 8 8 0 0 0-8-8m0 12.5c-.563 0-1-.438-1-1s.41-1 1-1c.534 0 1 .438 1 1s-.466 1-1 1m2.16-4.438-1.41.876V9a.76.76 0 0 1-.75.75.76.76 0 0 1-.75-.75v-.5c0-.25.125-.5.375-.656L9.906 6.78a.67.67 0 0 0 .344-.593c0-.375-.34-.688-.716-.688H7.937a.676.676 0 0 0-.687.688.76.76 0 0 1-.75.75.76.76 0 0 1-.75-.75C5.75 4.968 6.719 4 7.91 4h1.596c1.275 0 2.244.969 2.244 2.188 0 .75-.406 1.468-1.09 1.875Z", opacity: 0.4 })] }), jsx("defs", { children: jsx("clipPath", { id: clipPathId, children: jsx("path", { fill: "#fff", d: "M.5 0h16v16H.5z" }) }) })] });
};

export { SvgCircleQuestion as default };
//# sourceMappingURL=CircleQuestion.tsx.js.map
