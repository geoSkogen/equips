'use strict'

console.log('equips-select-submit.js');

function eqSelectSubmit() {
  var formats = document.querySelectorAll('.format');
  var submit = document.querySelector('#submit');
  formats.forEach(function (format) {
    format.addEventListener("change", function () {
      submit.click();
    });
  });
}

eqSelectSubmit();
