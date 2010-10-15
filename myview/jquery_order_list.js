/////////////////////////////////////////////////////////////////
/////  EDIT THE FOLLOWING VARIABLE VALUES  //////////////////////
/////////////////////////////////////////////////////////////////

// UserID is set in the parent/calling php document (index.php)
var userID = "2";

// set the list selector
var setSelector = "#courselist";
// set the cookie name
var setCookieName = "listOrder";
// set the cookie expiry time (days):
var setCookieExpiry = 7;

/////////////////////////////////////////////////////////////////
/////  YOU PROBABLY WON'T NEED TO EDIT BELOW  ///////////////////
/////////////////////////////////////////////////////////////////

// function that writes the list order to a cookie
function getOrder() {
  // save custom order to cookie
  $.cookie(setCookieName, $(setSelector).sortable("toArray"), { expires: setCookieExpiry, path: "/" });
  $.post("mycourseslistorder.php", { action: "set", name: userID, mycourseslist: $(setSelector).sortable("toArray") } );
}

// function that restores the list order from a cookie
function restoreOrder() {
  var list = $(setSelector);
  if (list == null) return

  // fetch the cookie value (saved order)
  var cookie = $.cookie(setCookieName);
  if (!cookie) return;

  // make array from saved order
  var IDs = cookie.split(",");

  // fetch current order
  var items = list.sortable("toArray");

  // make array from current order
  var rebuild = new Array();
  for ( var v=0, len=items.length; v<len; v++ ){
    rebuild[items[v]] = items[v];
  }

  for (var i = 0, n = IDs.length; i < n; i++) {

    // item id from saved order
    var itemID = IDs[i];

    if (itemID in rebuild) {

      // select item id from current order
      var item = rebuild[itemID];

      // select the item according to current order
      var child = $("ul.ui-sortable").children("#" + item);

      // select the item according to the saved order
      var savedOrd = $("ul.ui-sortable").children("#" + itemID);

      // remove all the items
      child.remove();

      // add the items in turn according to saved order
      // we need to filter here since the "ui-sortable"
      // class is applied to all ul elements and we
      // only want the very first!  You can modify this
      // to support multiple lists - not tested!
      $("ul.ui-sortable").filter(":first").append(savedOrd);
    }
  }
}

// code executed when the document loads
$(function() {
  // here, we allow the user to sort the items
  $(setSelector).sortable({
    axis: "y",
    cursor: "move",
    update: function() { getOrder(); }
  });

  // here, we reload the saved order
  restoreOrder();
});
