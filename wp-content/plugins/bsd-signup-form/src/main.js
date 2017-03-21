var $ = require('jquery');
var bsdSignup = require('./bsd-signup-jsapi-simple-oldie-dev.js');

$(document).ready(function() {
    $('.bsd-signup').bsdSignup({no_redirect:true})
    .on('bsd-success', function() {
        $('.bsd-signup').addClass('hide');
        $('.bsd-signup').next('p').removeClass('hide');
    });
});