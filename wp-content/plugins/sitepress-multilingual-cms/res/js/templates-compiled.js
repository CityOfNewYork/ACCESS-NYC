this["WPML_core"] = this["WPML_core"] || {};
this["WPML_TM"] = this["WPML_TM"] || {};

this["WPML_core"]["templates/taxonomy-translation/copy-all-popup.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '';
__p += '<div class="icl_tt_form wpml-dialog" id="icl_tt_form_' +
((__t = ( trid + '_' + lang )) == null ? '' : __t) +
'" title="' +
((__t = ( labels.copyToAllLanguages )) == null ? '' : __t) +
'"><div class="wpml-dialog-body wpml-dialog-translate"><p class="wpml-dialog-cols-icon"><i class="otgs-ico-copy wpml-dialog-icon-xl"></i></p><div class="wpml-dialog-cols-content"><p> ' +
((__t = ( copyMessage )) == null ? '' : __t) +
' </p><label><input type="checkbox" name="overwrite"> ' +
((__t = ( labels.copyAllOverwrite )) == null ? '' : __t) +
'</label></div><div class="wpml-dialog-footer"><span class="errors icl_error_text"></span> <input class="cancel wpml-dialog-close-button alignleft" value="' +
((__t = ( labels.cancel )) == null ? '' : __t) +
'" type="button"> <input class="button-primary js-copy-all-ok alignright" value="' +
((__t = ( labels.Ok )) == null ? '' : __t) +
'" type="submit"> <span class="spinner alignright"></span></div></div></div>';
return __p
  }
};

this["WPML_core"]["templates/taxonomy-translation/filter.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '', __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
__p += '<div class="icl-tt-tools tablenav top clearfix"> ';
 if ( mode === "translate" ) { ;
__p += ' ' +
((__t = ( WPML_core[ "templates/taxonomy-translation/status-trans-select.html" ]( { taxonomy: taxonomy } ) )) == null ? '' : __t) +
' <label for="in-lang" id="in-lang-label" class="hidden">' +
((__t = (labels.in)) == null ? '' : __t) +
'</label> <select name="language" id="in-lang" class="hidden"><option value="all">' +
((__t = ( labels.anyLang )) == null ? '' : __t) +
'</option> ';
 _.each(langs, function( lang, code ) { ;
__p += ' <option value="' +
((__t = ( code )) == null ? '' : __t) +
'">' +
((__t = ( lang.label )) == null ? '' : __t) +
'</option> ';
 }); ;
__p += ' </select><div class="alignright"><input type="text" name="search" id="tax-search" placeholder="' +
((__t = ( labels.searchPlaceHolder )) == null ? '' : __t) +
'" value=""></div> ';
 } else { ;
__p += ' ' +
((__t = ( labels.refLang.replace( "%language%", WPML_core[ "templates/taxonomy-translation/ref_sync_select.html" ]( { taxonomy:taxonomy, langs:langs } ) ) )) == null ? '' : __t) +
' ';
 } ;
__p += ' <span class="spinner"></span></div>';
return __p
  }
};

this["WPML_core"]["templates/taxonomy-translation/individual-label.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '';
__p += '<a class="icl_tt_label" id="' +
((__t = (taxonomy)) == null ? '' : __t) +
'_' +
((__t = (lang)) == null ? '' : __t) +
'" title="' +
((__t = ( langs[ lang ].label )) == null ? '' : __t) +
': ' +
((__t = ( labels.editTranslation )) == null ? '' : __t) +
'"><i class="otgs-ico-edit"></i></a><div id="popup-' +
((__t = (lang)) == null ? '' : __t) +
'"></div>';
return __p
  }
};

