if(typeof Object.assign != 'function'){
  (function () {
    Object.assign = function (target) {
      'use strict';
      // We must check against these specific cases.
      if(target === undefined || target === null){
        throw new TypeError('Cannot convert undefined or null to object');
      }

      var output = Object(target);
      for (var index = 1; index < arguments.length; index++) {
        var source = arguments[index];
        if(source !== undefined && source !== null){
          for (var nextKey in source) {
            if(source.hasOwnProperty(nextKey)){
              output[nextKey] = source[nextKey];
            }
          }
        }
      }
      return output;
    };
  })();
}

function ReplaceWith(Ele) {
  'use-strict'; // For safari, and IE > 10
  var parent = this.parentNode,
    i = arguments.length,
    firstIsNode = +(parent && typeof Ele === 'object');
  if(!parent) return;

  while (i-- > firstIsNode){
    if(parent && typeof arguments[i] !== 'object'){
      arguments[i] = document.createTextNode(arguments[i]);
    }
    if(!parent && arguments[i].parentNode){
      arguments[i].parentNode.removeChild(arguments[i]);
      continue;
    }
    parent.insertBefore(this.previousSibling, arguments[i]);
  }
}

if(!Element.prototype.replaceWith)
  Element.prototype.replaceWith = ReplaceWith;
if(!CharacterData.prototype.replaceWith)
  CharacterData.prototype.replaceWith = ReplaceWith;
if(!DocumentType.prototype.replaceWith)
  CharacterData.prototype.replaceWith = ReplaceWith;

if(!Element.prototype.matches)
  Element.prototype.matches = Element.prototype.msMatchesSelector ||
    Element.prototype.webkitMatchesSelector;

if(!Element.prototype.closest){
  Element.prototype.closest = function (s) {
    var el = this;
    if(!document.documentElement.contains(el)) return null;
    do {
      if(el.matches(s)) return el;
      el = el.parentElement || el.parentNode;
    } while (el !== null && el.nodeType === 1);
    return null;
  };
}