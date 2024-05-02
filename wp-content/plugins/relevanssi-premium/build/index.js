/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./src/index.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./node_modules/@babel/runtime/helpers/arrayLikeToArray.js":
/*!*****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/arrayLikeToArray.js ***!
  \*****************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _arrayLikeToArray(arr, len) {
  if (len == null || len > arr.length) len = arr.length;

  for (var i = 0, arr2 = new Array(len); i < len; i++) {
    arr2[i] = arr[i];
  }

  return arr2;
}

module.exports = _arrayLikeToArray;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/arrayWithHoles.js":
/*!***************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/arrayWithHoles.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _arrayWithHoles(arr) {
  if (Array.isArray(arr)) return arr;
}

module.exports = _arrayWithHoles;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js":
/*!**********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/assertThisInitialized.js ***!
  \**********************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _assertThisInitialized(self) {
  if (self === void 0) {
    throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
  }

  return self;
}

module.exports = _assertThisInitialized;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/classCallCheck.js":
/*!***************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/classCallCheck.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

module.exports = _classCallCheck;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/createClass.js":
/*!************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/createClass.js ***!
  \************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _defineProperties(target, props) {
  for (var i = 0; i < props.length; i++) {
    var descriptor = props[i];
    descriptor.enumerable = descriptor.enumerable || false;
    descriptor.configurable = true;
    if ("value" in descriptor) descriptor.writable = true;
    Object.defineProperty(target, descriptor.key, descriptor);
  }
}

function _createClass(Constructor, protoProps, staticProps) {
  if (protoProps) _defineProperties(Constructor.prototype, protoProps);
  if (staticProps) _defineProperties(Constructor, staticProps);
  return Constructor;
}

module.exports = _createClass;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/defineProperty.js":
/*!***************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/defineProperty.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _defineProperty(obj, key, value) {
  if (key in obj) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
  } else {
    obj[key] = value;
  }

  return obj;
}

module.exports = _defineProperty;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js":
/*!***************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/getPrototypeOf.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _getPrototypeOf(o) {
  module.exports = _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) {
    return o.__proto__ || Object.getPrototypeOf(o);
  };
  return _getPrototypeOf(o);
}

module.exports = _getPrototypeOf;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/inherits.js":
/*!*********************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/inherits.js ***!
  \*********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var setPrototypeOf = __webpack_require__(/*! ./setPrototypeOf */ "./node_modules/@babel/runtime/helpers/setPrototypeOf.js");

function _inherits(subClass, superClass) {
  if (typeof superClass !== "function" && superClass !== null) {
    throw new TypeError("Super expression must either be null or a function");
  }

  subClass.prototype = Object.create(superClass && superClass.prototype, {
    constructor: {
      value: subClass,
      writable: true,
      configurable: true
    }
  });
  if (superClass) setPrototypeOf(subClass, superClass);
}

module.exports = _inherits;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/iterableToArrayLimit.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/iterableToArrayLimit.js ***!
  \*********************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _iterableToArrayLimit(arr, i) {
  if (typeof Symbol === "undefined" || !(Symbol.iterator in Object(arr))) return;
  var _arr = [];
  var _n = true;
  var _d = false;
  var _e = undefined;

  try {
    for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) {
      _arr.push(_s.value);

      if (i && _arr.length === i) break;
    }
  } catch (err) {
    _d = true;
    _e = err;
  } finally {
    try {
      if (!_n && _i["return"] != null) _i["return"]();
    } finally {
      if (_d) throw _e;
    }
  }

  return _arr;
}

module.exports = _iterableToArrayLimit;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/nonIterableRest.js":
/*!****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/nonIterableRest.js ***!
  \****************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _nonIterableRest() {
  throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}

module.exports = _nonIterableRest;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js":
/*!**************************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js ***!
  \**************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var _typeof = __webpack_require__(/*! @babel/runtime/helpers/typeof */ "./node_modules/@babel/runtime/helpers/typeof.js");