this["WPML_core"]["templates/taxonomy-translation/label-popup.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '', __e = _.escape, __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
__p += '<div class="icl_tt_form wpml-dialog" id="icl_tt_form_' +
((__t = ( taxonomy )) == null ? '' : __t) +
'" title="' +
((__t = ( labels.labelPopupDialogTitle )) == null ? '' : __t) +
'"><div class="wpml-dialog-body wpml-dialog-translate"><header class="wpml-term-translation-header"><h3 class="wpml-header-original">' +
__e( labels.original ) +
' <span class="wpml-title-flag"><img src="' +
((__t = ( langs[ source_lang ].flag )) == null ? '' : __t) +
'"></span><strong>' +
__e( langs[ source_lang ].label ) +
'</strong></h3><h3 class="wpml-header-translation">' +
__e( labels.translationTo ) +
' <span class="wpml-title-flag"><img src="' +
((__t = ( langs[ lang ].flag )) == null ? '' : __t) +
'"></span><strong>' +
__e( langs[ lang ].label ) +
'</strong></h3></header><div class="wpml-form-row"><label for="' +
((__t = ( taxonomy )) == null ? '' : __t) +
'-singular">' +
__e( labels.Singular ) +
'</label> <input readonly="readonly" id="' +
((__t = ( taxonomy )) == null ? '' : __t) +
'-singular-original" value="' +
__e( originalLabels.singular ) +
'" type="text"> <button class="button-copy button-secondary js-button-copy otgs-ico-copy" title="' +
__e( labels.copyFromOriginal ) +
'"></button> <input class="js-translation js-required-translation" id="' +
((__t = ( taxonomy )) == null ? '' : __t) +
'-singular" value="' +
__e( translatedLabels.singular ) +
'" type="text"></div><div class="wpml-form-row"><label for="' +
((__t = ( taxonomy )) == null ? '' : __t) +
'-plural">' +
__e( labels.Plural ) +
'</label> <input readonly="readonly" id="' +
((__t = ( taxonomy )) == null ? '' : __t) +
'-plural-original" value="' +
__e(originalLabels.general ) +
'" type="text"> <button class="button-copy button-secondary js-button-copy otgs-ico-copy" title="' +
__e( labels.copyFromOriginal ) +
'"></button> <input class="js-translation js-required-translation" id="' +
((__t = ( taxonomy )) == null ? '' : __t) +
'-plural" value="' +
__e( translatedLabels.general ) +
'" type="text"></div> ';
 if( slugTranslationEnabled ) { ;
__p += ' <div class="wpml-form-row js-slug-translation-wrapper"><label for="' +
((__t = ( taxonomy )) == null ? '' : __t) +
'-slug">' +
__e( labels.Slug ) +
'</label> <input readonly="readonly" id="' +
((__t = ( taxonomy )) == null ? '' : __t) +
'-slug-original" value="' +
__e(originalLabels.slug ) +
'" type="text"> <button class="button-copy button-secondary js-button-copy otgs-ico-copy" title="' +
__e( labels.copyFromOriginal ) +
'"></button> <input class="js-translation" id="' +
((__t = ( taxonomy )) == null ? '' : __t) +
'-slug" value="' +
__e( translatedLabels.slug ) +
'" type="text"></div> ';
 } ;
__p += ' <div class="wpml-dialog-footer"><span class="errors icl_error_text"></span> <input class="cancel wpml-dialog-close-button alignleft" value="' +
__e( labels.cancel ) +
'" type="button"> <input class="button-primary js-label-save alignright" value="' +
__e( labels.save ) +
'" type="submit"> <span class="spinner alignright"></span></div></div></div>';
return __p
  }
};

this["WPML_core"]["templates/taxonomy-translation/main.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '', __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
__p += '<label for="icl_tt_tax_switch"> ' +
((__t = (labels.taxToTranslate)) == null ? '' : __t) +
' <select id="icl_tt_tax_switch"><option disabled="disabled" selected="selected">-- ' +
((__t = (labels.taxonomy)) == null ? '' : __t) +
' --</option> ';
 _.each(taxonomies, function(taxonomy, index){ ;
__p += ' <option value="' +
((__t = (index)) == null ? '' : __t) +
'"> ' +
((__t = (taxonomy.label)) == null ? '' : __t) +
' </option> ';
 }); ;
__p += ' </select></label><div class="wpml-loading-taxonomy"><span class="spinner is-active"></span>' +
((__t = (labels.preparingTermsData)) == null ? '' : __t) +
'</div><div id="taxonomy-translation"></div>';
return __p
  }
};

this["WPML_core"]["templates/taxonomy-translation/nav.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '', __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
__p += '<div class="tablenav bottom"><div class="tablenav-pages" id="taxonomy-terms-table-nav"><span class="displaying-num"> ';
 if(pages > 1) { ;
__p += ' ' +
((__t = (items)) == null ? '' : __t) +
' ' +
((__t = (labels.items)) == null ? '' : __t) +
' ';
 } else if(pages === 1) {;
__p += ' 1 ' +
((__t = (labels.item)) == null ? '' : __t) +
' ';
 } ;
__p += ' </span><a class="first-page ';
 if(page <= 1 ){ ;
__p += ' disabled ';
 } ;
__p += '" href="###" title="' +
((__t = (labels.goToFirstPage)) == null ? '' : __t) +
'">«</a> <a href="###" title="' +
((__t = (labels.goToPreviousPage)) == null ? '' : __t) +
'" class="prev-page ';
 if(page < 2 ) {;
__p += ' disabled';
 } ;
__p += '">‹</a> <input class="current-page" size="1" value="' +
((__t = (page)) == null ? '' : __t) +
'" title="' +
((__t = (labels.currentPage)) == null ? '' : __t) +
'" type="text"> ' +
((__t = ( labels.of )) == null ? '' : __t) +
' <span class="total-pages">' +
((__t = ( pages )) == null ? '' : __t) +
'</span><a class="next-page ';
 if(page == pages ) {;
__p += ' disabled ';
 } ;
__p += '" href="###" title="' +
((__t = (labels.goToNextPage)) == null ? '' : __t) +
'">›</a> <a class="last-page ';
 if(page == pages ) {;
__p += ' disabled ';
 } ;
__p += '" href="###" title="' +
((__t = (labels.goToLastPage)) == null ? '' : __t) +
'">»</a></div></div>';
return __p
  }
};

