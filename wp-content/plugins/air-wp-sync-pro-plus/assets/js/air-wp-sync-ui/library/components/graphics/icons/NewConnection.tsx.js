import { jsxs, jsx } from 'react/jsx-runtime';

const SvgNewConnection = ({ title, titleId, ...props }) => (jsxs("svg", { xmlns: "http://www.w3.org/2000/svg", width: 24, height: 26, viewBox: "0 0 24 26", fill: "none", "aria-labelledby": titleId, ...props, children: [title ? jsx("title", { id: titleId, children: title }) : null, jsx("path", { fill: "currentColor", d: "M24 17.333c0 3.313-2.688 6-6 6-3.313 0-6-2.688-6-6s2.688-6 6-6c3.313 0 6 2.688 6 6m-6.667-2.704v2h-2c-.366 0-.666.337-.666.666 0 .405.3.667.666.667h2v2c0 .404.3.667.667.667a.646.646 0 0 0 .667-.667v-2h2a.646.646 0 0 0 .666-.666c0-.33-.3-.667-.666-.667h-2v-2c0-.33-.3-.667-.667-.667s-.667.338-.667.667" }), jsx("path", { fill: "currentColor", d: "M5.333 7.333H2.667v-4a1.333 1.333 0 0 1 2.666 0zm8 0h-2.666v-4a1.333 1.333 0 1 1 2.666 0zM0 10c0-.738.597-1.333 1.333-1.333h13.334a1.332 1.332 0 0 1 1.304 1.616c-3.063.846-5.304 3.705-5.304 7.05 0 .475.046.938.129 1.388-.458.212-.95.375-1.463.479v4.133H6.667V19.2c-3.043-.654-5.334-3.308-5.334-6.533v-1.334A1.333 1.333 0 0 1 0 10", opacity: 0.4 })] }));

export { SvgNewConnection as default };
//# sourceMappingURL=NewConnection.tsx.js.map
