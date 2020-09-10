/* eslint-env browser */
import ProgramsDetail from 'modules/programs-detail';

/**
* Programs Detail
*/
(() => {
  'use strict';

  (element => {
    if (element) {
      new ProgramsDetail();
    }
  })(document.querySelector(ProgramsDetail.selector));
})();