this["WPML_core"]["templates/taxonomy-translation/no-terms-found.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '';
__p += '<tr><td colspan="2"><h2 class="text-center">' +
((__t = ( message )) == null ? '' : __t) +
'</h2></td></tr>';
return __p
  }
};

this["WPML_core"]["templates/taxonomy-translation/not-translated-label.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '';
__p += '<a class="icl_tt_label lowlight" id="' +
((__t = ( taxonomy )) == null ? '' : __t) +
'_' +
((__t = ( lang )) == null ? '' : __t) +
'" title="' +
((__t = ( langs[ lang ].label )) == null ? '' : __t) +
': ' +
((__t = ( labels.addTranslation )) == null ? '' : __t) +
'"><i class="otgs-ico-add"></i></a><div id="popup-' +
((__t = ( lang )) == null ? '' : __t) +
'"></div>';
return __p
  }
};

this["WPML_core"]["templates/taxonomy-translation/original-label-disabled.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '';
__p += '<span title="' +
((__t = ( langs[ lang ].label )) == null ? '' : __t) +
': ' +
((__t = ( labels.originalLanguage )) == null ? '' : __t) +
'"><i class="otgs-ico-original"></i></span>';
return __p
  }
};

this["WPML_core"]["templates/taxonomy-translation/original-label.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '', __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
__p += '<td class="wpml-col-title"><span class="wpml-title-flag"><img src="' +
((__t = ( flag )) == null ? '' : __t) +
'"></span><strong>' +
((__t = ( taxLabel.singular + ' / ' + taxLabel.general )) == null ? '' : __t) +
'</strong><p> ';
 if(!langSelector){ ;
__p += '<a href="#" class="js-show-lang-selector">' +
((__t = ( labels.changeLanguage )) == null ? '' : __t) +
'</a>';
 } ;
__p += ' ' +
((__t = ( langSelector )) == null ? '' : __t) +
' </p></td>';
return __p
  }
};

this["WPML_core"]["templates/taxonomy-translation/original-term-popup.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '';
__p += '<div class="icl_tt_form wpml-dialog" id="icl_tt_form_' +
((__t = ( trid + '_' + lang )) == null ? '' : __t) +
'" title="' +
((__t = ( labels.originalTermPopupDialogTitle )) == null ? '' : __t) +
'"><div class="wpml-dialog-body wpml-dialog-translate"><header class="wpml-term-translation-header"><h3 class="wpml-header-original-no-translation">' +
((__t = ( labels.original )) == null ? '' : __t) +
' <span class="wpml-title-flag"><img src="' +
((__t = ( langs[ lang ].flag )) == null ? '' : __t) +
'"></span><strong>' +
((__t = ( langs[ lang ].label )) == null ? '' : __t) +
'</strong></h3></header><div class="wpml-form-row-no-translation"><label for="term-name">' +
((__t = ( labels.Name )) == null ? '' : __t) +
'</label> <input id="term-name" value="' +
((__t = ( term.name )) == null ? '' : __t) +
'" type="text"></div><div class="wpml-form-row-no-translation"><label for="term-slug">' +
((__t = ( labels.Slug )) == null ? '' : __t) +
'</label> <input id="term-slug" value="' +
((__t = ( term.slug )) == null ? '' : __t) +
'" type="text"></div><div class="wpml-form-row-no-translation"><label for="term-description">' +
((__t = ( labels.Description )) == null ? '' : __t) +
'</label> <textarea id="term-description" cols="22" rows="4">' +
((__t = ( term.description )) == null ? '' : __t) +
'</textarea></div><div class="wpml-dialog-footer"><span class="errors icl_error_text"></span> <input class="cancel wpml-dialog-close-button alignleft" value="' +
((__t = ( labels.cancel )) == null ? '' : __t) +
'" type="button"> <input class="button-primary term-save alignright" value="' +
((__t = ( labels.save )) == null ? '' : __t) +
'" type="submit"> <span class="spinner alignright"></span></div></div></div>';
return __p
  }
};

this["WPML_core"]["templates/taxonomy-translation/original-term.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '', __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
__p += '<a class="icl_tt_term_name" id="' +
((__t = (trid + '-' + lang)) == null ? '' : __t) +
'"><span class="wpml-title-flag"><img src="' +
((__t = ( langs[ lang ].flag )) == null ? '' : __t) +
'"></span><strong> ';
 if(!name){ ;
__p += ' ' +
((__t = (labels.lowercaseTranslate)) == null ? '' : __t) +
' ';
 } else {  ;
__p += ' ';
 if ( level > 0 ) { ;
__p += ' ' +
((__t = (Array(level+1).join('—') + " ")) == null ? '' : __t) +
' ';
 } ;
__p += ' ' +
((__t = (name)) == null ? '' : __t) +
' ';
 } ;
__p += ' </strong></a><div id="' +
((__t = (trid + '-popup-' + lang)) == null ? '' : __t) +
'"></div><div class="row-actions"><a class="js-copy-to-all-langs">' +
((__t = ( labels.copyToAllLanguages )) == null ? '' : __t) +
'</a></div>';
return __p
  }
};

