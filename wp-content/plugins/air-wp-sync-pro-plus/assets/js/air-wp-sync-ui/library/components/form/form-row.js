import { jsx } from 'react/jsx-runtime';

/**
 * FormRow. <br />
 * Manages spacing between form elements within the form row.
 *
 * @param {React.ReactNode} children Children
 *
 * @constructor
 */
const FormRow = ({ children, className, ...props }) => {
    return (jsx("div", { className: `airwpsync-c-form-row ${className ?? ''}`, ...props, children: children }));
};

export { FormRow as default };
//# sourceMappingURL=form-row.js.map
