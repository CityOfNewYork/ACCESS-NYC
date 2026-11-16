import { jsx, jsxs } from 'react/jsx-runtime';
import 'react';
import SvgCircleChecked from '../graphics/icons/CircleChecked.tsx.js';
import SvgCircleCross from '../graphics/icons/CircleCross.tsx.js';
import SvgCircleTrash from '../graphics/icons/CircleTrash.tsx.js';
import SvgGrab from '../graphics/icons/Grab.tsx.js';
import CircleLoadingAnimation from '../graphics/circle-loading-animation.js';
import Input from './input.js';
import '../graphics/icons/CircleLoading.tsx.js';
import 'classnames';
import '../graphics/icons/Info.tsx.js';

var MappingRowTemplateStatus;
(function (MappingRowTemplateStatus) {
    MappingRowTemplateStatus["Idle"] = "idle";
    MappingRowTemplateStatus["Valid"] = "valid";
    MappingRowTemplateStatus["Invalid"] = "invalid";
    MappingRowTemplateStatus["Loading"] = "loading";
})(MappingRowTemplateStatus || (MappingRowTemplateStatus = {}));

function MappingRowTemplate({ texts, status, index, expectedAirtableFieldName, wordPressFieldId, mappingManager, errorMessage, readOnly = false, sortable = false, ...props }) {
    const wordPressField = mappingManager.getWordPressFieldById(wordPressFieldId);
    const removeMappingRowHandler = () => {
        mappingManager.removeMappingRow(index);
    };
    let statusIndicator = null;
    switch (status) {
        case MappingRowTemplateStatus.Valid:
            statusIndicator = jsx(SvgCircleChecked, { style: { color: 'var(--airwpsync--color--green-500)' } });
            break;
        case MappingRowTemplateStatus.Loading:
            statusIndicator = jsx(CircleLoadingAnimation, { style: { color: 'var(--airwpsync--color--primary)' } });
            break;
        case MappingRowTemplateStatus.Invalid:
            statusIndicator = jsx(SvgCircleCross, { style: { color: 'var(--airwpsync--color--error)' } });
            break;
    }
    if (!statusIndicator && sortable) {
        statusIndicator = jsxs("div", { className: "airwpsync-c-mapping-row-template__btn-sort airwpsync-u-reset-btn", children: [jsx("span", { className: "screen-reader-text", children: texts.sort }), jsx(SvgGrab, {})] });
    }
    const { className, ...otherProps } = props;
    return jsxs("tr", { className: `airwpsync-c-mapping-row-template ${className ?? ''}`, ...otherProps, children: [jsx("td", { className: "airwpsync-c-mapping-row-template__wordpress-field-col", children: jsxs("div", { className: "airwpsync-c-mapping-row-template__wordpress-field", children: [statusIndicator, wordPressField.label] }) }), jsx("td", { className: "airwpsync-c-mapping-row-template__assigned-to", children: texts.assigned_to }), jsx("td", { className: "airwpsync-c-mapping-row-template__airtable-field-col", children: jsx(Input, { label: '', value: expectedAirtableFieldName, readOnly: true, status: status === 'invalid' ? status : 'idle', errorMessage: errorMessage }) }), jsx("td", { className: "airwpsync-c-mapping-row-template__flex", children: !readOnly ?
                    jsxs("button", { type: "button", className: "airwpsync-c-mapping-row-template__remove-btn airwpsync-u-reset-btn", onClick: removeMappingRowHandler, children: [jsx("span", { className: "screen-reader-text", children: texts.remove }), jsx(SvgCircleTrash, {})] })
                    : null })] });
}

export { MappingRowTemplate as default };
//# sourceMappingURL=mapping-row-template.js.map