this["WPML_core"]["templates/taxonomy-translation/ref_sync_select.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '', __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
__p += '<select id="in-lang" name="language"> ';
 _.each( langs, function( lang, code ) { ;
__p += ' <option value="' +
((__t = (code)) == null ? '' : __t) +
'">' +
((__t = ( lang.label )) == null ? '' : __t) +
'</option> ';
 }); ;
__p += ' </select>';
return __p
  }
};

this["WPML_core"]["templates/taxonomy-translation/status-trans-select.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '';
__p += '<div class="alignleft"><label for="status-select">' +
((__t = (labels.Show)) == null ? '' : __t) +
'</label> <select id="status-select" name="status"><option value="0">' +
((__t = (labels.all + ' ' + taxonomy.label)) == null ? '' : __t) +
'</option><option value="1">' +
((__t = (labels.untranslated + ' ' + taxonomy.label)) == null ? '' : __t) +
'</option></select></div>';
return __p
  }
};

this["WPML_core"]["templates/taxonomy-translation/table.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '', __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
__p += '<table class="widefat striped fixed ' +
((__t = (  ( mode !== 'sync' )? 'wpml-tt-table' : 'wpml-tt-sync-table' )) == null ? '' : __t) +
'" id="tax-table-' +
((__t = (tableType)) == null ? '' : __t) +
'"><thead><tr> ';
 if ( mode !== 'sync' ) { ;
__p += ' <th class="wpml-col-title">' +
((__t = ( firstColumnHeading )) == null ? '' : __t) +
'</th><th class="wpml-col-languages"> ';
 _.each(langs, function( lang ) { ;
__p += ' <span title="' +
((__t = ( lang.label )) == null ? '' : __t) +
'"><img src="' +
((__t = ( lang.flag )) == null ? '' : __t) +
'" alt="' +
((__t = ( lang.label )) == null ? '' : __t) +
'"></span> ';
 }); ;
__p += ' </th> ';
 } else { ;
__p += ' ';
 _.each(langs, function( lang ) { ;
__p += ' <th class="wpml-col-ttsync"><span class="wpml-title-flag"><img src="' +
((__t = ( lang.flag )) == null ? '' : __t) +
'" alt="' +
((__t = ( lang.label )) == null ? '' : __t) +
'"></span>' +
((__t = ( lang.label )) == null ? '' : __t) +
' </th> ';
 }); ;
__p += ' ';
 } ;
__p += ' </tr></thead></table>';
return __p
  }
};

this["WPML_core"]["templates/taxonomy-translation/tabs.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '', __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
__p += '<div id="term-table-tab-controls" class="wpml-tabs"><button class="nav-tab ' +
((__t = ( ( mode ==='translate' ? 'nav-tab-active' : '' ) )) == null ? '' : __t) +
'" id="term-table-header">' +
((__t = ( headerTerms )) == null ? '' : __t) +
'</button> ';
 if( taxonomy.hierarchical ) {;
__p += ' <button class="nav-tab ' +
((__t = ( ( mode ==='sync' ? 'nav-tab-active' : '' ) )) == null ? '' : __t) +
'" id="term-table-sync-header">' +
((__t = ( syncLabel )) == null ? '' : __t) +
'</button> ';
 } ;
__p += ' </div>';
return __p
  }
};

this["WPML_core"]["templates/taxonomy-translation/taxonomy-main-wrap.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '', __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
__p += '<div class="wpml-wrap"> ';
 if ( mode === 'translate' ) { ;
__p += ' <h3 id="term-table-summary">' +
((__t = ( summaryTerms )) == null ? '' : __t) +
'</h3> ';
 if ( TaxonomyTranslation.data.resultsTruncated ) { ;
__p += ' <div class="icl-admin-message-warning"><p>' +
((__t = ( resultsTruncated )) == null ? '' : __t) +
'</p></div> ';
 } ;
__p += ' <div id="wpml-taxonomy-translation-filters"></div><div id="wpml-taxonomy-translation-terms-table"></div><div id="wpml-taxonomy-translation-terms-nav"></div><h3 id="term-label-summary">' +
((__t = ( labelSummary )) == null ? '' : __t) +
'</h3> ';
 if ( TaxonomyTranslation.data.translatedTaxonomyLabels ) { ;
__p += ' <div id="wpml-taxonomy-translation-labels-table"></div> ';
 } else { ;
__p += ' <div class="otgs-notice notice notice-warning"><p>' +
((__t = ( labels.activateStringTranslation )) == null ? '' : __t) +
'</p></div> ';
 } ;
__p += ' ';
 } else if ( mode === 'sync' ) { ;
__p += ' <div id="wpml-taxonomy-translation-filters"></div> ';
 if ( hasContent ) { ;
__p += ' <div id="wpml-taxonomy-translation-terms-table"></div><div id="wpml-taxonomy-translation-terms-nav"></div><div class="wpml-tt-sync-section"><div class="wpml-tt-sync-legend"><strong>' +
((__t = ( labels.legend )) == null ? '' : __t) +
'</strong><span class="wpml-parent-added" style="background-color:#CCFF99;">' +
((__t = ( labels.willBeAdded )) == null ? '' : __t) +
'</span><span class="wpml-parent-removed" style="background-color:#F55959;">' +
((__t = ( labels.willBeRemoved )) == null ? '' : __t) +
'</span></div><div class="wpml-tt-sync-action"><input type="submit" class="button-primary button-lg" value="' +
((__t = ( labels.synchronizeBtn )) == null ? '' : __t) +
'" id="tax-apply"></div></div> ';
 } else { ;
__p += ' <h2 class="text-center">' +
((__t = ( labelSynced )) == null ? '' : __t) +
'</h2> ';
 } ;
__p += ' ';
 } ;
__p += ' </div>';
return __p
  }
};

