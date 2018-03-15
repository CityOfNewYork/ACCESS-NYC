import uglify from 'rollup-plugin-uglify';
import multiEntry from 'rollup-plugin-multi-entry';

export default {
    input: [
        'assets/js/src/event-manager.js',
        'assets/js/src/front.js',
        'assets/js/src/front-facets.js'
    ],
    output: {
        file: 'assets/js/dist/front.min.js',
        name: 'FWP_Build',
        format: 'iife'
    },
    watch: {
        include: 'assets/js/src/**'
    },
    plugins: [
        multiEntry(),
        uglify()
    ]
}
