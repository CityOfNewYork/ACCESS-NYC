/* eslint-env browser */
'use strict';

import _ from 'underscore';

/**
 * Maps screener household/person models to NYC Benefits Screening API payload.
 */

const FREQUENCY_TO_API = {
  weekly: 'Weekly',
  biweekly: 'Biweekly',
  monthly: 'Monthly',
  semimonthly: 'Semimonthly',
  yearly: 'Yearly'
};

const HOUSEHOLD_MEMBER_TYPES = [
  'HeadOfHousehold', 'Child', 'FosterChild', 'StepChild', 'Grandchild', 'Spouse',
  'Parent', 'FosterParent', 'StepParent', 'Grandparent', 'SisterBrother',
  'StepSisterStepBrother', 'BoyfriendGirlfriend', 'DomesticPartner', 'Unrelated', 'Other'
];

const mapFrequency = frequency => {
  if (!frequency) {
    return frequency;
  }
  const key = String(frequency).toLowerCase();
  return FREQUENCY_TO_API[key] || frequency;
};

const formatAmount = amount => {
  if (amount === '' || amount === null || typeof amount === 'undefined') {
    return '';
  }
  return String(amount);
};

const mapPaymentItems = items => {
  if (!_.isArray(items)) {
    return [];
  }
  return items
    .filter(item => item && item.type && item.amount !== '' && item.frequency)
    .map(item => ({
      amount: formatAmount(item.amount),
      type: item.type,
      frequency: mapFrequency(item.frequency)
    }));
};

const resolveHouseholdMemberType = personAttrs => {
  if (personAttrs.headOfHousehold) {
    return 'HeadOfHousehold';
  }
  const relation = personAttrs.headOfHouseholdRelation;
  if (relation && HOUSEHOLD_MEMBER_TYPES.indexOf(relation) >= 0) {
    return relation;
  }
  return 'Other';
};

const mapPerson = person => {
  const raw = person.toObject();
  return {
    age: raw.age,
    student: raw.student,
    studentFulltime: raw.studentFulltime,
    pregnant: raw.pregnant,
    unemployed: raw.unemployed,
    unemployedWorkedLast18Months: raw.unemployedWorkedLast18Months,
    blind: raw.blind,
    disabled: raw.disabled,
    veteran: raw.veteran,
    benefitsMedicaid: raw.benefitsMedicaid,
    benefitsMedicaidDisability: raw.benefitsMedicaidDisability,
    livingOwnerOnDeed: raw.livingOwnerOnDeed,
    livingRentalOnLease: raw.livingRentalOnLease,
    householdMemberType: resolveHouseholdMemberType(raw),
    incomes: mapPaymentItems(raw.incomes),
    expenses: mapPaymentItems(raw.expenses)
  };
};

const mapHousehold = household => {
  const raw = household.toObject();
  const mapped = {
    cashOnHand: formatAmount(raw.cashOnHand || 0),
    livingRentalType: raw.livingRentalType || '',
    livingRenting: raw.livingRenting,
    livingOwner: raw.livingOwner,
    livingStayingWithFriend: raw.livingStayingWithFriend,
    livingHotel: raw.livingHotel,
    livingShelter: raw.livingShelter,
    livingPreferNotToSay: raw.livingPreferNotToSay
  };

  if (raw.zip) {
    mapped.zip = raw.zip;
  }
  if (raw.city === 'NYC') {
    mapped.city = raw.city;
  }

  return mapped;
};

/**
 * @param {ScreenerHousehold} household
 * @param {ScreenerPerson[]} people
 * @param {number} memberCount
 * @return {Array<object>} Screening API submission body
 */
export const buildScreeningApiPayload = (household, people, memberCount) => {
  const persons = _.chain(people)
    .slice(0, memberCount)
    .filter(Boolean)
    .map(mapPerson)
    .value();

  return [{
    household: [mapHousehold(household)],
    person: persons,
    withholdPayload: true
  }];
};

export default buildScreeningApiPayload;
