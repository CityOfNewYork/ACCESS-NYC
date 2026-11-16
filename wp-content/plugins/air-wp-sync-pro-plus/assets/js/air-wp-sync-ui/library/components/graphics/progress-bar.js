import { jsxs, jsx } from 'react/jsx-runtime';
import { useId } from 'react';

const ProgressBar = ({ children, ratio, color = 'yellow-500', bgColor = 'yellow-100' }) => {
    const id = useId();
    return jsxs("div", { className: "airwpsync-c-progress-bar", style: {
            '--airwpsync--progress-bar-bgcolor': `var(--airwpsync--color--${bgColor})`,
            '--airwpsync--progress-bar-color': `var(--airwpsync--color--${color})`
        }, children: [children(id), jsx("div", { className: "airwpsync-c-progress-bar__bar", role: "meter", "aria-valuenow": ratio * 100, "aria-valuemin": "0", "aria-valuemax": "100", "aria-labelledby": id, children: jsx("div", { className: "airwpsync-c-progress-bar__inner", style: { '--airwpsync--progress-bar-percentage': (ratio * 100) + '%' } }) })] });
};

export { ProgressBar as default };
//# sourceMappingURL=progress-bar.js.map
