import { React, PropTypes } from '@gravityforms/libraries';

const { forwardRef } = React;

/**
 * @module NoSuppressions
 * @description The NoSuppressions svg.
 *
 * @since 1.6.0
 *
 * @param {object}      props        Component props.
 * @param {number}      props.height The height of the logo.
 * @param {string}      props.title  The title of the logo.
 * @param {number}      props.width  The width of the logo.
 * @param {object|null} ref          Ref to the component.
 *
 * @return {JSX.Element} The svg component.
 * @example
 * import NoSuppressions from '../no-suppressions';
 *
 * return (
 *    <NoSuppressions height={ 91 } width={ 320 } />
 * );
 *
 */
const NoSuppressions = forwardRef( ( {
	height = 91,
	title = '',
	width = 390,
}, ref ) => {
	return (
		<svg xmlns="http://www.w3.org/2000/svg" width={ width } height={ height } viewBox="0 0 315 91" fill="none" ref={ ref }>
			{ title !== '' && <title>{ title }</title> }
			<mask
				id="b"
				width={ 332 }
				height={ 137 }
				x={ - 5 }
				y={ - 19 }
				maskUnits="userSpaceOnUse"
				style={ {
					maskType: "alpha",
				} }
			>
				<ellipse
					cx={ 160.875 }
					cy={ 49.753 }
					fill="url(#a)"
					rx={ 165.625 }
					ry={ 68.125 }
				/>
			</mask>
			<g mask="url(#b)">
				<path
					fill="#B5C1CB"
					d="M151.004 45.354 161 50.352l9.996-4.998A2.5 2.5 0 0 0 168.5 43h-15a2.5 2.5 0 0 0-2.496 2.354Z"
				/>
				<path
					fill="#B5C1CB"
					d="m171 48.147-10 5-10-5V55.5a2.5 2.5 0 0 0 2.5 2.5h15a2.5 2.5 0 0 0 2.5-2.5v-7.353ZM191.004 45.354 201 50.352l9.996-4.998A2.5 2.5 0 0 0 208.5 43h-15a2.5 2.5 0 0 0-2.496 2.354Z"
				/>
				<path
					fill="#B5C1CB"
					d="m211 48.147-10 5-10-5V55.5a2.5 2.5 0 0 0 2.5 2.5h15a2.5 2.5 0 0 0 2.5-2.5v-7.353ZM231.004 45.354 241 50.352l9.996-4.998A2.5 2.5 0 0 0 248.5 43h-15a2.5 2.5 0 0 0-2.496 2.354Z"
				/>
				<path
					fill="#B5C1CB"
					d="m251 48.147-10 5-10-5V55.5a2.5 2.5 0 0 0 2.5 2.5h15a2.5 2.5 0 0 0 2.5-2.5v-7.353ZM271.004 45.354 281 50.352l9.996-4.998A2.5 2.5 0 0 0 288.5 43h-15a2.5 2.5 0 0 0-2.496 2.354Z"
				/>
				<path
					fill="#B5C1CB"
					d="m291 48.147-10 5-10-5V55.5a2.5 2.5 0 0 0 2.5 2.5h15a2.5 2.5 0 0 0 2.5-2.5v-7.353ZM31.004 45.354 41 50.352l9.996-4.998A2.5 2.5 0 0 0 48.5 43h-15a2.5 2.5 0 0 0-2.496 2.354Z"
				/>
				<path
					fill="#B5C1CB"
					d="m51 48.147-10 5-10-5V55.5a2.5 2.5 0 0 0 2.5 2.5h15a2.5 2.5 0 0 0 2.5-2.5v-7.353ZM71.004 45.354 81 50.352l9.996-4.998A2.5 2.5 0 0 0 88.5 43h-15a2.5 2.5 0 0 0-2.496 2.354Z"
				/>
				<path
					fill="#B5C1CB"
					d="m91 48.147-10 5-10-5V55.5a2.5 2.5 0 0 0 2.5 2.5h15a2.5 2.5 0 0 0 2.5-2.5v-7.353ZM111.004 45.354 121 50.352l9.996-4.998A2.5 2.5 0 0 0 128.5 43h-15a2.5 2.5 0 0 0-2.496 2.354Z"
				/>
				<path
					fill="#B5C1CB"
					d="m131 48.147-10 5-10-5V55.5a2.5 2.5 0 0 0 2.5 2.5h15a2.5 2.5 0 0 0 2.5-2.5v-7.353Z"
				/>
				<path
					fill="#DDE3E7"
					fillRule="evenodd"
					d="M181 50c0 11.046-8.954 20-20 20s-20-8.954-20-20 8.954-20 20-20 20 8.954 20 20Zm-11.308 12.227A14.934 14.934 0 0 1 161 65c-8.284 0-15-6.716-15-15 0-3.24 1.027-6.24 2.773-8.691l20.919 20.918Zm3.535-3.536-20.918-20.918A14.932 14.932 0 0 1 161 35c8.284 0 15 6.716 15 15 0 3.24-1.027 6.24-2.773 8.691Z"
					clipRule="evenodd"
				/>
			</g>
			<defs>
				<radialGradient
					id="a"
					cx={ 0 }
					cy={ 0 }
					r={ 1 }
					gradientTransform="matrix(0 65 -158.028 0 160.875 53.128)"
					gradientUnits="userSpaceOnUse"
				>
					<stop stopColor="#fff" />
					<stop offset={ 1 } stopColor="#fff" stopOpacity={ 0 } />
				</radialGradient>
			</defs>
		</svg>
	);
} );

NoSuppressions.propTypes = {
	height: PropTypes.number,
	title: PropTypes.string,
	width: PropTypes.number,
};

NoSuppressions.displayName = 'NoSuppressions';

export default NoSuppressions;
