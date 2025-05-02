"use strict";

if ( icl_vars.loadLanguageJs ) {
  var icl_lang = icl_vars.current_language;
  var icl_home = icl_vars.icl_home;

  window.addLoadEvent = function(func) {
	  if (document.readyState === 'loading') {
		  document.addEventListener('DOMContentLoaded', func);
	  } else {
		  func();
	  }
  }
}
