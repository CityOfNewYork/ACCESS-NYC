'use strict'; // For safari, and IE > 10

/**
 * Polyfill for Element.prototype.replaceWith();
 * @src https://developer.mozilla.org/en-US/docs/Web/API/ChildNode/replaceWith#Polyfill
 */

function ReplaceWithPolyfill() {
  var parent = this.parentNode;
  var i = arguments.length;
  var currentNode;

  if (!parent) return;

  // if there are no arguments
  if (!i) parent.removeChild(this);

  while (i--) { // i-- decrements i and returns the value of i before the decrement
    currentNode = arguments[i];

    if (typeof currentNode !== 'object') {
      currentNode = this.ownerDocument.createTextNode(currentNode);
    } else if (currentNode.parentNode) {
      currentNode.parentNode.removeChild(currentNode);
    }

    // the value of "i" below is after the decrement
    if (!i) {
      // if currentNode is the first argument (currentNode === arguments[0])
      parent.replaceChild(currentNode, this);
    } else {
      // if currentNode isn't the first
      parent.insertBefore(currentNode, this.nextSibling);
    }
  }
}

if (!Element.prototype.replaceWith) {
  Element.prototype.replaceWith = ReplaceWithPolyfill;
}

if (!CharacterData.prototype.replaceWith) {
  CharacterData.prototype.replaceWith = ReplaceWithPolyfill;
}

if (!DocumentType.prototype.replaceWith) {
  DocumentType.prototype.replaceWith = ReplaceWithPolyfill;
}