var assertThisInitialized = __webpack_require__(/*! ./assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");

function _possibleConstructorReturn(self, call) {
  if (call && (_typeof(call) === "object" || typeof call === "function")) {
    return call;
  }

  return assertThisInitialized(self);
}

module.exports = _possibleConstructorReturn;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/setPrototypeOf.js":
/*!***************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/setPrototypeOf.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _setPrototypeOf(o, p) {
  module.exports = _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) {
    o.__proto__ = p;
    return o;
  };

  return _setPrototypeOf(o, p);
}

module.exports = _setPrototypeOf;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/slicedToArray.js":
/*!**************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/slicedToArray.js ***!
  \**************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var arrayWithHoles = __webpack_require__(/*! ./arrayWithHoles */ "./node_modules/@babel/runtime/helpers/arrayWithHoles.js");

var iterableToArrayLimit = __webpack_require__(/*! ./iterableToArrayLimit */ "./node_modules/@babel/runtime/helpers/iterableToArrayLimit.js");

var unsupportedIterableToArray = __webpack_require__(/*! ./unsupportedIterableToArray */ "./node_modules/@babel/runtime/helpers/unsupportedIterableToArray.js");

var nonIterableRest = __webpack_require__(/*! ./nonIterableRest */ "./node_modules/@babel/runtime/helpers/nonIterableRest.js");

function _slicedToArray(arr, i) {
  return arrayWithHoles(arr) || iterableToArrayLimit(arr, i) || unsupportedIterableToArray(arr, i) || nonIterableRest();
}

module.exports = _slicedToArray;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/typeof.js":
/*!*******************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/typeof.js ***!
  \*******************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _typeof(obj) {
  "@babel/helpers - typeof";

  if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") {
    module.exports = _typeof = function _typeof(obj) {
      return typeof obj;
    };
  } else {
    module.exports = _typeof = function _typeof(obj) {
      return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
    };
  }

  return _typeof(obj);
}

module.exports = _typeof;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/unsupportedIterableToArray.js":
/*!***************************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/unsupportedIterableToArray.js ***!
  \***************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var arrayLikeToArray = __webpack_require__(/*! ./arrayLikeToArray */ "./node_modules/@babel/runtime/helpers/arrayLikeToArray.js");

function _unsupportedIterableToArray(o, minLen) {
  if (!o) return;
  if (typeof o === "string") return arrayLikeToArray(o, minLen);
  var n = Object.prototype.toString.call(o).slice(8, -1);
  if (n === "Object" && o.constructor) n = o.constructor.name;
  if (n === "Map" || n === "Set") return Array.from(o);
  if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return arrayLikeToArray(o, minLen);
}

module.exports = _unsupportedIterableToArray;

/***/ }),

/***/ "./src/index.js":
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/slicedToArray */ "./node_modules/@babel/runtime/helpers/slicedToArray.js");
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_plugins__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/plugins */ "@wordpress/plugins");
/* harmony import */ var _wordpress_plugins__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_plugins__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_edit_post__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/edit-post */ "@wordpress/edit-post");
/* harmony import */ var _wordpress_edit_post__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_edit_post__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_13__);









function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }








