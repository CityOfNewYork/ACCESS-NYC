// Compiled template. Do not edit.
window.JST = window.JST || {};
window.JST["screener/template-expense"] = function(obj){
var __t,__p='',__j=Array.prototype.join,print=function(){__p+=__j.call(arguments,'');};
with(obj||{}){
__p+='<div id="screener-person-'+
((__t=( personIndex ))==null?'':__t)+
'-expense-'+
((__t=( matrixIndex ))==null?'':__t)+
'" class="js-matrix-item">\n  <hr class="divider screen-tablet:divider-large border-grey-light">\n\n  <div class="c-question">\n    ';
 if (matrixIndex === 0) { 
__p+='\n    <label class="c-question__label" for="person-'+
((__t=( personIndex ))==null?'':__t)+
'-expenses-'+
((__t=( matrixIndex ))==null?'':__t)+
'-type">\n      ';
 if (personIndex === 0) { 
__p+='\n      {{ __("What type of expense have you had most recently?", "accessnyc-screener")|trim|escape("js") }}\n      ';
 } else { 
__p+='\n      {{ __("What type of expense have they had most recently?", "accessnyc-screener")|trim|escape("js") }}\n      ';
 } 
__p+='\n    </label>\n    <p>{{ __("Answer the best you can. You will be able to include additional types of expenses. The more you include, the more accurate your results will be.", "accessnyc-screener")|trim|escape("js") }}</p>\n    ';
 } else { 
__p+='\n    <label class="c-question__label d-inline-block m-bottom" for="person-'+
((__t=( personIndex ))==null?'':__t)+
'-expenses-';
 print(matrixIndex + 1) 
__p+='-type">\n      ';
 if (personIndex === 0) { 
__p+='\n      {{ __("If you have another type of expense, select it below.", "accessnyc-screener")|trim|escape("js") }}\n      ';
 } else { 
__p+='\n      {{ __("If they have another type of expense, select it below.", "accessnyc-screener")|trim|escape("js") }}\n      ';
 } 
__p+='\n    </label>\n    ';
 } 
__p+='\n\n    <div class="c-question__container">\n      <select class="select js-matrix-select js-add-section js-screener-toggle"\n          id="person-'+
((__t=( personIndex ))==null?'':__t)+
'-expenses-'+
((__t=( matrixIndex ))==null?'':__t)+
'"\n          name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].expenses['+
((__t=( matrixIndex ))==null?'':__t)+
'].type"\n          data-toggles="#screener-person-'+
((__t=( personIndex ))==null?'':__t)+
'-expense-'+
((__t=( matrixIndex ))==null?'':__t)+
'-details"\n          data-renders="#screener-person-'+
((__t=( personIndex ))==null?'':__t)+
'-expense-';
 print(matrixIndex + 1) 
__p+='"\n          data-matrix="expense"\n          data-matrix-index="';
 print(matrixIndex + 1) 
__p+='"\n          data-person-index="'+
((__t=( personIndex ))==null?'':__t)+
'">\n        <option value="">{{ __("Click to add an expense type", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="ChildCare">{{ __("Child Care", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="ChildSupport">{{ __("Child Support (Paid)", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="DependentCare">{{ __("Dependent Care", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="Rent">{{ __("Rent", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="Medical">{{ __("Medical expense", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="Heating">{{ __("Heating", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="Cooling">{{ __("Cooling", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="Mortgage">{{ __("Mortgage", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="Utilities">{{ __("Utilities", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="Telephone">{{ __("Telephone", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="InsurancePremiums">{{ __("Third Party Insurance Premiums", "accessnyc-screener")|trim|escape("js") }}</option>\n      </select>\n    </div>\n  </div>\n\n  <div id="screener-person-'+
((__t=( personIndex ))==null?'':__t)+
'-expense-'+
((__t=( matrixIndex ))==null?'':__t)+
'-details" class="hidden">\n    <div class="c-question">\n      <label class="c-question__label" for="person-'+
((__t=( personIndex ))==null?'':__t)+
'-expenses-'+
((__t=( matrixIndex ))==null?'':__t)+
'-amount">\n        {{ __("How much is this type of expense:", "accessnyc-screener")|trim|escape("js") }}\n        <span data-js="transaction-label"></span>?\n      </label>\n      <div class="c-question__container">\n        <div class="input-currency-usd">\n          <input id="person-'+
((__t=( personIndex ))==null?'':__t)+
'-expenses-'+
((__t=( matrixIndex ))==null?'':__t)+
'-amount" name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].expenses['+
((__t=( matrixIndex ))==null?'':__t)+
'].amount" type="number" data-type="float" step="0.01" required>\n        </div>\n      </div>\n    </div>\n\n    <div class="c-question">\n      <label class="c-question__label d-inline-block m-bottom" for="person-'+
((__t=( personIndex ))==null?'':__t)+
'-expenses-'+
((__t=( matrixIndex ))==null?'':__t)+
'-frequency">\n        ';
 if (personIndex === 0) { 
__p+='\n        {{ __("How often do you have this expense:", "accessnyc-screener")|trim|escape("js") }}\n        ';
 } else { 
__p+='\n        {{ __("How often do they have this expense:", "accessnyc-screener")|trim|escape("js") }}\n        ';
 } 
__p+='\n        <span data-js="transaction-label"></span>?\n      </label>\n      <div class="c-question__container">\n        <select class="select" id="person-'+
((__t=( personIndex ))==null?'':__t)+
'-expenses-'+
((__t=( matrixIndex ))==null?'':__t)+
'-frequency" name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].expenses['+
((__t=( matrixIndex ))==null?'':__t)+
'].frequency" required>\n          <option value="">{{ __("Click to add an expense frequency", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="weekly">{{ __("Every week", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="biweekly">{{ __("Every 2 weeks", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="monthly">{{ __("Monthly", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="semimonthly">{{ __("Twice a month", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="yearly">{{ __("Every year", "accessnyc-screener")|trim|escape("js") }}</option>\n        </select>\n      </div>\n    </div>\n\n  </div>\n</div>\n';
}
return __p;
}