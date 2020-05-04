// Compiled template. Do not edit.
window.JST = window.JST || {};
window.JST["screener/template-income"] = function(obj){
var __t,__p='',__j=Array.prototype.join,print=function(){__p+=__j.call(arguments,'');};
with(obj||{}){
__p+='<div id="screener-person-'+
((__t=( personIndex ))==null?'':__t)+
'-income-'+
((__t=( matrixIndex ))==null?'':__t)+
'" class="js-matrix-item">\n  <hr class="divider screen-tablet:divider-large border-grey-light">\n\n  <div class="c-question">\n    ';
 if (matrixIndex === 0) { 
__p+='\n    <label class="c-question__label" for="person-'+
((__t=( personIndex ))==null?'':__t)+
'-incomes-'+
((__t=( matrixIndex ))==null?'':__t)+
'-type">\n      ';
 if (personIndex === 0) { 
__p+='\n      {{ __("What type of income have you had most recently?", "accessnyc-screener")|trim|escape("js") }}\n      ';
 } else { 
__p+='\n      {{ __("What type of income have they had most recently?", "accessnyc-screener")|trim|escape("js") }}\n      ';
 } 
__p+='\n    </label>\n    <p>{{ __("Answer the best you can. You will be able to include additional types of income. The more you include, the more accurate your results will be.", "accessnyc-screener")|trim|escape("js") }}</p>\n    ';
 } else { 
__p+='\n    <label class="c-question__label" for="person-'+
((__t=( personIndex ))==null?'':__t)+
'-incomes-'+
((__t=( matrixIndex ))==null?'':__t)+
'-type">\n      ';
 if (personIndex === 0) { 
__p+='\n      {{ __("If you receive another type of income, select it below.", "accessnyc-screener")|trim|escape("js") }}\n      ';
 } else { 
__p+='\n      {{ __("If they receive another type of income, select it below.", "accessnyc-screener")|trim|escape("js") }}\n      ';
 } 
__p+='\n    </label>\n    ';
 } 
__p+='\n\n    <div class="c-question__container">\n      <select class="select js-matrix-select js-add-section js-screener-toggle"\n          id="person-'+
((__t=( personIndex ))==null?'':__t)+
'-incomes-'+
((__t=( matrixIndex ))==null?'':__t)+
'"\n          name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].incomes['+
((__t=( matrixIndex ))==null?'':__t)+
'].type"\n          data-toggles="#screener-person-'+
((__t=( personIndex ))==null?'':__t)+
'-income-'+
((__t=( matrixIndex ))==null?'':__t)+
'-details"\n          data-renders="#screener-person-'+
((__t=( personIndex ))==null?'':__t)+
'-income-';
 print(matrixIndex + 1) 
__p+='"\n          data-matrix="income"\n          data-matrix-index="';
 print(matrixIndex + 1) 
__p+='"\n          data-person-index="'+
((__t=( personIndex ))==null?'':__t)+
'">\n        <option value="" selected>{{ __("Click to add an income type", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="Wages">{{ __("wages, salaries, tips", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="SelfEmployment">{{ __("self-employment income", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="Unemployment">{{ __("unemployment benefits", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="CashAssistance">{{ __("Cash Assistance grant", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="ChildSupport">{{ __("child support (received)", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="DisabilityMedicaid">{{ __("disability-related Medicaid", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="SSI">{{ __("Supplemental Security Income (SSI)", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="SSDependent">{{ __("Social Security Dependent Benefits", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="SSDisability">{{ __("Social Security Disability Benefits", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="SSSurvivor">{{ __("Social Security Survivor’s Benefits", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="SSRetirement">{{ __("Social Security Retirement Benefits", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="NYSDisability">{{ __("New York State Disability Benefits", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="Veteran">{{ __("Veteran’s Pension or Benefits", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="Pension">{{ __("Government or Private Pension", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="DeferredComp">{{ __("Withdrawals from Deferred Compensation (IRA, Keogh, etc.)", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="WorkersComp">{{ __("Worker’s Compensation", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="Alimony">{{ __("alimony (received)", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="Boarder">{{ __("boarder or lodger", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="Gifts">{{ __("gifts/contributions (received)", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="Rental">{{ __("rental income", "accessnyc-screener")|trim|escape("js") }}</option>\n        <option value="Investment">{{ __("investment income (interest, dividends, and profit from selling stocks)", "accessnyc-screener")|trim|escape("js") }}</option>\n      </select>\n    </div>\n  </div>\n\n  <div id="screener-person-'+
((__t=( personIndex ))==null?'':__t)+
'-income-'+
((__t=( matrixIndex ))==null?'':__t)+
'-details" class="hidden">\n    <div class="c-question">\n      <label class="c-question__label d-inline-block m-bottom" for="person-'+
((__t=( personIndex ))==null?'':__t)+
'-incomes-'+
((__t=( matrixIndex ))==null?'':__t)+
'-amount">\n        ';
 if (personIndex === 0) { 
__p+='\n        {{ __("How much do you receive for this type of income:", "accessnyc-screener")|trim|escape("js") }}\n        ';
 } else { 
__p+='\n        {{ __("How much do they receive for this type of income:", "accessnyc-screener")|trim|escape("js") }}\n        ';
 } 
__p+='\n        <span data-js="transaction-label"></span>?\n      </label>\n      <div class="c-question__container">\n        <div class="input-currency-usd">\n          <input id="person-'+
((__t=( personIndex ))==null?'':__t)+
'-incomes-'+
((__t=( matrixIndex ))==null?'':__t)+
'-amount" name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].incomes['+
((__t=( matrixIndex ))==null?'':__t)+
'].amount" type="number" data-type="float" step="0.01" required>\n        </div>\n      </div>\n    </div>\n\n    <div class="c-question">\n      <label class="c-question__label d-inline-block m-bottom" for="person-'+
((__t=( personIndex ))==null?'':__t)+
'-incomes-'+
((__t=( matrixIndex ))==null?'':__t)+
'-frequency">\n        ';
 if (personIndex === 0) { 
__p+='\n        {{ __("How often do you receive this income:", "accessnyc-screener")|trim|escape("js") }}\n        ';
 } else { 
__p+='\n        {{ __("How often do they receive this income:", "accessnyc-screener")|trim|escape("js") }}\n        ';
 } 
__p+='\n        <span data-js="transaction-label"></span>?\n      </label>\n      <div class="c-question__container">\n        <select class="select" id="person-'+
((__t=( personIndex ))==null?'':__t)+
'-incomes-'+
((__t=( matrixIndex ))==null?'':__t)+
'-frequency" name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].incomes['+
((__t=( matrixIndex ))==null?'':__t)+
'].frequency" required>\n          <option value="">{{ __("Click to add an income frequency", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="weekly">{{ __("Every week", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="biweekly">{{ __("Every 2 weeks", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="monthly">{{ __("Monthly", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="semimonthly">{{ __("Twice a month", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="yearly">{{ __("Every year", "accessnyc-screener")|trim|escape("js") }}</option>\n        </select>\n      </div>\n    </div>\n  </div>\n</div>\n';
}
return __p;
}