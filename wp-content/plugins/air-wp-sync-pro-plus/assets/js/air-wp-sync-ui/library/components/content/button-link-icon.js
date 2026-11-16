import { jsx } from 'react/jsx-runtime';
import ButtonLink from './button-link.js';
import { buttonIconPropsModifier } from '../base/ButtonIconBase.js';
import '../../ButtonBase.js';
import '../graphics/icons/ArrowLeft.tsx.js';
import 'react';
import '../graphics/icons/ArrowRight.tsx.js';
import '../graphics/icons/Cross.tsx.js';
import '../graphics/icons/Download.tsx.js';
import '../graphics/icons/NewConnection.tsx.js';
import '../graphics/icons/OpenExternal.tsx.js';
import '../graphics/icons/Sync.tsx.js';
import '../graphics/icons/Verify.tsx.js';
import '../graphics/circle-loading-animation.js';
import '../graphics/icons/CircleLoading.tsx.js';

/**
 * ButtonLinkIcon. <br />
 * Display a link (`<a>`) with an icon defined by the `icon` property.
 *
 * @param {React.ReactNode} children Children
 * @param {( 'arrow-right' | 'arrow-left' | 'open-external' | 'new-connection' | 'verify' | 'circle-loading' | 'cross' | 'sync' | 'download' )} icon Button's icon.
 * @param {('before' | 'after')} iconPos Icon position.
 * @param {object} ...props Other HTML button props.
 *
 * @constructor
 */
function ButtonLinkIcon({ children, icon, iconPos, ...props }) {
    const buttonIconProps = buttonIconPropsModifier({ children, icon, iconPos, ...props });
    return jsx(ButtonLink, { ...buttonIconProps });
}

export { ButtonLinkIcon as default };
//# sourceMappingURL=button-link-icon.js.map
