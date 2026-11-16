import { jsxs, jsx } from 'react/jsx-runtime';
import { useId, useState } from 'react';
import SvgCircleQuestion from '../graphics/icons/CircleQuestion.tsx.js';
import classnames from 'classnames';

/**
 * @typedef {function(id: string):React.ReactNode} ChildrenFunction
 */
/**
 * Tooltip. <br />
 * Display a tooltip on focus/hover.
 *
 * @param {ChildrenFunction} children Children
 * @param {React.ReactNode} message Tooltip's message.
 * @param {('left' | 'right')} iconPosition Position of the icon (left or right)
 * @param {('top' | 'right')} tooltipPosition Position of the tooltip (left or right)
 * @constructor
 */
const Tooltip = ({ message, children, iconPosition = 'right', tooltipPosition = 'top' }) => {
    const id = useId();
    const [isActive, setIsActive] = useState(false);
    return jsxs("span", { className: classnames({
            'airwpsync-c-tooltip': true,
            'airwpsync-c-tooltip--is-active': isActive,
        }), onFocus: () => setIsActive(true), onBlur: () => setIsActive(false), onMouseOver: () => setIsActive(true), onMouseOut: () => setIsActive(false), children: [children(id), jsxs("span", { className: classnames({
                    'airwpsync-c-tooltip__icon': true,
                    'airwpsync-c-tooltip__icon--icon-position-right': 'right' === iconPosition,
                    'airwpsync-c-tooltip__icon--icon-position-left': 'left' === iconPosition,
                }), children: [jsx(SvgCircleQuestion, {}), jsx("span", { className: classnames({
                            'airwpsync-c-tooltip__message': true,
                            'airwpsync-c-tooltip__message--position-top': 'top' === tooltipPosition,
                            'airwpsync-c-tooltip__message--position-right': 'right' === tooltipPosition,
                        }), role: "tooltip", id: id, "aria-hidden": !isActive, children: message })] })] });
};

export { Tooltip as default };
//# sourceMappingURL=tooltip.js.map
