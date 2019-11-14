// Compiled template. Do not edit.
window.JST = window.JST || {};
window.JST["screener/template-member-summary"] = function(obj){
var __t,__p='',__j=Array.prototype.join,print=function(){__p+=__j.call(arguments,'');};
with(obj||{}){
__p+='';
 members.forEach(function(member, index) { 
__p+='\n<li>\n  ';
 if (index === 0 && !member.isHoh) { 
__p+='\n  <span class="c-member-list__item">{{ __("You", "accessnyc-screener")|trim|escape("js") }}</span>\n  ';
 } 
__p+='\n  <span class="c-member-list__item">'+
((__t=( member.relation ))==null?'':__t)+
'</span>\n  <span class="c-member-list__item">'+
((__t=( member.age ))==null?'':__t)+
'</span>\n  ';
 if (index === 0 && member.isHoh) { 
__p+='\n  <span class="c-member-list__item">{{ __("Head of household", "accessnyc-screener")|trim|escape("js") }}</span>\n  ';
 } 
__p+='\n</li>\n';
 }); 
__p+='';
}
return __p;
}