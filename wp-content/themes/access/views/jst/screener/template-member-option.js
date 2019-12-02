// Compiled template. Do not edit.
window.JST = window.JST || {};
window.JST["screener/template-member-option"] = function(obj){
var __t,__p='',__j=Array.prototype.join,print=function(){__p+=__j.call(arguments,'');};
with(obj||{}){
__p+='';
 people.forEach(function(person, i) { 
__p+='\n<label class="checkbox">\n  <input type="checkbox"\n    name="Person['+
((__t=( i ))==null?'':__t)+
'].'+
((__t=( attribute ))==null?'':__t)+
'"\n    value="1"\n    data-type="boolean"\n    ';
 if ((attribute === 'livingRentalOnLease' && person.leasee) || (attribute === 'livingOwnerOnDeed' && person.owner)) { print('checked') } 
__p+='>\n  <span class="checkbox__label">\n    <span class="list-inline-comma">\n      <span>'+
((__t=( person.relation ))==null?'':__t)+
'</span>\n      <span>'+
((__t=( person.age ))==null?'':__t)+
'</span>\n    </span>\n  </span>\n</label>\n';
 }); 
__p+='';
}
return __p;
}