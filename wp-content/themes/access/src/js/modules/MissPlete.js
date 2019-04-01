/* eslint-env browser */
'use strict';

import jaroWinkler from './jaroWinkler.js';
import memoize from './memoize.js';

/**
 * MissPlete for autocomplete.
 * https://github.com/devowhippit/miss-plete-js
 */
class MissPlete {
 /**
 * class Constructor MissPlete.
 */
  constructor({
    input,
    options,
    className,
    scoreFn = memoize(MissPlete.scoreFn),
    listItemFn = MissPlete.listItemFn
  }) {
    Object.assign(this, {input, options, className, scoreFn, listItemFn});
    this.scoredOptions = null;
    this.container = null;
    this.ul = null;
    this.highlightedIndex = -1;

    this.input.addEventListener('input', () => {
      if (this.input.value.length > 0) {
        this.scoredOptions = this.options
          .map((option) => scoreFn(this.input.value, option))
          .sort((a, b) => b.score - a.score);
      } else {
        this.scoredOptions = [];
      }
      this.renderOptions();
    });

    this.input.addEventListener('keydown', (event) => {
      if (this.ul) {  // dropdown visible?
        switch (event.keyCode) {
          case 13:
            this.select();
            break;
          case 27:  // Esc
            this.removeDropdown();
            break;
          case 40:  // Down arrow
            // Otherwise up arrow places the cursor at the beginning of the
            // field, and down arrow at the end
            event.preventDefault();
            this.changeHighlightedOption(
              this.highlightedIndex < this.ul.children.length - 1
              ? this.highlightedIndex + 1
              : -1
            );
            break;
          case 38:  // Up arrow
            event.preventDefault();
            this.changeHighlightedOption(
              this.highlightedIndex > -1
              ? this.highlightedIndex - 1
              : this.ul.children.length - 1
            );
            break;
        }
      }
    });

    this.input.addEventListener('blur', (event) => {
      this.removeDropdown();
      this.highlightedIndex = -1;
    });
  }  // end constructor

 /**
 * It must return an object with at least the properties `score` and
 * `displayValue`
 * @param {array} inputValue
 * @param {array} optionSynonyms
 * Default is a Jaro–Winkler similarity function.
 * @return {int} score or displayValue
 */
  static scoreFn(inputValue, optionSynonyms) {
    let closestSynonym = null;
    for (let synonym of optionSynonyms) {
      let similarity = jaroWinkler(
        synonym.trim().toLowerCase(),
        inputValue.trim().toLowerCase()
      );
      if (closestSynonym === null || similarity > closestSynonym.similarity) {
        closestSynonym = {similarity, value: synonym};
        if (similarity === 1) {
          break;
        }
      }
    }
    return {
      score: closestSynonym.similarity,
      displayValue: optionSynonyms[0]
    };
  }

  /**
 * Maximum amount of results to be returned.
 */
  static get MAX_ITEMS() {
    return 5;
  }

  /**
  * List item for dropdown list.
  * @param {Number} scoredOption
  * @param {Number} itemIndex
  * @return {string} The a list item <li>.
  */
  static listItemFn(scoredOption, itemIndex) {
    const li = itemIndex > MissPlete.MAX_ITEMS
      ? null
      : document.createElement('li');
    li && li.appendChild(document.createTextNode(scoredOption.displayValue));
    return li;
  }

  /**
  * Get index of previous element.
  * @param {array} node
  * @return {number} index of previous element.
  */
  getSiblingIndex(node) {
    let index = -1;
    let n = node;
    do {
      index++;
      n = n.previousElementSibling;
    } while (n);
    return index;
  }

  /**
  * Display options as a list.
  */
  renderOptions() {
    const documentFragment = document.createDocumentFragment();

    this.scoredOptions.every((scoredOption, i) => {
      const listItem = this.listItemFn(scoredOption, i);
      listItem && documentFragment.appendChild(listItem);
      return !!listItem;
    });

    this.removeDropdown();
    this.highlightedIndex = -1;

    if (documentFragment.hasChildNodes()) {
      const newUl = document.createElement('ul');
      newUl.addEventListener('mouseover', (event) => {
        if (event.target.tagName === 'LI') {
          this.changeHighlightedOption(this.getSiblingIndex(event.target));
        }
      });

      newUl.addEventListener('mouseleave', () => {
        this.changeHighlightedOption(-1);
      });

      newUl.addEventListener('mousedown', (event) => event.preventDefault());

      newUl.addEventListener('click', (event) => {
        if (event.target.tagName === 'LI') {
          this.select();
        }
      });

      newUl.appendChild(documentFragment);

      // See CSS to understand why the <ul> has to be wrapped in a <div>
      const newContainer = document.createElement('div');
      newContainer.className = this.className;
      newContainer.appendChild(newUl);

      // Inserts the dropdown just after the <input> element
      this.input.parentNode.insertBefore(newContainer, this.input.nextSibling);
      this.container = newContainer;
      this.ul = newUl;
    }
  }

  /**
  * Highlight new option selected.
  * @param {Number} newHighlightedIndex
  */
  changeHighlightedOption(newHighlightedIndex) {
    if (newHighlightedIndex >= -1 &&
        newHighlightedIndex < this.ul.children.length) {
      // If any option already selected, then unselect it
      if (this.highlightedIndex !== -1) {
        this.ul.children[this.highlightedIndex].classList.remove('highlight');
      }

      this.highlightedIndex = newHighlightedIndex;

      if (this.highlightedIndex !== -1) {
        this.ul.children[this.highlightedIndex].classList.add('highlight');
      }
    }
  }

  /**
  * Selects an option from a list of items.
  */
  select() {
    if (this.highlightedIndex !== -1) {
      this.input.value = this.scoredOptions[this.highlightedIndex].displayValue;
      this.removeDropdown();
    }
  }

  /**
  * Remove dropdown list once a list item is selected.
  */
  removeDropdown() {
    this.container && this.container.remove();
    this.container = null;
    this.ul = null;
  }

}


export default MissPlete;
