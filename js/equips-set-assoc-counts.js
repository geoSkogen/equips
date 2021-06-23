console.log('equips-set-assoc-counts');

function eqSetAssocCounts() {
  var submit = document.querySelector("#submit");
  var submit_top = document.querySelector("#submit_top");


  submit.addEventListener('click', eqSetCount);
  submit_top.addEventListener('click', eqSetCount);


  function eqSetCount() {
    var val = 0;
    var counts = document.querySelectorAll(".eq_assoc_count");
    var divs = document.querySelectorAll(".img_assoc");
    for (var i = 0; i < counts.length; i++) {
      console.log('settings-field ' + (i+1).toString());
      val = (divs[i].querySelector(".equips-img-select-path") &&
        divs[i].querySelector(".equips-img-select-path").value.indexOf("wp-content/uploads") != -1) ?
        assoc_count((i+1).toString(), true) : assoc_count((i+1).toString(), false);
      console.log("assoc count: " + val.toString());
      counts[i].value = val;
    }
  }

  function assoc_count(index, returnNext) {
    var assoc = document.querySelector('#img_assoc_id_' + index);
    var imgs = assoc.querySelectorAll('.eq_assoc');
    var int = (imgs.length) ? imgs.length : 0;
    return (returnNext)? int+1 : int;
  }
}

eqSetAssocCounts();