this["WPML_core"]["templates/taxonomy-translation/term-not-synced.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '', __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
__p += '<span class="icl_tt_term_name_sync" id="' +
((__t = (trid + '-' + lang)) == null ? '' : __t) +
'"> ';
 if ( name ) { ;
__p += ' ' +
((__t = ( parent )) == null ? '' : __t) +
'<br> ';
 if ( level > 0 ) { ;
__p += ' ' +
((__t = ( Array(level+1).join('—') + " " )) == null ? '' : __t) +
' ';
 } ;
__p += ' ' +
((__t = ( name )) == null ? '' : __t) +
' ';
 } ;
__p += ' </span>';
return __p
  }
};

this["WPML_core"]["templates/taxonomy-translation/term-not-translated.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '';
__p += '<a class="icl_tt_term_name lowlight" id="' +
((__t = ( trid + '-' + lang )) == null ? '' : __t) +
'" title="' +
((__t = ( langs[ lang ].label )) == null ? '' : __t) +
': ' +
((__t = ( labels.addTranslation )) == null ? '' : __t) +
'"><i class="otgs-ico-add"></i></a><div id="' +
((__t = ( trid + '-popup-' + lang )) == null ? '' : __t) +
'"></div>';
return __p
  }
};

this["WPML_core"]["templates/taxonomy-translation/term-original-disabled.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '';
__p += '<span title="' +
((__t = ( langs[ lang ].label )) == null ? '' : __t) +
': ' +
((__t = ( labels.originalLanguage )) == null ? '' : __t) +
'"><i class="otgs-ico-original"></i></span>';
return __p
  }
};

