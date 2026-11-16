import { jsx } from 'react/jsx-runtime';
import SvgCircleLoading from './icons/CircleLoading.tsx.js';
import 'react';

const CircleLoadingAnimation = (props) => (jsx("div", { className: "airwpsync-c-circle-loading", children: jsx(SvgCircleLoading, { ...props }) }));

export { CircleLoadingAnimation as default };
//# sourceMappingURL=circle-loading-animation.js.map
