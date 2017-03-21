jQuery(document).ready(function ($) {

    // Check to see if user set alternate class
    var target  = ( cssTarget != 'img.' ? cssTarget : 'img.style-svg' );

    jQuery(target).each(function(index){
        var $img = jQuery(this);
        var imgID = $img.attr('id');
        var imgClass = $img.attr('class');
        var imgURL = $img.attr('src');

        jQuery.get(imgURL, function(data) {

            // Get the SVG tag, ignore the rest
            var $svg = jQuery(data).find('svg');

            var svgID = $svg.attr('id');

            // Add replaced image's ID to the new SVG if necessary
            if(typeof imgID === 'undefined') {
                if(typeof svgID === 'undefined') {
                    imgID = 'svg-replaced-'+index;
                    $svg = $svg.attr('id', imgID);
                } else {
                    imgID = svgID;
                }
            } else {
                $svg = $svg.attr('id', imgID);
            }

            // Add replaced image's classes to the new SVG
            if(typeof imgClass !== 'undefined') {
                $svg = $svg.attr('class', imgClass+' replaced-svg');
            }

            // Remove any invalid XML tags as per http://validator.w3.org
            $svg = $svg.removeAttr('xmlns:a');

            // Replace image with new SVG
            $img.replaceWith($svg);

            jQuery(document).trigger('svg.loaded', [imgID]);

        }, 'xml');
    });
});