this["WPML_core"]["templates/taxonomy-translation/term-popup.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '', __e = _.escape, __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
__p += '<div class="icl_tt_form wpml-dialog" id="icl_tt_form_' +
((__t = ( trid + '_' + lang )) == null ? '' : __t) +
'" title="' +
((__t = ( labels.termPopupDialogTitle )) == null ? '' : __t) +
'"><div class="wpml-dialog-body wpml-dialog-translate"><header class="wpml-term-translation-header"><h3 class="wpml-header-original">' +
((__t = ( labels.original )) == null ? '' : __t) +
' <span class="wpml-title-flag"><img src="' +
((__t = ( langs[ source_lang ].flag )) == null ? '' : __t) +
'"></span><strong>' +
((__t = ( langs[ source_lang ].label )) == null ? '' : __t) +
'</strong></h3><h3 class="wpml-header-translation">' +
((__t = ( labels.translationTo )) == null ? '' : __t) +
' <span class="wpml-title-flag"><img src="' +
((__t = ( langs[ lang ].flag )) == null ? '' : __t) +
'"></span><strong>' +
((__t = ( langs[ lang ].label )) == null ? '' : __t) +
'</strong></h3></header><div class="wpml-form-row"><label for="term-name">' +
((__t = ( labels.Name )) == null ? '' : __t) +
'</label> <input readonly="readonly" id="term-name-original" value="' +
((__t = ( original_term.name )) == null ? '' : __t) +
'" type="text"> <button class="button-copy button-secondary js-button-copy otgs-ico-copy" title="' +
((__t = ( labels.copyFromOriginal )) == null ? '' : __t) +
'"></button> <input id="term-name" value="' +
((__t = ( term.name )) == null ? '' : __t) +
'" type="text"></div><div class="wpml-form-row"><label for="term-slug">' +
((__t = ( labels.Slug )) == null ? '' : __t) +
'</label> <input readonly="readonly" id="term-slug-original" value="' +
((__t = ( original_term.slug )) == null ? '' : __t) +
'" type="text"> <button class="button-copy button-secondary js-button-copy otgs-ico-copy" title="' +
((__t = ( labels.copyFromOriginal )) == null ? '' : __t) +
'"></button> <input id="term-slug" value="' +
((__t = ( term.slug )) == null ? '' : __t) +
'" type="text"></div><div class="wpml-form-row"><label for="term-description">' +
((__t = ( labels.Description )) == null ? '' : __t) +
'</label> <textarea readonly="readonly" id="term-description-original" cols="22" rows="4">' +
((__t = ( original_term.description )) == null ? '' : __t) +
'</textarea> <button class="button-copy button-secondary js-button-copy otgs-ico-copy" title="' +
((__t = ( labels.copyFromOriginal )) == null ? '' : __t) +
'"></button> <textarea id="term-description" cols="22" rows="4">' +
((__t = ( term.description )) == null ? '' : __t) +
'</textarea></div> ';
 if ( original_term_meta.length ) { ;
__p += ' <hr><label>' +
((__t = ( labels.termMetaLabel)) == null ? '' : __t) +
'</label><div class="wpml-form-row"> ';
 _.each(original_term_meta, function(meta_data){
					if (Array.isArray(meta_data.meta_value)) {
						meta_data.meta_value = meta_data.meta_value.join("");
					}
				;
__p += ' <label for="term-meta">' +
((__t = ( meta_data.meta_key )) == null ? '' : __t) +
'</label> ';
 if ( meta_data.meta_value.includes('\r\n') || meta_data.meta_value.includes('\n') ) { ;
__p += ' <textarea readonly="readonly" cols="22" rows="4">' +
__e( meta_data.meta_value ) +
'</textarea> <button class="button-copy button-secondary js-button-copy otgs-ico-copy" title="' +
((__t = ( labels.copyFromOriginal )) == null ? '' : __t) +
'"></button> <textarea name="term-meta" class="term-meta" data-meta-key="' +
((__t = ( meta_data.meta_key )) == null ? '' : __t) +
'" cols="22" rows="4">' +
__e( term_meta[meta_data.meta_key] ) +
'</textarea> ';
 } else { ;
__p += ' <input readonly="readonly" value="' +
__e( meta_data.meta_value ) +
'" type="text"> <button class="button-copy button-secondary js-button-copy otgs-ico-copy" title="' +
((__t = ( labels.copyFromOriginal )) == null ? '' : __t) +
'"></button> <input name="term-meta" class="term-meta" data-meta-key="' +
((__t = ( meta_data.meta_key )) == null ? '' : __t) +
'" value="' +
__e( term_meta[meta_data.meta_key] ) +
'" type="text"> ';
 } ;
__p += ' ';
 }); ;
__p += ' </div> ';
 } ;
__p += ' </div><div class="wpml-dialog-footer"><span class="errors icl_error_text"></span> <input class="cancel wpml-dialog-close-button alignleft" value="' +
((__t = ( labels.cancel )) == null ? '' : __t) +
'" type="button"> <input class="button-primary term-save alignright" value="' +
((__t = ( labels.save )) == null ? '' : __t) +
'" type="submit"> <span class="spinner alignright"></span></div></div>';
return __p
  }
};

this["WPML_core"]["templates/taxonomy-translation/term-synced.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '', __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
__p += '<span class="icl_tt_term_name_sync" id="' +
((__t = (trid + '-' + lang)) == null ? '' : __t) +
'"> ';
 if ( name ) { ;
__p += ' ' +
((__t = ( parent )) == null ? '' : __t) +
' ';
if ( level > 0 ) { ;
__p += ' <br> ' +
((__t = ( Array(level+1).join('—') + " " )) == null ? '' : __t) +
' ';
 } ;
__p += ' ' +
((__t = ( name )) == null ? '' : __t) +
' ';
 } ;
__p += ' </span>';
return __p
  }
};

this["WPML_core"]["templates/taxonomy-translation/term-translated.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '';
__p += '<a class="icl_tt_term_name" id="' +
((__t = ( trid + '-' + lang )) == null ? '' : __t) +
'" title="' +
((__t = ( langs[ lang ].label )) == null ? '' : __t) +
': ' +
((__t = ( labels.editTranslation )) == null ? '' : __t) +
'"><i class="otgs-ico-edit"></i></a><div id="' +
((__t = ( trid + '-popup-' + lang )) == null ? '' : __t) +
'"></div>';
return __p
  }
};

this["WPML_TM"]["templates/translation-editor/footer.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '';
__p += '<div class="wpml-translation-action-buttons-abort"><button class="cancel wpml-dialog-close-button js-dialog-cancel">' +
((__t = (cancel)) == null ? '' : __t) +
'</button> <button class="button-secondary wpml-resign-button js-resign">' +
((__t = (resign)) == null ? '' : __t) +
'</button></div><div class="wpml-translation-action-buttons-status"><div class="progress-bar js-progress-bar"><div class="progress-bar-text"></div></div><label><input class="js-translation-complete" name="complete" type="checkbox">' +
((__t = (translation_complete)) == null ? '' : __t) +
'</label><div class="otgs-toggle-group"><input type="checkbox" class="js-toggle-translated otgs-switcher-input" id="wpml_tm_toggle_translated"> <label for="wpml_tm_toggle_translated" class="otgs-switcher" data-on="ON" data-off="OFF">' +
((__t = (hide_translated)) == null ? '' : __t) +
'</label></div></div><div class="wpml-translation-action-buttons-apply"><span class="js-saving-message" style="display:none"><img src="' +
((__t = (loading_url)) == null ? '' : __t) +
'" alt="' +
((__t = (saving)) == null ? '' : __t) +
'" height="16" width="16">' +
((__t = (saving)) == null ? '' : __t) +
'</span><button class="button button-primary button-large wpml-dialog-close-button js-save-and-close">' +
((__t = (save_and_close)) == null ? '' : __t) +
'</button> <button class="button button-primary button-large wpml-dialog-close-button js-save">' +
((__t = (save)) == null ? '' : __t) +
'</button></div>';
return __p
  }
};

