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

		setHiddenValue(
			getSiblingHiddenInput(columnSelect),
			undefined,
			columnSelect.value
		);
	}

	/**
	 * @param {Event} event
	 */
	function tableSelectorChanged(event) {
		/** @var {HTMLSelectElement} tableSelect */
		var tableSelect = event.target;

		resetColumnSelector(
			tableSelect.parentElement.querySelector('.cw-column-selector'),
			tableSelect.value
		);

		setHiddenValue(
			getSiblingHiddenInput(tableSelect),
			tableSelect.value,
			undefined
		);
	}

	$(document).on('change', '.cw-table-selector', tableSelectorChanged);
	$(document).on('change', '.cw-column-selector', columnSelectorChanged);
})(window, document, jQuery);