var relevanssiIcon = wp.element.createElement("svg", {
  width: 20,
  height: 20
}, wp.element.createElement("path", {
  d: "M5.644 20.665 C6.207 20.545 6.612 20.029 6.574 19.438 6.469 17.784 6.492 16.554 6.617 15.602 L7.388 19.228 C7.454 19.538 7.576 19.815 7.737 20.058 7.742 20.12 7.749 20.181 7.763 20.243 L8.444 23.384 C10.112 23.233 11.311 22.775 11.214 23.077 L10.82 21.227 C10.875 21.218 10.929 21.211 10.984 21.199 10.995 21.197 11.004 21.193 11.015 21.191 L11.35 22.766 C11.571 22.305 13.613 22.092 14.187 21.891 L13.42 19.11 C13.529 18.742 13.553 18.346 13.466 17.936 L12.445 13.134 C12.535 13.088 12.62 13.03 12.698 12.959 12.737 12.929 12.786 12.899 12.84 12.864 13.25 12.596 14.097 12.042 14.433 10.839 L20.429 12.98 C20.642 13.056 20.862 13.067 21.069 13.023 21.456 12.941 21.792 12.667 21.934 12.267 22.154 11.655 21.835 10.981 21.222 10.763 L14.393 8.324 C14.385 8.291 14.379 8.26 14.37 8.226 14.212 7.595 13.573 7.212 12.94 7.372 12.887 7.385 12.838 7.402 12.789 7.422 12.873 6.845 12.859 6.245 12.731 5.643 12.145 2.884 9.422 1.118 6.661 1.705 3.901 2.292 2.132 5.012 2.718 7.771 3.304 10.529 6.027 12.295 8.788 11.708 10.041 11.442 11.088 10.735 11.805 9.786 11.917 9.894 12.05 9.981 12.203 10.04 12.148 10.37 11.997 10.56 11.811 10.71 10.72 11.467 10.238 11.826 9.318 12.07 L8.678 12.167 C7.581 12.344 6.407 12.307 5.457 11.871 4.141 13.689 3.972 15.683 4.221 19.589 4.263 20.238 4.823 20.73 5.473 20.688 5.531 20.685 5.589 20.677 5.644 20.665 Z M8.568 10.67 C6.38 11.135 4.222 9.735 3.758 7.55 3.293 5.364 4.695 3.208 6.883 2.743 9.07 2.278 11.229 3.677 11.693 5.863 12.158 8.049 10.755 10.205 8.568 10.67 Z"
}), wp.element.createElement("path", {
  d: "M8.009 5.745 C7.25 5.906 6.576 5.754 6.502 5.406 6.496 5.377 6.496 5.348 6.498 5.318 6.012 5.752 5.765 6.429 5.911 7.115 6.127 8.132 7.122 8.783 8.132 8.568 9.142 8.353 9.786 7.354 9.57 6.338 9.483 5.928 9.269 5.58 8.98 5.323 8.755 5.503 8.411 5.66 8.009 5.745 Z"
}));
var Fragment = wp.element.Fragment;
Object(_wordpress_plugins__WEBPACK_IMPORTED_MODULE_9__["registerPlugin"])("relevanssi-premium", {
  render: function render() {
    var _useState = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["useState"])([]),
        _useState2 = _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_6___default()(_useState, 2),
        relevanssiSees = _useState2[0],
        setRelevanssiSees = _useState2[1];

    var _useState3 = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["useState"])([]),
        _useState4 = _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_6___default()(_useState3, 2),
        relevanssiRelated = _useState4[0],
        setRelevanssiRelated = _useState4[1];

    var _useState5 = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["useState"])([]),
        _useState6 = _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_6___default()(_useState5, 2),
        relevanssiExcluded = _useState6[0],
        setRelevanssiExcluded = _useState6[1];

    var _useState7 = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["useState"])([]),
        _useState8 = _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_6___default()(_useState7, 2),
        relevanssiExcludedIds = _useState8[0],
        setRelevanssiExcludedIds = _useState8[1];

    var _useState9 = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["useState"])([]),
        _useState10 = _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_6___default()(_useState9, 2),
        relevanssiCommonTerms = _useState10[0],
        setRelevanssiCommonTerms = _useState10[1];

    var _useState11 = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["useState"])([]),
        _useState12 = _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_6___default()(_useState11, 2),
        relevanssiLowRankingTerms = _useState12[0],
        setRelevanssiLowRankingTerms = _useState12[1];

    var regenerateRelatedPosts = function regenerateRelatedPosts(postId, metaKey, metaValue) {
      if (!metaValue) metaValue = "0";
      wp.apiFetch({
        path: "/relevanssi/v1/regeneraterelatedposts/".concat(postId, "/").concat(metaKey, "/").concat(metaValue)
      }).then(function (data) {
        setRelevanssiRelated(createRelatedList(data));
      });
    };

    var RelatedPostControl = Object(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["withFocusOutside"])( /*#__PURE__*/function (_React$Component) {
      _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default()(_class, _React$Component);

      var _super = _createSuper(_class);

      function _class() {
        _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, _class);

        return _super.apply(this, arguments);
      }

      _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(_class, [{
        key: "handleFocusOutside",
        value: function handleFocusOutside() {
          regenerateRelatedPosts(wp.data.select("core/editor").getCurrentPostId(), this.props.metaKey, wp.data.select("core/editor").getEditedPostAttribute("meta")[this.props.metaKey]);
        }
      }, {
        key: "render",
        value: function render() {
          return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(MetaControl, {
            control: this.props.control,
            title: this.props.title,
            metaKey: this.props.metaKey
          });
        }
      }]);

      return _class;
    }(React.Component));
    var MetaControl = Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_13__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_12__["withDispatch"])(function (dispatch, props) {
      return {
        setMetaValue: function setMetaValue(metaValue) {
          dispatch("core/editor").editPost({
            meta: _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()({}, props.metaKey, metaValue)
          });
        }
      };
    }), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_12__["withSelect"])(function (select, props) {
      var metaValue = select("core/editor").getEditedPostAttribute("meta")[props.metaKey];
      if (metaValue === "0") metaValue = "";
      return {
        metaValue: metaValue
      };
    }))(function (props) {
      var args = {
        label: props.title,
        value: props.metaValue,
        onChange: function onChange(content) {
          props.setMetaValue(content);
        }
      };

      if (props.control == _wordpress_components__WEBPACK_IMPORTED_MODULE_8__["CheckboxControl"]) {
        var checked = props.metaValue == "on" ? true : false;
        args.value = "";
        args.checked = checked;

        args.onChange = function (content) {
          content = content ? "on" : "off";
          props.setMetaValue(content);
        };
      }

      return wp.element.createElement(props.control, args);
    });

    var excludeRelatedPost = function excludeRelatedPost(excludedPostId, postId) {
      wp.apiFetch({
        path: "/relevanssi/v1/excluderelatedpost/".concat(excludedPostId, "/").concat(postId)
      }).then(function (data) {
        setRelevanssiExcludedIds(data);
      });
    };

    var unExcludeRelatedPost = function unExcludeRelatedPost(excludedPostId, postId) {
      wp.apiFetch({
        path: "/relevanssi/v1/unexcluderelatedpost/".concat(excludedPostId, "/").concat(postId)
      }).then(function (data) {
        setRelevanssiExcludedIds(data);
      });
    };

    var postId = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_12__["select"])("core/editor").getCurrentPostId();
    Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["useEffect"])(function () {
      wp.apiFetch({
        path: "/relevanssi/v1/sees/".concat(postId)
      }).then(function (data) {
        setRelevanssiSees(data);
      });
    }, [postId]);
    /*<MetaButton excludedPostId={row.id} postId={postId} />
    							*/

    var createRelatedList = function createRelatedList(data) {
      return data.map(function (row) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("li", {
          key: row.id
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("a", {
          href: row.link
        }, row.title), " ", Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["Button"], {
          onClick: function onClick() {
            return excludeRelatedPost(row.id, postId);
          }
        }, "(", Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("not this", "relevanssi"), ")"));
      });
    };

    Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["useEffect"])(function () {
      wp.apiFetch({
        path: "/relevanssi/v1/listrelated/".concat(postId)
      }).then(function (data) {
        setRelevanssiRelated(createRelatedList(data));
      });
    }, [postId, relevanssiExcludedIds]);
    Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["useEffect"])(function () {
      wp.apiFetch({
        path: "/relevanssi/v1/listexcluded/".concat(postId)
      }).then(function (data) {
        var list = data.map(function (row) {
          return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("li", {
            key: row.id
          }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("a", {
            href: row.link
          }, row.title), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["Button"], {
            onClick: function onClick() {
              return unExcludeRelatedPost(row.id, postId);
            }
          }, "(", Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("use this", "relevanssi"), ")"));
        });
        setRelevanssiExcluded(list);
      });
    }, [postId, relevanssiExcludedIds]);

    var createTermsList = function createTermsList(data) {
      return data.map(function (row) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("li", {
          key: row.id
        }, row.query, " (", row.count, ")");
      });
    };

    Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["useEffect"])(function () {
      wp.apiFetch({
        path: "/relevanssi/v1/listinsightscommon/".concat(postId)
      }).then(function (data) {
        setRelevanssiCommonTerms(createTermsList(data));
      });
    }, [postId]);
    Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["useEffect"])(function () {
      wp.apiFetch({
        path: "/relevanssi/v1/listinsightslowranking/".concat(postId)
      }).then(function (data) {
        setRelevanssiLowRankingTerms(createTermsList(data));
      });
    }, [postId]);
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(Fragment, null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_edit_post__WEBPACK_IMPORTED_MODULE_10__["PluginSidebarMoreMenuItem"], {
      target: "relevanssi-premium",
      icon: relevanssiIcon
    }, "Relevanssi Premium"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_edit_post__WEBPACK_IMPORTED_MODULE_10__["PluginSidebar"], {
      name: "relevanssi-premium",
      icon: relevanssiIcon,
      title: "Relevanssi Premium"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["Panel"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["PanelBody"], {
      initialOpen: false,
      title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("How Relevanssi sees this post", "relevanssi")
    }, relevanssiSees.title && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("p", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("strong", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("Title:", "relevanssi")), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("br", null), relevanssiSees.title), relevanssiSees.content && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("p", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("strong", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("Content:", "relevanssi")), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("br", null), relevanssiSees.content), relevanssiSees.author && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("p", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("strong", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("Author:", "relevanssi")), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("br", null), relevanssiSees.author), relevanssiSees.category && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("p", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("strong", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("Categories:", "relevanssi")), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("br", null), relevanssiSees.category), relevanssiSees.tag && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("p", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("strong", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("Tags:", "relevanssi")), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("br", null), relevanssiSees.tag), relevanssiSees.taxonomy && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("p", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("strong", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("Other taxonomies:", "relevanssi")), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("br", null), relevanssiSees.taxonomy), relevanssiSees.comment && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("p", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("strong", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("Comments:", "relevanssi")), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("br", null), relevanssiSees.comment), relevanssiSees.customfield && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("p", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("strong", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("Custom fields:", "relevanssi")), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("br", null), relevanssiSees.customfield), relevanssiSees.excerpt && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("p", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("strong", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("Excerpt:", "relevanssi")), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("br", null), relevanssiSees.excerpt), relevanssiSees.link && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("p", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("strong", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("Links to this post:", "relevanssi")), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("br", null), relevanssiSees.link), relevanssiSees.mysql && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("p", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("strong", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("MySQL columns:", "relevanssi")), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("br", null), relevanssiSees.mysql), relevanssiSees.reason && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("p", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("strong", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("Reason this post is not indexed:", "relevanssi")), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("br", null), relevanssiSees.reason))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["Panel"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["PanelBody"], {
      initialOpen: false,
      title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("Pinning", "relevanssi")
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["PanelRow"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(MetaControl, {
      control: _wordpress_components__WEBPACK_IMPORTED_MODULE_8__["CheckboxControl"],
      title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("Pin this post for all searches it appears in.", "relevanssi"),
      metaKey: "_relevanssi_pin_for_all"
    })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["PanelRow"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(MetaControl, {
      control: _wordpress_components__WEBPACK_IMPORTED_MODULE_8__["TextareaControl"],
      title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("A comma-separated list of single word keywords or multi-word phrases.", "relevanssi"),
      metaKey: "_relevanssi_pin_keywords"
    })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["PanelRow"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("p", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("If any of these keywords are present in the search query, this post will be moved on top of the search results.", "relevanssi"))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["PanelRow"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("p", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("You can add weights to pinned keywords like this: 'keyword (100)'. The post with the highest weight will be sorted first if there are multiple posts pinned to the same keyword.", "relevanssi")))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["PanelBody"], {
      initialOpen: false,
      title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("Exclusion", "relevanssi")
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["PanelRow"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(MetaControl, {
      control: _wordpress_components__WEBPACK_IMPORTED_MODULE_8__["CheckboxControl"],
      title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("Exclude this post or page from the index.", "relevanssi"),
      metaKey: "_relevanssi_hide_post"
    })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["PanelRow"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(MetaControl, {
      control: _wordpress_components__WEBPACK_IMPORTED_MODULE_8__["CheckboxControl"],
      title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("Ignore post content in the indexing.", "relevanssi"),
      metaKey: "_relevanssi_hide_content"
    })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["PanelRow"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(MetaControl, {
      control: _wordpress_components__WEBPACK_IMPORTED_MODULE_8__["TextareaControl"],
      title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("A comma-separated list of single word keywords or multi-word phrases.", "relevanssi"),
      metaKey: "_relevanssi_unpin_keywords"
    })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["PanelRow"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("p", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("If any of these keywords are present in the search query, this post will be removed from the search results.", "relevanssi"))))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["Panel"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["PanelBody"], {
      initialOpen: false,
      title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("Related posts", "relevanssi")
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["PanelRow"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(MetaControl, {
      control: _wordpress_components__WEBPACK_IMPORTED_MODULE_8__["CheckboxControl"],
      title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("Don't append the related posts to this page.", "relevanssi"),
      metaKey: "_relevanssi_related_no_append"
    })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["PanelRow"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(MetaControl, {
      control: _wordpress_components__WEBPACK_IMPORTED_MODULE_8__["CheckboxControl"],
      title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("Don't show this as a related post for any post.", "relevanssi"),
      metaKey: "_relevanssi_related_not_related"
    })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["PanelRow"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(RelatedPostControl, {
      control: _wordpress_components__WEBPACK_IMPORTED_MODULE_8__["TextareaControl"],
      title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("A comma-separated list of keywords to use for the Related Posts feature.", "relevanssi"),
      metaKey: "_relevanssi_related_keywords"
    })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["PanelRow"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("p", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("Anything entered here will used when searching for related posts. Using phrases with quotes is allowed, but will restrict the related posts to posts including that phrase.", "relevanssi"))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["PanelRow"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(RelatedPostControl, {
      control: _wordpress_components__WEBPACK_IMPORTED_MODULE_8__["TextControl"],
      title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("A comma-separated list of post IDs to use as related posts for this post", "relevanssi"),
      metaKey: "_relevanssi_related_include_ids"
    })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("p", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("Related posts for this post:", "relevanssi")), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("ol", null, relevanssiRelated), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("p", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("Excluded posts for this post:", "relevanssi")), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("ol", null, relevanssiExcluded))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["Panel"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["PanelBody"], {
      initialOpen: false,
      title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("Insights", "relevanssi")
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("p", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("The most common search terms used to find this post:", "relevanssi")), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("ol", null, relevanssiCommonTerms), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("p", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("Low-ranking search terms used to find this post:", "relevanssi")), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("ol", null, relevanssiLowRankingTerms)))));
  }
});

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["components"]; }());

/***/ }),

/***/ "@wordpress/compose":
/*!*********************************!*\
  !*** external ["wp","compose"] ***!
  \*********************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["compose"]; }());

/***/ }),

/***/ "@wordpress/data":
/*!******************************!*\
  !*** external ["wp","data"] ***!
  \******************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["data"]; }());

/***/ }),

/***/ "@wordpress/edit-post":
/*!**********************************!*\
  !*** external ["wp","editPost"] ***!
  \**********************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["editPost"]; }());

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["element"]; }());

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["i18n"]; }());

/***/ }),

/***/ "@wordpress/plugins":
/*!*********************************!*\
  !*** external ["wp","plugins"] ***!
  \*********************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["plugins"]; }());

/***/ })

/******/ });
//# sourceMappingURL=index.js.map