this["WPML_TM"]["templates/translation-editor/group.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '', __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }

 if ( title ) { ;
__p +=
((__t = ( title )) == null ? '' : __t);
 } ;
__p += ' <div class="inside"></div> ';
 if ( divider ) { ;
__p += ' <hr> ';
 } ;
__p += ' <button class="button-copy button-secondary js-button-copy-group"><i class="otgs-ico-copy"></i></button>';
return __p
  }
};

this["WPML_TM"]["templates/translation-editor/header.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '';
__p +=
((__t = ( title )) == null ? '' : __t) +
' <a href="' +
((__t = ( link_url )) == null ? '' : __t) +
'" class="view" target="_blank">' +
((__t = ( link_text )) == null ? '' : __t) +
'</a>';
return __p
  }
};

this["WPML_TM"]["templates/translation-editor/image.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '', __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
__p += '<div class="inside"><img src="' +
((__t = ( image_src )) == null ? '' : __t) +
'"></div> ';
 if ( divider ) { ;
__p += ' <hr> ';
 } ;

return __p
  }
};

this["WPML_TM"]["templates/translation-editor/languages.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '';
__p += '<input type="hidden" name="source_lang" value="' +
((__t = ( language.source )) == null ? '' : __t) +
'"> <input type="hidden" name="target_lang" value="' +
((__t = ( language.target )) == null ? '' : __t) +
'"><h3 class="wpml-header-original">' +
((__t = ( labels.source_lang )) == null ? '' : __t) +
': <span class="wpml-title-flag"><img src="' +
((__t = ( language.img.source_url )) == null ? '' : __t) +
'" alt="' +
((__t = ( language.source_lang )) == null ? '' : __t) +
'"></span><strong>' +
((__t = ( language.source_lang )) == null ? '' : __t) +
'</strong></h3><h3 class="wpml-header-translation">' +
((__t = ( labels.target_lang )) == null ? '' : __t) +
': <span class="wpml-title-flag"><img src="' +
((__t = ( language.img.target_url )) == null ? '' : __t) +
'" alt="' +
((__t = ( language.target_lang )) == null ? '' : __t) +
'"></span><strong>' +
((__t = ( language.target_lang )) == null ? '' : __t) +
'</strong></h3><div class="wpml-copy-container"><button class="button-secondary button-copy-all js-button-copy-all" title="' +
((__t = ( labels.copy_from_original )) == null ? '' : __t) +
'"><i class="otgs-ico-copy"></i> ' +
((__t = ( labels.copy_all )) == null ? '' : __t) +
' </button></div>';
return __p
  }
};

this["WPML_TM"]["templates/translation-editor/note.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '';
__p += '<p>' +
((__t = ( note )) == null ? '' : __t) +
'</p>';
return __p
  }
};

this["WPML_TM"]["templates/translation-editor/section.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '', __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
__p += '<div class="handlediv button-link"><br></div><h3 class="hndle"><span>' +
((__t = ( section.title )) == null ? '' : __t) +
' ';
 if ( section.empty ) { ;
__p += '&nbsp;<i>' +
((__t = ( section.empty_message )) == null ? '' : __t);
 } ;
__p += '</i></span> ';
 if ( section.sub_title ) { ;
__p += ' <span class="subtitle"><i class="otgs-ico-warning"></i>' +
((__t = ( section.sub_title )) == null ? '' : __t) +
'</span> ';
 } ;
__p += ' </h3><div class="inside"></div>';
return __p
  }
};

