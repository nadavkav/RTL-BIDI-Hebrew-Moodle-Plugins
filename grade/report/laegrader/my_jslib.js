/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
    function zerofill(_itemid) {
//      $("#user-grades:scroller." + _itemid + "][value='-']").attr("value","0.00");
      $("[rel=" + _itemid + "][value='-']").attr("value","0.00");
    }
/*
    $(".zerofill").click(function () {
      alert("Working from the called js file");
      $(".")
//      var text = '0.00';
//      $("input").val(text);
    });
*/
    function show_alert() {
        alert("Working from the called js file");
    }


