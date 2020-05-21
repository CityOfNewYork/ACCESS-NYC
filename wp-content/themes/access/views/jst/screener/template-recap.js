// Compiled template. Do not edit.
window.JST = window.JST || {};
window.JST["screener/template-recap"] = function(obj){
var __t,__p='',__j=Array.prototype.join,print=function(){__p+=__j.call(arguments,'');};
with(obj||{}){
__p+='<h2 class="recap-confirmation">{{ __("Is all of your information correct?", "accessnyc-screener")|trim|escape("js") }}</h2>\n\n<div class="text-normal bg-white p-2 screen-tablet:p-4">\n  <div>\n    <h3 class="type-h4 text-blue-dark">\n      {{ __("Looking for help with", "accessnyc-screener")|trim|escape("js") }}\n      <a role="button" href="#step-1" class="block screen-tablet:inline screen-tablet:px-2 font-normal">{{ __("Edit", "accessnyc-screener")|trim|escape("js") }}</a>\n    </h3>\n\n    <div class="layout-gutter screen-tablet:layout-columns-gutter mb-4">\n      ';
 if (categories.length === 0) { 
__p+='\n      <p>{{ __("No categories selected.", "accessnyc-screener")|trim|escape("js") }}</p>\n      ';
 } 
__p+='\n\n      ';
 categories.forEach(function(category) { 
__p+='\n      {% if language_code == "en" %}\n      ';
 var slug = category.slug 
__p+='\n      {% else %}\n      ';
 var slug = category.slug.substring(0, category.slug.lastIndexOf("-{{ language_code }}")); 
__p+='\n      {% endif %}\n\n      <article class="c-card p-0 items-center">\n        <div class="c-card__icon">\n          <svg class="icon icon-6 text-blue-bright fill-blue-light" aria-hidden="true">\n            <use xlink:href="#icon-'+
((__t=( slug ))==null?'':__t)+
'-v2"></use>\n          </svg>\n        </div>\n\n        <div class="c-card__body">'+
((__t=( category.label ))==null?'':__t)+
'</div>\n      </article>\n      ';
 }); 
__p+='\n    </div>\n\n  </div>\n\n  <hr class="divider screen-tablet:divider-large border-grey-light">\n\n  <div>\n    <h3 class="type-h4 text-blue-dark">\n      {{ __("Your household:", "accessnyc-screener")|trim|escape("js") }}\n      '+
((__t=( members.length ))==null?'':__t)+
'\n      ';
 if (members.length === 1) { 
__p+='\n      {{ __("person", "accessnyc-screener")|trim|escape("js") }}\n      ';
 } else { 
__p+='\n      {{ __("people", "accessnyc-screener")|trim|escape("js") }}\n      ';
 } 
__p+='\n    </h3>\n\n    <ul class="c-member-list ">{% apply spaceless %}\n      ';
 members.forEach(function(member, index) { 
__p+='\n      <li class="pb-4">\n        ';
 if (index === 0 && !member.isHoh) { 
__p+='\n        <span class="c-member-list__item">{{ __("You", "accessnyc-screener")|trim|escape("js") }}</span>\n        ';
 } 
__p+='\n        <span class="c-member-list__item">'+
((__t=( member.relation ))==null?'':__t)+
'</span>\n        <span class="c-member-list__item">'+
((__t=( member.age ))==null?'':__t)+
'</span>\n        ';
 if (index === 0 && member.isHoh) { 
__p+='\n        <span class="c-member-list__item">{{ __("Head of household", "accessnyc-screener")|trim|escape("js") }}</span>\n        ';
 } 
__p+='\n\n        <span class="c-member-list__item inline">\n          <a id="recap-edit-person" role="button" data-person="'+
((__t=( index ))==null?'':__t)+
'" class="js-edit-person screen-tablet:px-2 font-normal cursor-pointer">{{ __("Edit", "accessnyc-screener")|trim|escape("js") }}</a>';
 if (index !== 0 && !member.isHoh) { 
__p+='<a role="button" data-person="'+
((__t=( index ))==null?'':__t)+
'" class="js-remove-person px-2 font-normal">{{ __("Remove", "accessnyc-screener")|trim|escape("js") }}</a>';
 } 
__p+='\n        </span>\n\n        ';
 if (member.conditions.length) { 
__p+='\n        <div class="screen-mobile:flex font-normal leading-large pb-1">\n          <div class="flex-none py-1 screen-mobile:p-0" style="width:7rem">{{ __("Conditions:", "accessnyc-screener")|trim|escape("js") }}</div>\n          <ul class="list-inline-semicolon">\n            ';
 member.conditions.forEach(function(condition) { 
__p+='\n            <li>'+
((__t=( condition ))==null?'':__t)+
'</li>\n            ';
 }); 
__p+='\n          </ul>\n        </div>\n        ';
 } 
__p+='\n\n        ';
 if (member.benefits.length) { 
__p+='\n        <div class="screen-mobile:flex font-normal leading-large pb-1">\n          <div class="flex-none py-1 screen-mobile:p-0" style="width:7rem">{{ __("Benefits:", "accessnyc-screener")|trim|escape("js") }}</div>\n          <ul class="list-inline-semicolon">\n            ';
 member.benefits.forEach(function(benefit) { 
__p+='\n            <li>'+
((__t=( benefit ))==null?'':__t)+
'</li>\n            ';
 }); 
__p+='\n          </ul>\n        </div>\n        ';
 } 
__p+='\n\n        ';
 if (member.incomes.length) { 
__p+='\n        <div class="screen-mobile:flex font-normal leading-large pb-1">\n          <div class="flex-none py-1 screen-mobile:p-0" style="width:7rem">{{ __("Income:", "accessnyc-screener")|trim|escape("js") }}</div>\n          <ul class="list-inline-semicolon">\n            ';
 member.incomes.forEach(function(income) { 
__p+='\n            <li>\n              <span class="list-inline-comma">\n                <span>'+
((__t=( income.amount ))==null?'':__t)+
'</span>\n                <span>'+
((__t=( income.type ))==null?'':__t)+
'</span>\n                <span>'+
((__t=( income.frequency ))==null?'':__t)+
'</span>\n              </span>\n            </li>\n            ';
 }); 
__p+='\n          </ul>\n        </div>\n        ';
 } 
__p+='\n\n        ';
 if (member.expenses.length) { 
__p+='\n        <div class="screen-mobile:flex font-normal leading-large">\n          <div class="flex-none py-1 screen-mobile:p-0" style="width:7rem">{{ __("Expenses:", "accessnyc-screener")|trim|escape("js") }}</div>\n          <ul class="list-inline-semicolon">\n            ';
 member.expenses.forEach(function(expense) { 
__p+='\n            <li>\n              <span class="list-inline-comma">\n                <span>'+
((__t=( expense.amount ))==null?'':__t)+
'</span>\n                <span>'+
((__t=( expense.type ))==null?'':__t)+
'</span>\n                <span>'+
((__t=( expense.frequency ))==null?'':__t)+
'</span>\n              </span>\n            </li>\n            ';
 }); 
__p+='\n          </ul>\n        </div>\n        ';
 } 
__p+='\n      </li>\n      ';
 }) 
__p+='\n    {% endapply %}</ul>\n  </div>\n\n  <hr class="divider screen-tablet:divider-large border-grey-light">\n\n  <div>\n    <h3 class="type-h4 text-blue-dark">\n      {{ __("Household resources:", "accessnyc-screener")|trim|escape("js") }} <span class="force-ltr">'+
((__t=( household.assets ))==null?'':__t)+
'</span>\n      <a role="button" href="#step-10" class="block screen-tablet:inline screen-tablet:px-2 font-normal">{{ __("Edit", "accessnyc-screener")|trim|escape("js") }}</a>\n    </h3>\n\n    <p>{{ __("This is cash on hand; checking or savings accounts; stocks, bonds or mutual funds.", "accessnyc-screener")|trim|escape("js") }}</p>\n  </div>\n\n  <hr class="divider screen-tablet:divider-large border-grey-light">\n\n  <div>\n    <h3 class="type-h4 text-blue-dark">\n      {{ __("Housing", "accessnyc-screener")|trim|escape("js") }}\n      <a role="button" href="#step-10" class="block screen-tablet:inline screen-tablet:px-2 font-normal">{{ __("Edit", "accessnyc-screener")|trim|escape("js") }}</a>\n    </h3>\n\n    <ul class="list-inline-semicolon">{% apply spaceless %}\n      ';
 household.types.forEach(function(type) { 
__p+='\n      <li>\n        <div class="list-inline-comma">\n          <span>'+
((__t=( type.label ))==null?'':__t)+
'</span>\n          ';
 if (type.slug === "Renting") { 
__p+='\n            ';
 if (household.rentalType) { 
__p+='\n            <span class="recap-detail-info-item">\n              '+
((__t=( household.rentalType ))==null?'':__t)+
'\n            </span>\n            ';
 } 
__p+='\n\n            <span class="recap-detail-info-item">\n              ';
 if (household.renters.length === 1) { 
__p+='\n                ';
 if (household.renters[0].slug === "Self") { 
__p+='\n                {{ __("You are on the lease", "accessnyc-screener")|trim|escape("js") }}\n                ';
 } else { 
__p+='\n                '+
((__t=( household.renters[0].label ))==null?'':__t)+
' {{ __("is on the lease", "accessnyc-screener")|trim|escape("js") }}\n                ';
 } 
__p+='\n              ';
 } else { 
__p+='\n                <span class="list-inline-comma">\n                  ';
 household.renters.forEach(function(renter) { 
__p+='\n                  <span>'+
((__t=( renter.label ))==null?'':__t)+
'</span>\n                  ';
 }); 
__p+='\n                </span>\n                {{ __("are on the lease", "accessnyc-screener")|trim|escape("js") }}\n              ';
 } 
__p+='\n            </span>\n          ';
 } else if (type.slug === "Owner") { 
__p+='\n            <span>\n              ';
 if (household.owners.length === 1) { 
__p+='\n                ';
 if (household.owners[0].slug === "Self") { 
__p+='\n                {{ __("You are the owner", "accessnyc-screener")|trim|escape("js") }}\n                ';
 } else { 
__p+='\n                '+
((__t=( household.owners[0].label ))==null?'':__t)+
' {{ __("is the owner", "accessnyc-screener")|trim|escape("js") }}\n                ';
 } 
__p+='\n              ';
 } else { 
__p+='\n                <span class="list-inline-comma">\n                  ';
 household.owners.forEach(function(owner) { 
__p+='\n                  <span>'+
((__t=( owner.label ))==null?'':__t)+
'</span>\n                  ';
 }); 
__p+='\n                </span>\n                {{ __("are the owners", "accessnyc-screener")|trim|escape("js") }}\n              ';
 } 
__p+='\n            </span>\n          ';
 } 
__p+='\n        </div>\n      </li>\n      ';
 }); 
__p+='\n    {% endapply %}</ul>\n  </div>\n\n  <hr class="divider screen-tablet:divider-large border-grey-light">\n\n  <div>\n    <h3 class="type-h4 text-blue-dark">\n      {{ __("Your zip code:", "accessnyc-screener")|trim|escape("js") }} '+
((__t=( household.zip ))==null?'':__t)+
'\n      <a role="button" href="#step-3" class="block screen-tablet:inline screen-tablet:px-2 font-normal">{{ __("Edit", "accessnyc-screener")|trim|escape("js") }}</a>\n    </h3>\n  </div>\n</div>\n';
}
return __p;
}