import { jsx, jsxs } from 'react/jsx-runtime';
import classnames from 'classnames';
import 'react';
import SvgStepChecked from '../graphics/icons/StepChecked.tsx.js';

/**
 * StepsIndex. <br />
 * Display an index of steps.
 *
 * @param {StepIndex[]} steps Steps.
 * @param {string} currentStepKey Current step key.
 * @constructor
 */
const StepsIndex = ({ steps, currentStepKey, stepsDone = [], ...props }) => {
    return jsx("ol", { className: "airwpsync-c-steps-index", ...props, children: steps.map(({ stepKey, title }) => {
            const stepProps = {
                key: stepKey,
                className: classnames({
                    'airwpsync-c-steps-index__step': true,
                    'airwpsync-c-steps-index__step--current': currentStepKey === stepKey,
                    'airwpsync-c-steps-index__step--done': stepsDone?.indexOf(stepKey) > -1
                })
            };
            if (currentStepKey === stepKey) {
                // @ts-ignore
                stepProps['aria-current'] = 'step';
            }
            return jsxs("li", { ...stepProps, children: [jsx(SvgStepChecked, { className: "airwpsync-c-steps-index__step__icon" }), jsx("div", { className: "airwpsync-c-steps-index__step__title", children: title })] });
        }) });
};

export { StepsIndex as default };
//# sourceMappingURL=steps-index.js.map
