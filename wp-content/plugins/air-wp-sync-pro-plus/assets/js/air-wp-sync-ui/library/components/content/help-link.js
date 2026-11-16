import { jsxs, jsx } from 'react/jsx-runtime';
import 'react';
import SvgCircleQuestion from '../graphics/icons/CircleQuestion.tsx.js';

/**
 * HelpLink. <br />
 * Display a link to an external help page with an optional preview.
 *
 * @param {string} text Link text
 * @param {string} href Link href
 * @param {{ src: string, alt?: string }} preview Preview config (img src and alt properties)
 * @param {('vertical' | 'horizontal')} direction Stack direction
 * @constructor
 */
const HelpLink = ({ text, href, preview, direction = 'horizontal' }) => {
    return (jsxs("a", { className: `airwpsync-c-help-link airwpsync-c-help-link--${direction}`, href: href, target: "_blank", rel: "noreferrer", children: [jsxs("span", { className: `airwpsync-c-help-link__inner `, children: [jsx("span", { className: "airwpsync-c-help-link__icon", children: jsx(SvgCircleQuestion, {}) }), jsx("span", { className: "airwpsync-c-help-link__text", children: text })] }), preview ? jsx("img", { className: "airwpsync-c-help-link__preview", src: preview.src, alt: preview.alt ?? '' }) : null] }));
};

export { HelpLink as default };
//# sourceMappingURL=help-link.js.map
