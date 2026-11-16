import { jsxs, jsx } from 'react/jsx-runtime';
import { useId } from 'react';
import classnames from 'classnames';
import SvgCircleChecked from '../graphics/icons/CircleChecked.tsx.js';

const DetailedChoices = ({ legend, choices, selected, onChange }) => {
    const fieldSetId = useId();
    return jsxs("fieldset", { className: "airwpsync-c-detailed-choices", children: [jsx("legend", { className: "screen-reader-text", children: legend }), jsx("div", { className: "airwpsync-c-detailed-choices__list", children: choices.map((detailedChoice) => {
                    return jsxs("label", { className: classnames({
                            'airwpsync-c-detailed-choices__choice': true,
                            'is-checked': selected === detailedChoice.value
                        }), htmlFor: `${fieldSetId}_${detailedChoice.value}`, children: [jsx("div", { className: "airwpsync-c-detailed-choices__checked-icon", children: jsx(SvgCircleChecked, {}) }), jsx("input", { className: "airwpsync-c-detailed-choices__input screen-reader-text", type: "radio", id: `${fieldSetId}_${detailedChoice.value}`, value: detailedChoice.value, checked: selected === detailedChoice.value, onChange: (e) => {
                                    onChange(e.target.value);
                                } }), jsx("div", { className: "airwpsync-c-detailed-choices__choice__label", children: detailedChoice.label }), jsx("div", { className: "airwpsync-c-detailed-choices__choice__description", children: detailedChoice.description })] }, detailedChoice.value);
                }) })] });
};

export { DetailedChoices as default };
//# sourceMappingURL=detailed-choices.js.map
