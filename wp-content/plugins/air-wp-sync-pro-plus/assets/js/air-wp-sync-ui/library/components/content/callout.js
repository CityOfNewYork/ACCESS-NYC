import { jsxs, jsx } from 'react/jsx-runtime';
import 'react';
import SvgCircleChecked from '../graphics/icons/CircleChecked.tsx.js';
import SvgCircleCross from '../graphics/icons/CircleCross.tsx.js';
import SvgCircleInfo from '../graphics/icons/CircleInfo.tsx.js';
import SvgTriangleExclamation from '../graphics/icons/TriangleExclamation.tsx.js';
import InlineLoading from '../graphics/inline-loading.js';
import '../graphics/icons/Loading.tsx.js';

/**
 * Callout. <br />
 * Highlight a text and display meaningful icon.
 *
 * @param {React.ReactNode} children Children
 * @param {React.ReactNode} icon An icon (React node)
 * @param {string} iconColor A color from the theme (e.g "primary-50", "black")
 * @param {string} bgColor A color from the theme (e.g "primary-50", "black")
 * @param {('xs' | 'sm' | 'base' | 'lg' | 'xl')} fontSize Font size.
 * @param {('start' | 'center' )} iconAlignment Icon alignment
 * @constructor
 */
const Callout = ({ children, icon, iconColor, bgColor, fontSize = 'base', iconAlignment = 'start' }) => {
    return (jsxs("div", { className: `airwpsync-c-callout airwpsync-c-callout--icon-align-` + iconAlignment, style: { background: `var(--airwpsync--color--${bgColor}`, }, children: [jsx("div", { className: `airwpsync-c-callout__icon `, style: { color: iconColor ? `var(--airwpsync--color--${iconColor}` : '' }, children: icon }), jsx("div", { className: `airwpsync-c-callout__content airwpsync-t-font-size--${fontSize}`, children: children })] }));
};
/**
 * Prebuilt callout.
 * Use `type` parameter to display preconfigured Callout component.
 *
 * @param {( 'neutral' | 'success' | 'warning' | 'error' | 'loading' )} type Callout type.
 * @param {React.ReactNode} children Children
 * @param {React.ReactNode} icon An icon (React node)
 * @param {('xs' | 'sm' | 'base' | 'lg' | 'xl')} fontSize Font size.
 * @constructor
 */
const PrebuiltCallout = ({ type, icon, children, fontSize = 'base', ...props }) => {
    switch (type) {
        case 'neutral':
            return jsx(Callout, { icon: icon, bgColor: 'primary-50', ...props, children: children });
        case 'info':
            icon = icon ?? jsx(SvgCircleInfo, {});
            return jsx(Callout, { icon: icon, bgColor: 'primary-50', ...props, children: children });
        case 'success':
            icon = icon ?? jsx(SvgCircleChecked, {});
            return jsx(Callout, { icon: icon, iconColor: 'green-500', bgColor: 'green-50', children: children });
        case 'warning':
            icon = icon ?? jsx(SvgTriangleExclamation, {});
            return jsx(Callout, { icon: icon, iconColor: 'yellow-500', bgColor: 'warning-bg', children: children });
        case 'error':
            icon = icon ?? jsx(SvgCircleCross, {});
            return jsx(Callout, { icon: icon, iconColor: 'error', bgColor: 'error-bg', children: children });
        case 'loading':
            icon = icon ?? jsx(InlineLoading, {});
            return jsx(Callout, { icon: icon, iconColor: 'green-500', bgColor: 'primary-50', fontSize: fontSize, ...props, children: children });
    }
    return null;
};

export { PrebuiltCallout, Callout as default };
//# sourceMappingURL=callout.js.map
