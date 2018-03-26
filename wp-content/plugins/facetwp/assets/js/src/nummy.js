/**
 * Nummy.js - A (very) lightweight number formatter
 * @link https://github.com/mgibbs189/nummy
 */
(function() {

    var nummy;

    function Nummy( value ) {
        this._value = value;
    }

    function isValidNumber( input ) {
        return ! isNaN( parseFloat( input ) ) && isFinite( input );
    }

    function toFixed( value, precision ) {
        var power = Math.pow( 10, precision );
        return ( Math.round( value * power ) / power ).toFixed( precision );
    }

    function formatNumber( value, format, opts ) {

        var negative = false,
            precision = 0,
            valueStr = '',
            wholeStr = '',
            decimalStr = '',
            abbr = '';

        if ( -1 < format.indexOf( 'a' ) ) {
            var abs = Math.abs( value );
            if ( abs >= Math.pow( 10, 12 ) ) {
                value = value / Math.pow( 10, 12 );
                abbr += 't';
            }
            else if ( abs < Math.pow( 10, 12 ) && abs >= Math.pow( 10, 9 ) ) {
                value = value / Math.pow( 10, 9 );
                abbr += 'b';
            }
            else if ( abs < Math.pow( 10, 9 ) && abs >= Math.pow( 10, 6 ) ) {
                value = value / Math.pow( 10, 6 );
                abbr += 'm';
            }
            else if ( abs < Math.pow( 10, 6 ) && abs >= Math.pow( 10, 3 ) ) {
                value = value / Math.pow( 10, 3 );
                abbr += 'k';
            }
            format = format.replace( 'a', '' );
        }

        // Check for decimals format
        if ( -1 < format.indexOf( '.' ) ) {
            precision = format.split( '.' )[1].length;
        }

        value = toFixed( value, precision );
        valueStr = value.toString();

        // Handle negative number
        if ( value < 0 ) {
            negative = true;
            value = Math.abs( value );
            valueStr = valueStr.slice( 1 );
        }

        wholeStr = valueStr.split( '.' )[0] || '';
        decimalStr = valueStr.split( '.' )[1] || '';

        // Handle decimals
        decimalStr = ( 0 < precision && '' != decimalStr ) ? '.' + decimalStr : '';

        // Use thousands separators
        if ( -1 < format.indexOf( ',' ) ) {
            wholeStr = wholeStr.replace( /(\d)(?=(\d{3})+(?!\d))/g, '$1,' );
        }

        var output = ( negative ? '-' : '' ) + wholeStr + decimalStr + abbr;

        output = output.replace(/\./g, '{d}');
        output = output.replace(/\,/g, '{t}');
        output = output.replace(/{d}/g, opts.decimal_separator);
        output = output.replace(/{t}/g, opts.thousands_separator);

        return output;
    }

    nummy = function( input ) {
        if ( ! isValidNumber( input ) ) {
            input = 0;
        }
        return new Nummy( input );
    }

    Nummy.prototype = {
        format: function( format, opts ) {
            var opts = opts || {
                'thousands_separator': ',',
                'decimal_separator': '.'
            };
            return formatNumber( this._value, format, opts );
        }
    };

    window['nummy'] = nummy;

}).call(this);
