// Compiled template. Do not edit.
window.JST = window.JST || {};
window.JST["locations/template-pagination"] = function(obj){
var __t,__p='',__j=Array.prototype.join,print=function(){__p+=__j.call(arguments,'');};
with(obj||{}){
__p+='<div class="p-2 flex items-center justify-end">\n  <div class="text-small px-2 text-end">\n    {{ __("Showing", "accessnyc-locations")|trim|json_encode() }} '+
((__t=( displayedCount ))==null?'':__t)+
' {{ __("of", "accessnyc-locations")|trim|escape("js") }} '+
((__t=( totalCount ))==null?'':__t)+
'\n  </div>\n  ';
 if (displayedCount < totalCount) { 
__p+='\n  <div>\n    <button class="btn btn-secondary btn-next btn-small js-map-more">{{ __("Show more", "accessnyc-locations")|trim|escape("js") }}</button>\n  </div>\n  ';
 } 
__p+='\n</div>';
}
return __p;
}