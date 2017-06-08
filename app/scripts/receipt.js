//Receipt.js
'use strict';

//This file should get the return parameters and display the text on the screen
//to the user

var urlParams = location.search.split(/[?&]/).slice(1).map(function(paramPair) {
  return paramPair.split(/=(.+)?/).slice(0, 2);
}).reduce(function (obj, pairArray) {
  obj[pairArray[0]] = pairArray[1];
  return obj;
}, {});

var returnText = '';

if ( urlParams.r ){
  returnText = JSON.parse(atob(urlParams.r));
} else {
  returnText = 'Error: Please contact us for assistance before trying again.';
}
console.log(returnText);

document.addEventListener('DOMContentLoaded', function(event){
  var returnElement = document.getElementById('return-text');
  returnElement.innerHTML = returnText.message;
});
