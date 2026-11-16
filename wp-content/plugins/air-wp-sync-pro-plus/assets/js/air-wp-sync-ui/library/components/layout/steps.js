import { jsx } from 'react/jsx-runtime';
import { CSSTransition } from 'react-transition-group';
import { useRef } from 'react';

/**
 * @typedef {{ key: string, children: {React.ReactNode} }} StepProps
 */
/**
 * Steps.
 * Manage step transition.
 *
 * @param {string} currentStepKey Current step key.
 * @param {StepProps[]} steps Steps.
 *
 * @constructor
 */
const Steps = ({ currentStepKey, steps }) => {
    return (jsx("div", { className: `airwpsync-c-steps `, children: steps.map((stepProps) => {
            return jsx(Step, { ...{ ...stepProps, currentStepKey, key: stepProps.stepKey } });
        }) }));
};
function Step({ currentStepKey, stepKey, children }) {
    const stepsRef = useRef(null);
    return jsx(CSSTransition, { nodeRef: stepsRef, timeout: 300, classNames: 'item', in: stepKey === currentStepKey, appear: true, children: jsx("div", { ref: stepsRef, className: "airwpsync-c-steps__step", children: children }) }, stepKey);
}
function getNextKey(stepKeys, currentStepKey, loop = true) {
    const currentIndex = stepKeys.indexOf(currentStepKey);
    let nextIndex = currentIndex >= stepKeys.length - 1 && loop ? 0 : currentIndex + 1;
    if (nextIndex > stepKeys.length - 1) {
        nextIndex = false;
    }
    return nextIndex !== false ? stepKeys[nextIndex] : false;
}
function getPrevKey(stepKeys, currentStepKey, loop = true) {
    const currentIndex = stepKeys.indexOf(currentStepKey);
    let prevIndex = currentIndex === 0 && loop ? stepKeys.length - 1 : currentIndex - 1;
    if (prevIndex < 0) {
        prevIndex = false;
    }
    return prevIndex !== false ? stepKeys[prevIndex] : false;
}

export { Step, Steps as default, getNextKey, getPrevKey };
//# sourceMappingURL=steps.js.map
