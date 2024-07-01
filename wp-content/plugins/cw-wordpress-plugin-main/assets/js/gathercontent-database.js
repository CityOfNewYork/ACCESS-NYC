/**
 * Content Workflow (by Bynder) - v1.0.0 - 2024-06-25
 * 
 *
 * Copyright (c) 2024 Content Workflow (by Bynder)
 * Licensed under the GPLv2 license.
 */

(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
'use strict';

window.GatherContent = window.GatherContent || {};

/**
 * These methods enable the functionality of the table and column Select elements
 * defined in \GatherContent\Importer\Admin\Mapping\Field_Types\Database
 */

(function (window, document, $) {
	/**
  * @param {HTMLElement} someElement
  * @returns {HTMLInputElement}
  */
	function getSiblingHiddenInput(someElement) {
		return someElement.parentElement.querySelector('.hidden-database-table-name');
	}

	/**
  * Clears the selected value on the column selector, and hides all options
  * that do not belong to the given table.
  *
  * @param {HTMLSelectElement} columnSelectElement
  * @param {string} tableName
  */
	function resetColumnSelector(columnSelectElement, tableName) {
		columnSelectElement.value = '';
		columnSelectElement.querySelectorAll('option').forEach(function (o) {
			if (o.getAttribute('data-tablename') === tableName) {
				o.style.display = 'block';
			} else {
				o.style.display = 'none';
			}
		});
	}

	/**
  * Update the hidden input with the given table and column values.
  * Optionally set one at a time by passing undefined for the other.
  *
  * @param {HTMLInputElement} inputElement
  * @param {string|undefined} table
  * @param {string|undefined} column
  */
	function setHiddenValue(inputElement, table, column) {
		if (!inputElement.value.includes('.')) {
			inputElement.value = '.';
		}

		var parts = inputElement.value.split('.');

		if (typeof table === 'string') {
			parts[0] = table;
		}
		if (typeof column === 'string') {
			parts[1] = column;
		}

		inputElement.value = parts.join('.');
	}

	/**
  * @param {Event} event
  */
	function columnSelectorChanged(event) {
		/** @var {HTMLSelectElement} columnSelect */
		var columnSelect = event.target;

		setHiddenValue(getSiblingHiddenInput(columnSelect), undefined, columnSelect.value);
	}

	/**
  * @param {Event} event
  */
	function tableSelectorChanged(event) {
		/** @var {HTMLSelectElement} tableSelect */
		var tableSelect = event.target;

		resetColumnSelector(tableSelect.parentElement.querySelector('.cw-column-selector'), tableSelect.value);

		setHiddenValue(getSiblingHiddenInput(tableSelect), tableSelect.value, undefined);
	}

	$(document).on('change', '.cw-table-selector', tableSelectorChanged);
	$(document).on('change', '.cw-column-selector', columnSelectorChanged);
})(window, document, jQuery);

},{}]},{},[1]);