this["WPML_TM"]["templates/translation-editor/single-line.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '', __e = _.escape, __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
__p += '<label>' +
((__t = (field.title)) == null ? '' : __t) +
'</label> <input readonly="readonly" class="original_value js-original-value" value="' +
__e( field.field_data ) +
'" type="text" ' +
((__t = (field.original_direction)) == null ? '' : __t) +
'> <button class="button-copy button-secondary js-button-copy icl_tm_copy_link otgs-ico-copy" id="icl_tm_copy_link_' +
((__t = (field.field_type)) == null ? '' : __t) +
'" title="' +
((__t = ( labels.copy_from_original )) == null ? '' : __t) +
'"></button> <input class="translated_value js-translated-value" name="fields[' +
((__t = (field.field_type)) == null ? '' : __t) +
'][data]" value="' +
__e( field.field_data_translated ) +
'" type="text" ' +
((__t = (field.translation_direction)) == null ? '' : __t) +
'><div class="field_translation_complete"><label><input class="icl_tm_finished js-field-translation-complete" name="fields[' +
((__t = (field.field_type)) == null ? '' : __t) +
'][finished]" type="checkbox" ';
 if (field.field_finished) { ;
__p += ' checked="checked" ';
 } ;
__p += ' >' +
((__t = (labels.translation_complete)) == null ? '' : __t) +
'</label></div> ';
 if (field.diff) { ;
__p += ' <a class="js-toggle-diff toggle-diff">' +
((__t = (labels.show_diff)) == null ? '' : __t) +
'</a> ' +
((__t = (field.diff)) == null ? '' : __t) +
' ';
 } ;
__p += ' <input type="hidden" name="fields[' +
((__t = (field.field_type)) == null ? '' : __t) +
'][tid]" value="' +
((__t = (field.tid)) == null ? '' : __t) +
'"> <input type="hidden" name="fields[' +
((__t = (field.field_type)) == null ? '' : __t) +
'][format]" value="base64">';
return __p
  }
};

this["WPML_TM"]["templates/translation-editor/textarea.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '', __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
__p += '<label>' +
((__t = (field.title)) == null ? '' : __t) +
'</label> <textarea class="original_value js-original-value" readonly="readonly" cols="22" rows="10" ' +
((__t = (field.original_direction)) == null ? '' : __t) +
'>' +
((__t = ( field.field_data )) == null ? '' : __t) +
'</textarea> <button class="button-copy button-secondary js-button-copy icl_tm_copy_link otgs-ico-copy" id="icl_tm_copy_link_' +
((__t = (field.field_type)) == null ? '' : __t) +
'" title="' +
((__t = ( labels.copy_from_original )) == null ? '' : __t) +
'"></button> <textarea class="translated_value js-translated-value" cols="22" rows="10" name="fields[' +
((__t = (field.field_type)) == null ? '' : __t) +
'][data]" ' +
((__t = (field.translation_direction)) == null ? '' : __t) +
'>' +
((__t = ( field.field_data_translated )) == null ? '' : __t) +
'</textarea><div class="field_translation_complete"><label><input class="icl_tm_finished js-field-translation-complete" name="fields[' +
((__t = (field.field_type)) == null ? '' : __t) +
'][finished]" type="checkbox" ';
 if (field.field_finished) { ;
__p += ' checked="checked" ';
 } ;
__p += ' >' +
((__t = (labels.translation_complete)) == null ? '' : __t) +
'</label></div> ';
 if (field.diff) { ;
__p += ' <a class="js-toggle-diff toggle-diff">' +
((__t = (labels.show_diff)) == null ? '' : __t) +
'</a> ' +
((__t = (field.diff)) == null ? '' : __t) +
' ';
 } ;
__p += ' <input type="hidden" name="fields[' +
((__t = (field.field_type)) == null ? '' : __t) +
'][tid]" value="' +
((__t = (field.tid)) == null ? '' : __t) +
'"> <input type="hidden" name="fields[' +
((__t = (field.field_type)) == null ? '' : __t) +
'][format]" value="base64">';
return __p
  }
};

this["WPML_TM"]["templates/translation-editor/wysiwyg.html"] = function(obj) {
  obj || (obj = {});
  with (obj) {
    var __t, __p = '', __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
__p += '<label>' +
((__t = (field.title)) == null ? '' : __t) +
'</label><div id="original_' +
((__t = (field.field_type)) == null ? '' : __t) +
'_placeholder"></div><button class="button-copy button-secondary js-button-copy icl_tm_copy_link otgs-ico-copy" id="icl_tm_copy_link_' +
((__t = (field.field_type)) == null ? '' : __t) +
'" title="' +
((__t = ( labels.copy_from_original )) == null ? '' : __t) +
'"></button><div id="translated_' +
((__t = (field.field_type)) == null ? '' : __t) +
'_placeholder"></div><input type="hidden" name="fields[' +
((__t = (field.field_type)) == null ? '' : __t) +
'][tid]" value="' +
((__t = (field.tid)) == null ? '' : __t) +
'"> <input type="hidden" name="fields[' +
((__t = (field.field_type)) == null ? '' : __t) +
'][format]" value="base64"><div class="field_translation_complete"><label><input class="icl_tm_finished js-field-translation-complete" name="fields[' +
((__t = (field.field_type)) == null ? '' : __t) +
'][finished]" type="checkbox" ';
 if (field.field_finished) { ;
__p += ' checked="checked" ';
 } ;
__p += ' >' +
((__t = (labels.translation_complete)) == null ? '' : __t) +
'</label></div> ';
 if (field.diff) { ;
__p += ' <a class="js-toggle-diff toggle-diff">' +
((__t = (labels.show_diff)) == null ? '' : __t) +
'</a> ' +
((__t = (field.diff)) == null ? '' : __t) +
' ';
 } ;

return __p
  }
};
