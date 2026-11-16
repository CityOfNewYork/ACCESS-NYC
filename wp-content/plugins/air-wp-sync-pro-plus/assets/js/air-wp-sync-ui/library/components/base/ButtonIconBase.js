import { jsx, jsxs, Fragment } from 'react/jsx-runtime';
import SvgArrowLeft from '../graphics/icons/ArrowLeft.tsx.js';
import SvgArrowRight from '../graphics/icons/ArrowRight.tsx.js';
import 'react';
import SvgCross from '../graphics/icons/Cross.tsx.js';
import SvgDownload from '../graphics/icons/Download.tsx.js';
import SvgNewConnection from '../graphics/icons/NewConnection.tsx.js';
import SvgOpenExternal from '../graphics/icons/OpenExternal.tsx.js';
import SvgSync from '../graphics/icons/Sync.tsx.js';
import SvgVerify from '../graphics/icons/Verify.tsx.js';
import CircleLoadingAnimation from '../graphics/circle-loading-animation.js';
import '../graphics/icons/CircleLoading.tsx.js';

/**
 * Modify props to include icon.
 *
 * @param {React.ReactNode} children Children
 * @param {( 'arrow-right' | 'arrow-left' | 'open-external' | 'new-connection' | 'verify' | 'circle-loading' | 'cross' | 'sync' | 'download' )} icon Button's icon.
 * @param {('before' | 'after')} iconPos Icon position.
 * @param {object} ...props Other HTML tag props.
 *
 * @constructor
 */
function buttonIconPropsModifier({ children, classModifier, icon, iconPos = 'after', ...props }) {
    let iconComponent = null;
    switch (icon) {
        case 'arrow-right':
            iconComponent = jsx(SvgArrowRight, { className: "airwpsync-b-button__icon" });
            break;
        case 'arrow-left':
            iconComponent = jsx(SvgArrowLeft, { className: "airwpsync-b-button__icon" });
            break;
        case 'open-external':
            iconComponent = jsx(SvgOpenExternal, { className: "airwpsync-b-button__icon" });
            break;
        case 'new-connection':
            iconComponent = jsx(SvgNewConnection, { className: "airwpsync-b-button__icon" });
            break;
        case 'verify':
            iconComponent = jsx(SvgVerify, { className: "airwpsync-b-button__icon" });
            break;
        case 'circle-loading':
            iconComponent = jsx(CircleLoadingAnimation, { className: "airwpsync-b-button__icon" });
            break;
        case 'cross':
            iconComponent = jsx(SvgCross, { className: "airwpsync-b-button__icon" });
            break;
        case 'sync':
            iconComponent = jsx(SvgSync, { className: "airwpsync-b-button__icon" });
            break;
        case 'download':
            iconComponent = jsx(SvgDownload, { className: "airwpsync-b-button__icon" });
            break;
    }
    children = jsxs(Fragment, { children: [jsx("div", { children: children }), iconComponent] });
    classModifier = classModifier ?? '';
    return {
        classModifier: `airwpsync-b-button--icon airwpsync-b-button--icon-${iconPos} ${classModifier}`,
        children,
        ...props
    };
}

export { buttonIconPropsModifier };
//# sourceMappingURL=ButtonIconBase.js.map
