(function() {

    'use strict';

    var queryString = {};

    function strictUriEncode(str) {
        return encodeURIComponent(str).replace(/[!'()*]/g, function (c) {
            return '%' + c.charCodeAt(0).toString(16).toUpperCase();
        });
    }

    queryString.parse = function(str) {
        if (typeof str !== 'string') {
            return {};
        }

        str = str.trim().replace(/^(\?|#|&)/, '');

        if (!str) {
            return {};
        }

        return str.split('&').reduce(function(ret, param) {
            var parts = param.replace(/\+/g, ' ').split('=');
            var key = parts.shift();
            var val = parts.length > 0 ? parts.join('=') : undefined;

            key = decodeURIComponent(key);
            val = val === undefined ? null : decodeURIComponent(val);

            if (!ret.hasOwnProperty(key)) {
                ret[key] = val;
            } else if (Array.isArray(ret[key])) {
                ret[key].push(val);
            } else {
                ret[key] = [ret[key], val];
            }

            return ret;
        }, {});
    }

    queryString.stringify = function(obj) {
        return obj ? Object.keys(obj).sort().map(function(key) {
            var val = obj[key];

            if (val === undefined) {
                return '';
            }

            if (val === null) {
                return key;
            }

            if (Array.isArray(val)) {
                return val.sort().map(function(val2) {
                    return strictUriEncode(key) + '=' + strictUriEncode(val2);
                }).join('&');
            }

            return strictUriEncode(key) + '=' + strictUriEncode(val);
        }).filter(function(x) {
            return x.length > 0;
        }).join('&') : '';
    }

    self.queryString = queryString;

})();