// Compiled template. Do not edit.
window.JST = window.JST || {};
window.JST["screener/template-member"] = function(obj){
var __t,__p='',__j=Array.prototype.join,print=function(){__p+=__j.call(arguments,'');};
with(obj||{}){
__p+='<div class="c-question">\n  <label class="c-question__label" for="person-'+
((__t=( personIndex ))==null?'':__t)+
'-age">{{ __("How old are they?", "accessnyc-screener")|trim|escape("js") }}</label>\n  <div class="c-question__container">\n    <input id="person-'+
((__t=( personIndex ))==null?'':__t)+
'-age" name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].age" value="';
 if(person.age) { print(person.age) } 
__p+='" type="number" min="0" maxlength="3" pattern="[0-9]" step="1" data-type="integer" required>\n  </div>\n</div>\n\n<hr class="divider screen-tablet:divider-large border-grey-light">\n\n<div class="c-question">\n  <label class="c-question__label" for="person-'+
((__t=( personIndex ))==null?'':__t)+
'-relation">{{ __("What is this person’s relationship to the head of the household?", "accessnyc-screener")|trim|escape("js") }}</label>\n  <div class="c-question__container">\n    <select class="select" id="person'+
((__t=( personIndex ))==null?'':__t)+
'-relation" name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].headOfHouseholdRelation" required>\n      <option value="">{{ __("Click to select relationship", "accessnyc-screener")|trim|escape("js") }}</option>\n      <option value="Child"';
 if (person.headOfHouseholdRelation === "Child") { print(" selected") }
__p+='>{{ __("Child", "accessnyc-screener")|trim|escape("js") }}</option>\n      <option value="FosterChild"';
 if (person.headOfHouseholdRelation === "FosterChild") { print(" selected") }
__p+='>{{ __("Foster Child", "accessnyc-screener")|trim|escape("js") }}</option>\n      <option value="StepChild"';
 if (person.headOfHouseholdRelation === "StepChild") { print(" selected") }
__p+='>{{ __("Step-child", "accessnyc-screener")|trim|escape("js") }}</option>\n      <option value="Grandchild"';
 if (person.headOfHouseholdRelation === "Grandchild") { print(" selected") }
__p+='>{{ __("Grandchild", "accessnyc-screener")|trim|escape("js") }}</option>\n      <option value="Spouse"';
 if (person.headOfHouseholdRelation === "Spouse") { print(" selected") }
__p+='>{{ __("Spouse", "accessnyc-screener")|trim|escape("js") }}</option>\n      <option value="Parent"';
 if (person.headOfHouseholdRelation === "Parent") { print(" selected") }
__p+='>{{ __("Parent", "accessnyc-screener")|trim|escape("js") }}</option>\n      <option value="FosterParent"';
 if (person.headOfHouseholdRelation === "FosterParent") { print(" selected") }
__p+='>{{ __("Foster Parent", "accessnyc-screener")|trim|escape("js") }}</option>\n      <option value="StepParent"';
 if (person.headOfHouseholdRelation === "StepParent") { print(" selected") }
__p+='>{{ __("Step-parent", "accessnyc-screener")|trim|escape("js") }}</option>\n      <option value="Grandparent"';
 if (person.headOfHouseholdRelation === "Grandparent") { print(" selected") }
__p+='>{{ __("Grandparent", "accessnyc-screener")|trim|escape("js") }}</option>\n      <option value="SisterBrother"';
 if (person.headOfHouseholdRelation === "SisterBrother") { print(" selected") }
__p+='>{{ __("Sister/Brother", "accessnyc-screener")|trim|escape("js") }}</option>\n      <option value="StepSisterStepBrother"';
 if (person.headOfHouseholdRelation === "StepSisterStepBrother") { print(" selected") }
__p+='>{{ __("Step-sister/Step-brother", "accessnyc-screener")|trim|escape("js") }}</option>\n      <option value="BoyfriendGirlfriend"';
 if (person.headOfHouseholdRelation === "BoyfriendGirlfriend") { print(" selected") }
__p+='>{{ __("Boyfriend/Girlfriend", "accessnyc-screener")|trim|escape("js") }}</option>\n      <option value="DomesticPartner"';
 if (person.headOfHouseholdRelation === "DomesticPartner") { print(" selected") }
__p+='>{{ __("Domestic Partner", "accessnyc-screener")|trim|escape("js") }}</option>\n      <option value="Unrelated"';
 if (person.headOfHouseholdRelation === "Unrelated") { print(" selected") }
__p+='>{{ __("Unrelated", "accessnyc-screener")|trim|escape("js") }}</option>\n      <option value="Other"';
 if (person.headOfHouseholdRelation === "Other") { print(" selected") }
__p+='>{{ __("Related in some other way", "accessnyc-screener")|trim|escape("js") }}</option>\n    </select>\n  </div>\n</div>\n\n<hr class="divider screen-tablet:divider-large border-grey-light">\n\n<fieldset class="c-question">\n  <legend class="c-question__label">{{ __("Do any of these apply to them?", "accessnyc-screener")|trim|escape("js") }}</legend>\n  <p>{{ __("It’s OK to pick more than one.", "accessnyc-screener")|trim|escape("js") }}</p>\n  <div class="c-question__container screen-tablet:layout-columns js-screener-checkbox-group">\n    <label class="checkbox">\n      <input class="js-screener-toggle" type="checkbox" name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].student" value="1" data-toggles="#screener-is-person-'+
((__t=( personIndex ))==null?'':__t)+
'-full-time-student" data-type="boolean"';
 if (person.student) { print(" checked") }
__p+='>\n      <span class="checkbox__label">{{ __("Student", "accessnyc-screener")|trim|escape("js") }}</span>\n    </label>\n    <label class="checkbox">\n      <input type="checkbox" name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].pregnant" value="1" data-type="boolean"';
 if (person.pregnant) { print(" checked") }
__p+='>\n      <span class="checkbox__label">{{ __("Pregnant", "accessnyc-screener")|trim|escape("js") }}</span>\n    </label>\n    <label class="checkbox">\n      <input class="js-screener-toggle" type="checkbox" name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].unemployed" value="1" data-toggles="#screener-did-person-'+
((__t=( personIndex ))==null?'':__t)+
'-work-last-18-months" data-type="boolean"';
 if (person.unemployed) { print(" checked") }
__p+='>\n      <span class="checkbox__label">{{ __("Unemployed", "accessnyc-screener")|trim|escape("js") }}</span>\n    </label>\n    <label class="checkbox">\n      <input type="checkbox" name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].blind" value="1" data-type="boolean"';
 if (person.blind) { print(" checked") }
__p+='>\n      <span class="checkbox__label">{{ __("Blind or visually impaired", "accessnyc-screener")|trim|escape("js") }}</span>\n    </label>\n    <label class="checkbox">\n      <input type="checkbox" name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].disabled" value="1" data-type="boolean"';
 if (person.disabled) { print(" checked") }
__p+='>\n      <span class="checkbox__label">{{ __("Have any disabilities", "accessnyc-screener")|trim|escape("js") }}</span>\n    </label>\n    <label class="checkbox">\n      <input type="checkbox" name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].veteran" value="1" data-type="boolean"';
 if (person.veteran) { print(" checked") }
__p+='>\n      <span class="checkbox__label">{{ __("Served in the U.S. Armed Forces, National Guard or Reserves", "accessnyc-screener")|trim|escape("js") }}</span>\n    </label>\n    <label class="checkbox">\n      <input class="screener-checkboxes-input sr-only js-clear-group" type="checkbox"';
 if (!person.unemployed && !person.pregnant && !person.unemployed && !person.blind && !person.disabled && !person.veteran) { print(" checked") }
__p+='>\n      <span class="checkbox__label">{{ __("None of these apply", "accessnyc-screener")|trim|escape("js") }}</span>\n    </label>\n  </div>\n</fieldset>\n\n<fieldset class="c-question ';
 if (!person.studentFulltime) { print("hidden") }
__p+='" id="screener-is-person-'+
((__t=( personIndex ))==null?'':__t)+
'-full-time-student">\n  <legend class="c-question__label">{{ __("Are they a full-time student?", "accessnyc-screener")|trim|escape("js") }}</legend>\n  <div class="c-question__container js-screener-radio-group">\n    <label class="toggle js-screener-radio-label">\n      <input class="sr-only" value="1" type="radio" name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].studentFulltime" data-type="boolean"';
 if (person.studentFulltime) { print(" checked") }
__p+='/>\n      <span class="toggle__label">{{ __("Yes", "accessnyc-screener")|trim|escape("js") }}</span>\n    </label>\n    <label class="toggle js-screener-radio-label">\n      <input class="sr-only" value="0" type="radio" name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].studentFulltime" data-type="boolean"';
 if (!person.studentFulltime) { print(" checked") }
__p+='/>\n      <span class="toggle__label">{{ __("No", "accessnyc-screener")|trim|escape("js") }}</span>\n    </label>\n  </div>\n</fieldset>\n\n<fieldset class="c-question ';
 if (!person.unemployedWorkedLast18Months) { print("hidden") }
__p+='" id="screener-did-person-'+
((__t=( personIndex ))==null?'':__t)+
'-work-last-18-months">\n  <legend class="c-question__label">{{ __("Did they work in the last 18 months?", "accessnyc-screener")|trim|escape("js") }}</legend>\n  <div class="c-question__container js-screener-radio-group">\n    <label class="toggle js-screener-radio-label">\n      <input class="sr-only" value="1" type="radio" name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].unemployedWorkedLast18Months" data-type="boolean"';
 if (person.unemployedWorkedLast18Months) { print(" checked") }
__p+='/>\n      <span class="toggle__label">{{ __("Yes", "accessnyc-screener")|trim|escape("js") }}</span>\n    </label>\n    <label class="toggle js-screener-radio-label">\n      <input class="sr-only" value="0" type="radio" name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].unemployedWorkedLast18Months" data-type="boolean"';
 if (!person.unemployedWorkedLast18Months) { print(" checked") }
__p+='/>\n      <span class="toggle__label">{{ __("No", "accessnyc-screener")|trim|escape("js") }}</span>\n    </label>\n  </div>\n</fieldset>\n\n<hr class="divider screen-tablet:divider-large border-grey-light">\n\n<fieldset class="c-question">\n  <legend class="c-question__label">{{ __("Do they receive any of these benefits?", "accessnyc-screener")|trim|escape("js") }}</legend>\n  <div class="c-question__container screen-tablet:layout-columns js-screener-checkbox-group">\n    <label class="checkbox">\n      <input type="checkbox" name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].benefitsMedicaid" value="1" data-type="boolean"';
 if (person.benefitsMedicaid) { print(" checked") }
__p+='>\n      <span class="checkbox__label">{{ __("Medicaid", "accessnyc-screener")|trim|escape("js") }}</span>\n    </label>\n    <label class="checkbox">\n      <input type="checkbox" name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].benefitsMedicaidDisability" value="1" data-type="boolean"';
 if (person.benefitsMedicaidDisability) { print(" checked") }
__p+='>\n      <span class="checkbox__label">{{ __("Disability-related Medicaid", "accessnyc-screener")|trim|escape("js") }}</span>\n    </label>\n    <label class="checkbox">\n      <input class="screener-checkboxes-input sr-only js-clear-group" type="checkbox" checked>\n      <span class="checkbox__label">{{ __("None of these apply", "accessnyc-screener")|trim|escape("js") }}</span>\n    </label>\n  </div>\n</fieldset>\n\n<hr class="divider screen-tablet:divider-large border-grey-light">\n\n<fieldset class="c-question">\n  <legend class="c-question__label">{{ __("Do they have an income?", "accessnyc-screener")|trim|escape("js") }}</legend>\n  <p>{{ __("This includes money from jobs, alimony, investments or gifts.", "accessnyc-screener")|trim|escape("js") }}</p>\n  <div class="c-question__container js-screener-radio-group">\n    <label class="toggle js-screener-radio-label">\n      <input class="js-screener-toggle js-add-section sr-only"\n        value="1"\n        type="radio"\n        name="person-'+
((__t=( personIndex ))==null?'':__t)+
'-has-income"\n        data-type="boolean"\n        data-shows="#person-'+
((__t=( personIndex ))==null?'':__t)+
'-income"\n        data-renders="#screener-person-'+
((__t=( personIndex ))==null?'':__t)+
'-income-0"\n        data-render-target="#person-'+
((__t=( personIndex ))==null?'':__t)+
'-income"\n        data-matrix="income"\n        data-matrix-index="0"\n        data-person-index="'+
((__t=( personIndex ))==null?'':__t)+
'"\n        ';
 if (person.incomes.length) { print("checked") }
__p+='>\n      <span class="toggle__label">{{ __("Yes", "accessnyc-screener")|trim|escape("js") }}</span>\n    </label>\n    <label class="toggle js-screener-radio-label">\n      <input class="js-screener-toggle sr-only" value="0" type="radio" name="person-'+
((__t=( personIndex ))==null?'':__t)+
'-has-income" data-type="boolean" data-toggles="#person-'+
((__t=( personIndex ))==null?'':__t)+
'-income"';
 if (!person.incomes.length) { print(" checked") }
__p+='>\n      <span class="toggle__label">{{ __("No", "accessnyc-screener")|trim|escape("js") }}</span>\n    </label>\n  </div>\n</fieldset>\n\n<div class="js-screener-matrix screener-matrix';
 if (!person.incomes.length) { print(" hidden") }
__p+='" id="person-'+
((__t=( personIndex ))==null?'':__t)+
'-income">\n  ';
 if (person.incomes.length) { person.incomes.forEach(function(item, matrixIndex, list) { 
__p+='\n  <div id="screener-person-'+
((__t=( personIndex ))==null?'':__t)+
'-income-'+
((__t=( matrixIndex ))==null?'':__t)+
'" class="matrix-item js-matrix-item">\n    <hr class="divider screen-tablet:divider-large border-grey-light">\n\n    <div class="c-question">\n      <div class="prime-label">\n        <label class="c-question__label" for="person-'+
((__t=( personIndex ))==null?'':__t)+
'-incomes-'+
((__t=( matrixIndex ))==null?'':__t)+
'-type">\n          {{ __("What type of income have they had most recently?", "accessnyc-screener")|trim|escape("js") }}\n        </label>\n        <p>{{ __("Answer the best you can. You will be able to include additional types of expenses. The more you include, the more accurate your results will be.", "accessnyc-screener")|trim|escape("js") }}</p>\n      </div>\n      <div class="non-prime-label">\n        <label class="c-question__label" for="person-'+
((__t=( personIndex ))==null?'':__t)+
'-incomes-'+
((__t=( matrixIndex ))==null?'':__t)+
'-type">\n          {{ __("If they have another type of income, select it below.", "accessnyc-screener")|trim|escape("js") }}\n        </label>\n      </div>\n      <div class="c-question__container">\n        <select class="select js-matrix-select js-add-section js-screener-toggle"\n          id="person-'+
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
'">\n          <option value="">{{ __("Click to add an income type", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="Wages"';
 if (item.type === "Wages") { print(" selected") }
__p+='>{{ __("wages, salaries, tips", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="SelfEmployment"';
 if (item.type === "SelfEmployment") { print(" selected") }
__p+='>{{ __("self-employment income", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="Unemployment"';
 if (item.type === "Unemployment") { print(" selected") }
__p+='>{{ __("unemployment benefits", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="CashAssistance"';
 if (item.type === "CashAssistance") { print(" selected") }
__p+='>{{ __("Cash Assistance grant", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="ChildSupport"';
 if (item.type === "ChildSupport") { print(" selected") }
__p+='>{{ __("child support (received)", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="DisabilityMedicaid"';
 if (item.type === "DisabilityMedicaid") { print(" selected") }
__p+='>{{ __("disability-related Medicaid", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="SSI"';
 if (item.type === "SSI") { print(" selected") }
__p+='>{{ __("Supplemental Security Income (SSI)", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="SSDependent"';
 if (item.type === "SSDependent") { print(" selected") }
__p+='>{{ __("Social Security Dependent Benefits", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="SSDisability"';
 if (item.type === "SSDisability") { print(" selected") }
__p+='>{{ __("Social Security Disability Benefits", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="SSSurvivor"';
 if (item.type === "SSSurvivor") { print(" selected") }
__p+='>{{ __("Social Security Survivor’s Benefits", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="SSRetirement"';
 if (item.type === "SSRetirement") { print(" selected") }
__p+='>{{ __("Social Security Retirement Benefits", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="NYSDisability"';
 if (item.type === "NYSDisability") { print(" selected") }
__p+='>{{ __("New York State Disability Benefits", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="Veteran"';
 if (item.type === "Veteran") { print(" selected") }
__p+='>{{ __("Veteran’s Pension or Benefits", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="Pension"';
 if (item.type === "Pension") { print(" selected") }
__p+='>{{ __("Government or Private Pension", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="DeferredComp"';
 if (item.type === "DeferredComp") { print(" selected") }
__p+='>{{ __("Withdrawals from Deferred Compensation (IRA, Keogh, etc.)", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="WorkersComp"';
 if (item.type === "WorkersComp") { print(" selected") }
__p+='>{{ __("Worker’s Compensation", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="Alimony"';
 if (item.type === "Alimony") { print(" selected") }
__p+='>{{ __("alimony (received)", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="Boarder"';
 if (item.type === "Boarder") { print(" selected") }
__p+='>{{ __("boarder or lodger", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="Gifts"';
 if (item.type === "Gifts") { print(" selected") }
__p+='>{{ __("gifts/contributions (received)", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="Rental"';
 if (item.type === "Rental") { print(" selected") }
__p+='>{{ __("rental income", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="Investment"';
 if (item.type === "Investment") { print(" selected") }
__p+='>{{ __("investment income (interest, dividends, and profit from selling stocks)", "accessnyc-screener")|trim|escape("js") }}</option>\n        </select>\n      </div>\n    </div>\n\n    <div id="screener-person-'+
((__t=( personIndex ))==null?'':__t)+
'-income-'+
((__t=( matrixIndex ))==null?'':__t)+
'-details">\n      <div class="c-question">\n        <label class="c-question__label" for="person-'+
((__t=( personIndex ))==null?'':__t)+
'-incomes-'+
((__t=( matrixIndex ))==null?'':__t)+
'-amount">\n          {{ __("How much do they receive for this type of income:", "accessnyc-screener")|trim|escape("js") }} ';
 print(localize(item.type)) 
__p+='?\n        </label>\n        <div class="c-question__container">\n          <div class="currency-usd">\n            <input id="person-'+
((__t=( personIndex ))==null?'':__t)+
'-incomes-'+
((__t=( matrixIndex ))==null?'':__t)+
'-amount" name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].incomes['+
((__t=( matrixIndex ))==null?'':__t)+
'].amount" type="number" min="0" data-type="float" value="'+
((__t=( item.amount ))==null?'':__t)+
'" required>\n          </div>\n        </div>\n      </div>\n\n      <div class="c-question">\n        <label class="c-question__label" for="person-'+
((__t=( personIndex ))==null?'':__t)+
'-incomes-'+
((__t=( matrixIndex ))==null?'':__t)+
'-frequency">\n          {{ __("How often do they receive this income:", "accessnyc-screener")|trim|escape("js") }} ';
 print(localize(item.type)) 
__p+='?\n        </label>\n        <div class="c-question__container">\n          <select class="select" id="person-'+
((__t=( personIndex ))==null?'':__t)+
'-incomes-'+
((__t=( matrixIndex ))==null?'':__t)+
'-frequency" name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].incomes['+
((__t=( matrixIndex ))==null?'':__t)+
'].frequency" required>\n            <option value="">{{ __("Click to add an expense frequency", "accessnyc-screener")|trim|escape("js") }}</option>\n            <option value="weekly"';
 if (item.frequency === "weekly") { print(" selected") }
__p+='>{{ __("Every week", "accessnyc-screener")|trim|escape("js") }}</option>\n            <option value="biweekly"';
 if (item.frequency === "biweekly") { print(" selected") }
__p+='>{{ __("Every 2 weeks", "accessnyc-screener")|trim|escape("js") }}</option>\n            <option value="monthly"';
 if (item.frequency === "monthly") { print(" selected") }
__p+='>{{ __("Monthly", "accessnyc-screener")|trim|escape("js") }}</option>\n            <option value="semimonthly"';
 if (item.frequency === "semimonthly") { print(" selected") }
__p+='>{{ __("Twice a month", "accessnyc-screener")|trim|escape("js") }}</option>\n            <option value="yearly"';
 if (item.frequency === "yearly") { print(" selected") }
__p+='>{{ __("Every year", "accessnyc-screener")|trim|escape("js") }}</option>\n          </select>\n        </div>\n      </div>\n    </div>\n  </div>\n\n  ';
 if (matrixIndex === list.length - 1) { 
__p+='\n  <div id="screener-person-'+
((__t=( personIndex ))==null?'':__t)+
'-income-';
 print(matrixIndex + 1) 
__p+='" class="matrix-item js-matrix-item">\n\n    <hr class="divider screen-tablet:divider-large border-grey-light">\n\n    <div class="c-question">\n      ';
 if (matrixIndex === 0) { 
__p+='\n      <label class="c-question__label" for="person-'+
((__t=( personIndex ))==null?'':__t)+
'-incomes-';
 print(matrixIndex + 1) 
__p+='-type">\n        ';
 if (personIndex === 0) { 
__p+='\n        {{ __("What type of income have you had most recently?", "accessnyc-screener")|trim|escape("js") }}\n        ';
 } else { 
__p+='\n        {{ __("What type of income have they had most recently?", "accessnyc-screener")|trim|escape("js") }}\n        ';
 } 
__p+='\n      </label>\n\n      <p>{{ __("Answer the best you can. You will be able to include additional types of income. The more you include, the more accurate your results will be.", "accessnyc-screener")|trim|escape("js") }}</p>\n      ';
 } else { 
__p+='\n      <label class="c-question__label" for="person-'+
((__t=( personIndex ))==null?'':__t)+
'-incomes-';
 print(matrixIndex + 1) 
__p+='-type">\n        ';
 if (personIndex === 0) { 
__p+='\n        {{ __("If you receive another type of income, select it below.", "accessnyc-screener")|trim|escape("js") }}\n        ';
 } else { 
__p+='\n        {{ __("If they receive another type of income, select it below.", "accessnyc-screener")|trim|escape("js") }}\n        ';
 } 
__p+='\n      </label>\n      ';
 } 
__p+='\n\n      <div class="c-question__container">\n        <select class="select js-matrix-select js-add-section js-screener-toggle"\n          id="person-'+
((__t=( personIndex ))==null?'':__t)+
'-incomes-';
 print(matrixIndex + 1) 
__p+='"\n          name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].incomes[';
 print(matrixIndex + 1) 
__p+='].type"\n          data-toggles="#screener-person-'+
((__t=( personIndex ))==null?'':__t)+
'-income-';
 print(matrixIndex + 1) 
__p+='-details"\n          data-renders="#screener-person-'+
((__t=( personIndex ))==null?'':__t)+
'-income-';
 print(matrixIndex + 2) 
__p+='"\n          data-matrix="income"\n          data-matrix-index="';
 print(matrixIndex + 2) 
__p+='"\n          data-person-index="'+
((__t=( personIndex ))==null?'':__t)+
'">\n          <option value="" selected>{{ __("Click to add an income type", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="Wages">{{ __("wages, salaries, tips", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="SelfEmployment">{{ __("self-employment income", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="Unemployment">{{ __("unemployment benefits", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="CashAssistance">{{ __("Cash Assistance grant", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="ChildSupport">{{ __("child support (received)", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="DisabilityMedicaid">{{ __("disability-related Medicaid", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="SSI">{{ __("Supplemental Security Income (SSI)", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="SSDependent">{{ __("Social Security Dependent Benefits", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="SSDisability">{{ __("Social Security Disability Benefits", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="SSSurvivor">{{ __("Social Security Survivor’s Benefits", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="SSRetirement">{{ __("Social Security Retirement Benefits", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="NYSDisability">{{ __("New York State Disability Benefits", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="Veteran">{{ __("Veteran’s Pension or Benefits", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="Pension">{{ __("Government or Private Pension", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="DeferredComp">{{ __("Withdrawals from Deferred Compensation (IRA, Keogh, etc.)", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="WorkersComp">{{ __("Worker’s Compensation", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="Alimony">{{ __("alimony (received)", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="Boarder">{{ __("boarder or lodger", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="Gifts">{{ __("gifts/contributions (received)", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="Rental">{{ __("rental income", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="Investment">{{ __("investment income (interest, dividends, and profit from selling stocks)", "accessnyc-screener")|trim|escape("js") }}</option>\n        </select>\n      </div>\n    </div>\n\n    <div id="screener-person-'+
((__t=( personIndex ))==null?'':__t)+
'-income-';
 print(matrixIndex + 1) 
__p+='-details" class="hidden">\n      <div class="c-question">\n        <label class="c-question__label" for="person-'+
((__t=( personIndex ))==null?'':__t)+
'-incomes-';
 print(matrixIndex + 1) 
__p+='-amount">\n          {{ __("How much do they receive for this type of income:", "accessnyc-screener")|trim|escape("js") }}\n          <span data-js="transaction-label"></span>?\n        </label>\n        <div class="c-question__container">\n          <div class="currency-usd">\n            <input id="person-'+
((__t=( personIndex ))==null?'':__t)+
'-incomes-';
 print(matrixIndex + 1) 
__p+='-amount" name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].incomes[';
 print(matrixIndex + 1) 
__p+='].amount" type="number" data-type="float" required>\n          </div>\n        </div>\n      </div>\n\n      <div class="c-question">\n        <label class="c-question__label" for="person-'+
((__t=( personIndex ))==null?'':__t)+
'-incomes-';
 print(matrixIndex + 1) 
__p+='-frequency">\n          {{ __("How often do they receive this income:", "accessnyc-screener")|trim|escape("js") }}\n          <span data-js="transaction-label"></span>?\n        </label>\n        <div class="c-question__container">\n          <select class="select" id="person-'+
((__t=( personIndex ))==null?'':__t)+
'-incomes-';
 print(matrixIndex + 1) 
__p+='-frequency" name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].incomes[';
 print(matrixIndex + 1) 
__p+='].frequency" required>\n            <option value="">{{ __("Click to add an income frequency", "accessnyc-screener")|trim|escape("js") }}</option>\n            <option value="weekly">{{ __("Every week", "accessnyc-screener")|trim|escape("js") }}</option>\n            <option value="biweekly">{{ __("Every 2 weeks", "accessnyc-screener")|trim|escape("js") }}</option>\n            <option value="monthly">{{ __("Monthly", "accessnyc-screener")|trim|escape("js") }}</option>\n            <option value="semimonthly">{{ __("Twice a month", "accessnyc-screener")|trim|escape("js") }}</option>\n            <option value="yearly">{{ __("Every year", "accessnyc-screener")|trim|escape("js") }}</option>\n          </select>\n        </div>\n      </div>\n    </div>\n  </div>\n  ';
 } 
__p+='\n  ';
 }); } 
__p+='\n</div>\n\n<hr class="divider screen-tablet:divider-large border-grey-light">\n\n<fieldset class="c-question">\n  <legend class="c-question__label">{{ __("Do they have any expenses?", "accessnyc-screener")|trim|escape("js") }}</legend>\n\n  <p>{{ __("This includes costs like rent, mortgage, medical bills, child care, child support and heating bills.", "accessnyc-screener")|trim|escape("js") }}</p>\n\n  <div class="c-question__container js-screener-radio-group">\n    <label class="toggle js-screener-radio-label">\n      <input class="js-screener-toggle js-add-section sr-only"\n        value="1"\n        type="radio"\n        name="person-'+
((__t=( personIndex ))==null?'':__t)+
'-has-expenses"\n        data-type="boolean"\n        data-shows="#person-'+
((__t=( personIndex ))==null?'':__t)+
'-expense"\n        data-renders="#screener-person-'+
((__t=( personIndex ))==null?'':__t)+
'-expense-0"\n        data-render-target="#person-'+
((__t=( personIndex ))==null?'':__t)+
'-expense"\n        data-matrix="expense"\n        data-matrix-index="0"\n        data-person-index="'+
((__t=( personIndex ))==null?'':__t)+
'"\n        ';
 if (person.expenses.length) { print(" checked") }
__p+='>\n      <span class="toggle__label">{{ __("Yes", "accessnyc-screener")|trim|escape("js") }}</span>\n    </label>\n    <label class="toggle js-screener-radio-label">\n      <input class="js-screener-toggle sr-only" value="0" type="radio" name="person-'+
((__t=( personIndex ))==null?'':__t)+
'-has-expenses" data-type="boolean" data-toggles="#person-'+
((__t=( personIndex ))==null?'':__t)+
'-expense"';
 if (!person.expenses.length) { print(" checked") }
__p+=' />\n      <span class="toggle__label">{{ __("No", "accessnyc-screener")|trim|escape("js") }}</span>\n    </label>\n  </div>\n</fieldset>\n\n<div class="js-screener-matrix screener-matrix';
 if (!person.expenses.length) { print(" hidden") }
__p+='" id="person-'+
((__t=( personIndex ))==null?'':__t)+
'-expense">\n  ';
 if (person.expenses.length) { person.expenses.forEach(function(item, matrixIndex, list) { 
__p+='\n  <div id="screener-person-'+
((__t=( personIndex ))==null?'':__t)+
'-expense-'+
((__t=( matrixIndex ))==null?'':__t)+
'" class="matrix-item js-matrix-item">\n    <hr class="divider screen-tablet:divider-large border-grey-light">\n\n    <div class="c-question">\n      ';
 if (matrixIndex === 0) { 
__p+='\n      <label class="c-question__label" for="person-'+
((__t=( personIndex ))==null?'':__t)+
'-expenses-'+
((__t=( matrixIndex ))==null?'':__t)+
'-type">{{ __("What type of expense have they had most recently?", "accessnyc-screener")|trim|escape("js") }}</label>\n\n      <p>{{ __("Answer the best you can. You will be able to include additional types of expenses. The more you include, the more accurate your results will be.", "accessnyc-screener")|trim|escape("js") }}</p>\n      ';
 } else { 
__p+='\n      <label class="c-question__label" for="person-'+
((__t=( personIndex ))==null?'':__t)+
'-expenses-'+
((__t=( matrixIndex ))==null?'':__t)+
'-type">\n        {{ __("If they have another type of income, select it below.", "accessnyc-screener")|trim|escape("js") }}\n      </label>\n      ';
 } 
__p+='\n\n      <div class="c-question__container">\n        <select class="select js-matrix-select js-add-section js-screener-toggle"\n          id="person-'+
((__t=( personIndex ))==null?'':__t)+
'-expenses-'+
((__t=( matrixIndex ))==null?'':__t)+
'-type"\n          name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].expenses['+
((__t=( matrixIndex ))==null?'':__t)+
'].type"\n          data-toggles="#screener-person-'+
((__t=( personIndex ))==null?'':__t)+
'-expenses-'+
((__t=( matrixIndex ))==null?'':__t)+
'-details"\n          data-renders="#screener-person-'+
((__t=( personIndex ))==null?'':__t)+
'-expense-'+
((__t=( matrixIndex ))==null?'':__t)+
'"\n          data-matrix="expense"\n          data-matrix-index="';
 print(matrixIndex + 1) 
__p+='"\n          data-person-index="'+
((__t=( personIndex ))==null?'':__t)+
'">\n          <option value="">{{ __("Click to add an expense type", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="ChildCare"';
 if (item.type === "ChildCare") { print(" selected") }
__p+='>{{ __("Child Care", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="ChildSupport"';
 if (item.type === "ChildSupport") { print(" selected") }
__p+='>{{ __("Child Support (Paid)", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="DependentCare"';
 if (item.type === "DependentCare") { print(" selected") }
__p+='>{{ __("Dependent Care", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="Rent"';
 if (item.type === "Rent") { print(" selected") }
__p+='>{{ __("Rent", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="Medical"';
 if (item.type === "Medical") { print(" selected") }
__p+='>{{ __("Medical expense", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="Heating"';
 if (item.type === "Heating") { print(" selected") }
__p+='>{{ __("Heating", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="Cooling"';
 if (item.type === "Cooling") { print(" selected") }
__p+='>{{ __("Cooling", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="Mortgage"';
 if (item.type === "Mortgage") { print(" selected") }
__p+='>{{ __("Mortgage", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="Utilities"';
 if (item.type === "Utilities") { print(" selected") }
__p+='>{{ __("Utilities", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="Telephone"';
 if (item.type === "Telephone") { print(" selected") }
__p+='>{{ __("Telephone", "accessnyc-screener")|trim|escape("js") }}</option>\n          <option value="InsurancePremiums"';
 if (item.type === "InsurancePremiums") { print(" selected") }
__p+='>{{ __("Third Party Insurance Premiums", "accessnyc-screener")|trim|escape("js") }}</option>\n        </select>\n      </div>\n    </div>\n\n    <div id="screener-person-'+
((__t=( personIndex ))==null?'':__t)+
'-expense-'+
((__t=( matrixIndex ))==null?'':__t)+
'-details">\n      <div class="c-question">\n        <label class="c-question__label" for="person-'+
((__t=( personIndex ))==null?'':__t)+
'-expenses-'+
((__t=( matrixIndex ))==null?'':__t)+
'-amount">\n          {{ __("How much is this type of expense:", "accessnyc-screener")|trim|escape("js") }} ';
 print(localize(item.type)) 
__p+='?\n        </label>\n        <div class="c-question__container">\n          <div class="currency-usd">\n            <input id="person-'+
((__t=( personIndex ))==null?'':__t)+
'-expenses-'+
((__t=( matrixIndex ))==null?'':__t)+
'-amount" name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].expenses['+
((__t=( matrixIndex ))==null?'':__t)+
'].amount" type="number" data-type="float" min="0" value="'+
((__t=( item.amount ))==null?'':__t)+
'" required>\n          </div>\n        </div>\n      </div>\n\n      <div class="c-question">\n        <label class="c-question__label" for="person-'+
((__t=( personIndex ))==null?'':__t)+
'-expenses-'+
((__t=( matrixIndex ))==null?'':__t)+
'-frequency">\n          {{ __("How often do they have this expense:", "accessnyc-screener")|trim|escape("js") }} ';
 print(localize(item.type)) 
__p+='?\n        </label>\n        <div class="c-question__container">\n          <select class="select" id="person-'+
((__t=( personIndex ))==null?'':__t)+
'-expenses-'+
((__t=( matrixIndex ))==null?'':__t)+
'-frequency" name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].expenses['+
((__t=( matrixIndex ))==null?'':__t)+
'].frequency" required>\n            <option value="">{{ __("Click to add an expense frequency", "accessnyc-screener")|trim|escape("js") }}</option>\n            <option value="weekly"';
 if (item.frequency === "weekly") { print(" selected") }
__p+='>{{ __("Every week", "accessnyc-screener")|trim|escape("js") }}</option>\n            <option value="biweekly"';
 if (item.frequency === "biweekly") { print(" selected") }
__p+='>{{ __("Every 2 weeks", "accessnyc-screener")|trim|escape("js") }}</option>\n            <option value="monthly"';
 if (item.frequency === "monthly") { print(" selected") }
__p+='>{{ __("Monthly", "accessnyc-screener")|trim|escape("js") }}</option>\n            <option value="semimonthly"';
 if (item.frequency === "semimonthly") { print(" selected") }
__p+='>{{ __("Twice a month", "accessnyc-screener")|trim|escape("js") }}</option>\n            <option value="yearly"';
 if (item.frequency === "yearly") { print(" selected") }
__p+='>{{ __("Every year", "accessnyc-screener")|trim|escape("js") }}</option>\n          </select>\n        </div>\n      </div>\n    </div>\n  </div>\n  ';
 if (matrixIndex === list.length - 1) { 
__p+='\n    <div id="screener-person-'+
((__t=( personIndex ))==null?'':__t)+
'-expense-';
 print(matrixIndex + 1) 
__p+='" class="matrix-item js-matrix-item">\n      <hr class="divider screen-tablet:divider-large border-grey-light">\n\n      <div class="c-question">\n        ';
 if (matrixIndex === 0) { 
__p+='\n        <label class="c-question__label" for="person-'+
((__t=( personIndex ))==null?'':__t)+
'-expenses-';
 print(matrixIndex + 1) 
__p+='-type">\n          ';
 if (personIndex === 0) { 
__p+='\n          {{ __("What type of expense have you had most recently?", "accessnyc-screener")|trim|escape("js") }}\n          ';
 } else { 
__p+='\n          {{ __("What type of expense have they had most recently?", "accessnyc-screener")|trim|escape("js") }}\n          ';
 } 
__p+='\n        </label>\n\n        <p>{{ __("Answer the best you can. You will be able to include additional types of expenses. The more you include, the more accurate your results will be.", "accessnyc-screener")|trim|escape("js") }}</p>\n        ';
 } else { 
__p+='\n        <label class="c-question__label" for="person-'+
((__t=( personIndex ))==null?'':__t)+
'-expenses-';
 print(matrixIndex + 2) 
__p+='-type">\n          ';
 if (personIndex === 0) { 
__p+='\n          {{ __("If you have another type of expense, select it below.", "accessnyc-screener")|trim|escape("js") }}\n          ';
 } else { 
__p+='\n          {{ __("If they have another type of expense, select it below.", "accessnyc-screener")|trim|escape("js") }}\n          ';
 } 
__p+='\n        </label>\n        ';
 } 
__p+='\n\n        <div class="c-question__container">\n          <select class="select js-matrix-select js-add-section js-screener-toggle"\n            id="person-'+
((__t=( personIndex ))==null?'':__t)+
'-expenses-';
 print(matrixIndex + 1) 
__p+='"\n            name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].expenses[';
 print(matrixIndex + 1) 
__p+='].type"\n            data-toggles="#screener-person-'+
((__t=( personIndex ))==null?'':__t)+
'-expense-';
 print(matrixIndex + 1) 
__p+='-details"\n            data-renders="#screener-person-'+
((__t=( personIndex ))==null?'':__t)+
'-expense-';
 print(matrixIndex + 2) 
__p+='"\n            data-matrix="expense"\n            data-matrix-index="';
 print(matrixIndex + 2) 
__p+='"\n            data-person-index="'+
((__t=( personIndex ))==null?'':__t)+
'">\n            <option value="">{{ __("Click to add an expense type", "accessnyc-screener")|trim|escape("js") }}</option>\n            <option value="ChildCare">{{ __("Child Care", "accessnyc-screener")|trim|escape("js") }}</option>\n            <option value="ChildSupport">{{ __("Child Support (Paid)", "accessnyc-screener")|trim|escape("js") }}</option>\n            <option value="DependentCare">{{ __("Dependent Care", "accessnyc-screener")|trim|escape("js") }}</option>\n            <option value="Rent">{{ __("Rent", "accessnyc-screener")|trim|escape("js") }}</option>\n            <option value="Medical">{{ __("Medical expense", "accessnyc-screener")|trim|escape("js") }}</option>\n            <option value="Heating">{{ __("Heating", "accessnyc-screener")|trim|escape("js") }}</option>\n            <option value="Cooling">{{ __("Cooling", "accessnyc-screener")|trim|escape("js") }}</option>\n            <option value="Mortgage">{{ __("Mortgage", "accessnyc-screener")|trim|escape("js") }}</option>\n            <option value="Utilities">{{ __("Utilities", "accessnyc-screener")|trim|escape("js") }}</option>\n            <option value="Telephone">{{ __("Telephone", "accessnyc-screener")|trim|escape("js") }}</option>\n            <option value="InsurancePremiums">{{ __("Third Party Insurance Premiums", "accessnyc-screener")|trim|escape("js") }}</option>\n          </select>\n        </div>\n      </div>\n\n      <div id="screener-person-'+
((__t=( personIndex ))==null?'':__t)+
'-expense-';
 print(matrixIndex + 1) 
__p+='-details" class="hidden">\n        <div class="c-question">\n          <label class="c-question__label" for="person-'+
((__t=( personIndex ))==null?'':__t)+
'-expenses-';
 print(matrixIndex + 1) 
__p+='-amount">\n            {{ __("How much is this type of expense:", "accessnyc-screener")|trim|escape("js") }}\n            <span data-js="transaction-label"></span>?\n          </label>\n          <div class="c-question__container">\n            <div class="currency-usd">\n              <input id="person-'+
((__t=( personIndex ))==null?'':__t)+
'-expenses-';
 print(matrixIndex + 1) 
__p+='-amount" name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].expenses[';
 print(matrixIndex + 1) 
__p+='].amount" type="number" data-type="float" required>\n            </div>\n          </div>\n        </div>\n\n        <div class="c-question">\n          <label class="c-question__label" for="person-'+
((__t=( personIndex ))==null?'':__t)+
'-expenses-';
 print(matrixIndex + 1) 
__p+='-frequency">\n            ';
 if (personIndex === 0) { 
__p+='\n            {{ __("How often do you have this expense:", "accessnyc-screener")|trim|escape("js") }}\n            ';
 } else { 
__p+='\n            {{ __("How often do they have this expense:", "accessnyc-screener")|trim|escape("js") }}\n            ';
 } 
__p+='\n            <span data-js="transaction-label"></span>?\n          </label>\n\n          <div class="c-question__container">\n            <select class="select" id="person-'+
((__t=( personIndex ))==null?'':__t)+
'-expenses-';
 print(matrixIndex + 1) 
__p+='-frequency" name="Person['+
((__t=( personIndex ))==null?'':__t)+
'].expenses[';
 print(matrixIndex + 1) 
__p+='].frequency" required>\n              <option value="">{{ __("Click to add an expense frequency", "accessnyc-screener")|trim|escape("js") }}</option>\n              <option value="weekly">{{ __("Every week", "accessnyc-screener")|trim|escape("js") }}</option>\n              <option value="biweekly">{{ __("Every 2 weeks", "accessnyc-screener")|trim|escape("js") }}</option>\n              <option value="monthly">{{ __("Monthly", "accessnyc-screener")|trim|escape("js") }}</option>\n              <option value="semimonthly">{{ __("Twice a month", "accessnyc-screener")|trim|escape("js") }}</option>\n              <option value="yearly">{{ __("Every year", "accessnyc-screener")|trim|escape("js") }}</option>\n            </select>\n          </div>\n        </div>\n\n      </div>\n    </div>\n  ';
 } 
__p+='\n  ';
 }); } 
__p+='\n</div>\n';
}
return __p;
}