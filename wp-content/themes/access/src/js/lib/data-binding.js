/* eslint-env browser */
'use strict';

import _ from 'underscore';

/**
 * Simple functionality for binding object data to the dowm.
 * Author: Devon Hirth, NYC Opportunity
 *
 *   Bind input values;
 *     <input data-val="_household.members" type="number">
 *
 *   Bind text to dom nodes;
 *     <div data-bind="_household.members"></div>
 *
 *   Toggle classes;
 *     <div data-class="(_household.members === 1)?'c-red':'c-purple'"></div>
 *
 *   Repeating content (requires underscore templating);
 *     <div data-for="p in _household.people" aria-hidden="true">
 *      <script type="text/template">
 *        <h1><%= p.name %></h1>
 *      </script>
 *     </div>
 *
 */
class DataBinding {
  /**
   * @constructor
   * @param {string} name      - the name of the parent node element
   * @param {object} callbacks - collection of functions to hook into
   *                             data-binding
   */
  constructor(name, callbacks = {'compile': ()=>{}}) {
    /**
     * Create a key storage
     * @type {Object}
     */
    this._keys = {
      BIND: 'bind',
      VAL: 'val',
      FOR: 'for',
      CLASS: 'class',
      COMPILE: 'compile',
      MANIPULATE: 'manipulate',
      FOR_INDEX: 'index',
      SCOPE: 'scope',
      ATTRS: 'attrs'
    };

    this._callbacks = callbacks;

    /**
     * Store for compiling functions
     * @type {Array}
     */
    this._compileCycle = [
      this._keys.FOR
    ];

    /**
     * The namespaces for our attribute funtions
     * @type {Array}
     */
    this._manipulateCycle = [
      this._keys.VAL,
      this._keys.BIND,
      this._keys.CLASS
    ];

    /**
     * Set the scope
     * @return {object} node list of the scope
     */
    this._scope = () => {
      return document.querySelectorAll(`[data-scope="${this._name}"]`);
    };

    /**
     * Set the name of the scope
     * @type {string}
     */
    if (name)
      this._name = name;

    /**
     * Return the model public path. Does not support nested attrs
     * @param  {string} key - the model attribute
     * @return {string}     - the path joined by '.'
     */
    this._path = function(key) {
      return [this._name, key].join('.');
    };

    /**
     * Query and return dom elements
     * @param  {string} key   - the parameter key of the data
     * @param  {array}  cycle - the cycle list of namespaces to return
     * @return {object}       - collection of elements
     */
    this._selections = (key, cycle = this._manipulateCycle) => {
      let collection = {};
      let dictionary = {
        'bind': `[data-${this._keys.BIND}='${key}']`,
        'val': `[data-${this._keys.VAL}='${key}']`,
        'for': `[data-${this._keys.FOR}*='${key}']`,
        'class': `[data-${this._keys.CLASS}*='${key}']`
      };
      let scope = this._scope();
      // preset the collection for concantenation
      for (let i = cycle.length - 1; i >= 0; i--) {
        collection[cycle[i]] = [];
      }
      // for each scope, find each bound value within the cycle
      _.each(scope, (value, index) => {
        for (let i = cycle.length - 1; i >= 0; i--) {
          let subCollection = collection[cycle[i]];
          let nodelist = value.querySelectorAll(dictionary[cycle[i]]);
          if (nodelist.length) {
            // convert nodelist into an array to properly concantenate
            nodelist = Array.prototype.slice.call(nodelist);
            collection[cycle[i]] = subCollection.concat(nodelist);
          }
        }
      });
      return collection;
    };

    /**
     * Set bound dom inner text
     * @param {dom} element                       - the element to set
     * @param {string} key                        - the key of the changed value
     * @param {string|number|boolean|array} value - the value to set the
     *                                              innerText of the node
     */
    this._bind = function(element, key, value) {
      if (element.innerText !== value.toString()) {
        element.innerText = value;
      }
    };

    /**
     * Set bound value unless already set (case would be the original input)
     * @param {dom} element                       - the element to set
     * @param {string} key                        - the key of the changed value
     * @param {string|number|boolean|array} value - the value to set the element
     */
    this._val = function(element, key, value) {
      if (element.type === 'number' && element.value !== parseInt(value)) {
        element.value = value;
      }
      if (element.type === 'radio' && element.value === value.toString()) {
        element.checked = true;
      }
      if (element.type === 'select-one' && element.value !== value) {
        element.value = value;
      }
      if (element.type === 'checkbox' && element.value === value.toString()) {
        element.checked = true;
      }
    };

    /**
     * Create new DOM in a for loop element
     * @param {dom} element      - the dom element with the loop parameters
     * @param {collection} value - the data for the loop content
     */
    this._for = function(element, key, value) {
      let loop = [];
      let length = 0;

      // if the loop is based on a number
      if (typeof this._attrs[key] === 'number') {
        loop[0] = key;
        loop[1] = key;
        length = this._attrs[key];
      // if the loop is based on a collection
      } else if (typeof this._attrs[key] === 'object') {
        loop = element.dataset[this._keys.FOR].split(' in ');
        length = value.length;
      }

      // create classname and remove any previous elements for a clean slate
      let classname = `_for_${loop[1].replace('.', '__')}`;
      // console.dir(element.parentNode.)
      let previous = element.parentNode.querySelectorAll(`.${classname}`);
      for (let i = previous.length - 1; i >= 0; i--) {
        previous[i].remove();
      }

      // get the element's next sibling for the end of the loop block
      let nextSibling = element.nextSibling;

      // placeholders for the cloned elements
      let obj = {};
      let subkey = loop[0]; // new scope prefix
      let clone = element;

      for (let i = 0; i <= length - 1; i++) {
        // set the data for the template
        obj[subkey] = (value[i]) ? value[i] : {'length': value};
        obj[subkey][this._keys.FOR_INDEX] = i; // set the index for the data
        clone = element.cloneNode(); // clone template
        let template = element.firstElementChild;
        clone.innerHTML = _.template(template.innerHTML)(obj);
        clone.setAttribute('class', classname);
        clone.removeAttribute('aria-hidden');
        // creat a new dataset for the element
        delete clone.dataset.for; // delete the loop
        _.each(value[i], (dataValue, dataKey) => {
          clone.dataset[dataKey] = dataValue;
        });
        element.parentNode.insertBefore(clone, nextSibling);
      }
    };

    /**
     * Evaluate class ternary operators
     * @param {dom} element - the element with classes to toggle
     */
    this._class = function(element, key, value) {
      let ternary = element.dataset.class;
      let classnames = ternary.split('?')[1].replace(/'/g, '').split(':');
      // console.dir(value);
      let evaluation = eval(
        ternary.replace(key, `this._attrs.${key}`)
      ); // evaluate the conditional statement
      let index = classnames.indexOf(evaluation);
      let removeClass = classnames[((index) ? 0 : 1)].split(' ');
      let addClass = classnames[index].split(' ');
      for (let i = removeClass.length - 1; i >= 0; i--) {
        if (removeClass[i] !== '')
          element.classList.remove(removeClass[i]);
      } // remove old classes
      for (let i = addClass.length - 1; i >= 0; i--) {
        if (addClass[i] !== '')
          element.classList.add(addClass[i]);
      } // add new classes
    };

    /**
     * Set the dom based on updates to the model. Returns boolean on wether
     * setting has run or not.
     * @param  {string} key                        - the attr ket to be set
     * @param  {string|number|boolean|array} value - the value to set the key to
     * @param  {string} cycle                      - the cycle to run
     * @return {boolean}                           - if the cycle has run
     */
    this._setDom = (key, value, cycle) => {
      let bool = false;
      let fnCycle = `_${cycle}Cycle`;
      let selections = this._selections(key, this[fnCycle]);
      // run through each namespace
      _.each(this[fnCycle], (namespace) => {
        // for each selection assoc. with a namespace, run the function
        for (let i = selections[namespace].length - 1; i >= 0; i--) {
          this[`_${namespace}`](selections[namespace][i], key, value);
          bool = true;
        }
      });
      return bool;
    };

    /**
     * The cycle management function
     * @param {string} key                        - the attr ket to be set
     * @param {string|number|boolean|array} value - the value to set the key to
     */
    this._cycle = (key, value) => {
      // run attribute through compiling, if an attr requires compiling
      if (this._setDom(key, value, this._keys.COMPILE)) {
        // run the callback for compiling
        if (this._callbacks[this._keys.COMPILE]) {
          this._callbacks[this._keys.COMPILE]({
            'attr': key,
            'value': value,
            'object': this
          });
        }
        // then run dom manipulation for each attr
        this._eachAttrsCycle(this._keys.MANIPULATE);
        // proxy the new dom
        this._eachAttrsProxy();
        return;
      }
      // else attr is a manipulate function so run manipulate
      this._setDom(key, value, this._keys.MANIPULATE);
    };

    /**
     * Runs a new cycle on each attribute
     * @param  {string} cycle - the cycle to run
     */
    this._eachAttrsCycle = (cycle) => {
      _.each(this._attrs, (value, key) => {
        this._setDom(key, value, cycle);
      });
    };

    /**
     * Sets an individual attribute, matching for type.
     * @param  {string} key                        -
     * @param  {string|number|boolean|array} value -
     */
    this._setAttr = function(key, value) {
      if (
        key in this._attrs &&
        typeof this._attrs[key] === typeof value
      ) {
        this._attrs[key] = value;
        // if the model is changing, set the DOM
        this._cycle(key, value);
      } else {
        // if not, set to default
        this._cycle(key, this._defaults[key]);
      }
    };

    /**
     * Runs a new proxy on each attribute
     * @param {object} attrs - the collection of attributes to proxy
     */
    this._eachAttrsProxy = (attrs = this._attrs) => {
      _.each(attrs, (value, key) => {
        this._proxyDom(key, value);
      });
    };

    /**
     * Proxy the dom to change to the model
     * @param {string} key                        -
     * @param {string|number|boolean|array} value -
     */
    this._proxyDom = function(key, value) {
      let namespaces = this._manipulateCycle;
      let selections = this._selections(key, namespaces);

      for (let i = selections[this._keys.VAL].length - 1; i >= 0; i--) {
        let input = selections[this._keys.VAL][i];
        if (input.type === 'number') {
          input.addEventListener('change', (event) => {
            // ADD VALIDATION HERE!?
            this._setAttr(key, parseInt(event.currentTarget.value));
          });
        }
        if (input.type === 'radio') {
          input.addEventListener('change', (event) => {
            // ADD VALIDATION HERE!?
            let boolean = (event.currentTarget.value === 'true');
            this._setAttr(key, boolean);
          });
        }
        if (input.type === 'select-one') {
          input.addEventListener('change', (event) => {
            // ADD VALIDATION HERE!?
            this._setAttr(key, event.currentTarget.value);
          });
        }
        if (input.type === 'checkbox') {
          input.addEventListener('change', (event) => {
            // ADD VALIDATION HERE!?
            let value = event.currentTarget.value;
            let boolean = (value == 'true');
            value = (boolean) ? event.currentTarget.checked : value;
            this._setAttr(key, value);
          });
        }
      }
    };

    /**
     * Initialize the DOM proxy for each
     */
    this._init = function() {
      // We need to run functions that compile the dom before initial proxy
      this._eachAttrsCycle(this._keys.COMPILE);
      // Then we can proxy the dom and run the rest of the functions
      _.each(this._attrs, (value, key) => {
        this._cycle(key, value); // set initial dom value
        this._proxyDom(key, value);
      });
    };
  }

  /**
   * Initialize the DOM proxy for each
   * @return {object} the class object
   */
  init() {
    this._init();
    return this;
  }

}

export default DataBinding;

