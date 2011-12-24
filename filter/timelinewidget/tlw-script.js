/* Timeline Widget include/onResize script. */
var TLW = TLW || {};
TLW.include=function(filetype, opts){ //(url,filetype,callback,inner)
  var ref;
  if (filetype==="js"){ //JavaScript.
    ref=document.createElement("script");
    //ref.type="text/javascript";
    if(typeof opts.url!=="undefined"){
      ref.src=opts.url; //setAttribute("src", url);
    }
    if(typeof opts.callback!=="undefined"){
      ref.onreadystatechange=opts.callback;
      ref.onload=opts.callback;
    }
    //ref.id =typeof opts.id!="undefined" ? opts.id : ''; 
    //MSIE bug?
  } else if (filetype==="css"){ //External CSS file.
    ref=document.createElement("link");
    ref.rel="stylesheet";
    ref.type="text/css";
    ref.href=opts.url;
  }
  if (opts.inner!=="undefined"){
    //document.write('iH '+ref.innerHTML+'; iT '+ref.innerText+'; tC '+ref.textContent);//Debug.

    var MSIE = -1!==navigator.userAgent.indexOf("MSIE");

    if(MSIE && ref.text!=="undefined")  {ref.text = opts.inner;}
    else if(ref.innerHTML!=="undefined"){ ref.innerHTML = opts.inner;}//Firefox.
    else if(ref.innerText!=="undefined")  {ref.innerText = opts.inner;}
    else if(ref.textContent!=="undefined"){ref.textContent=opts.inner;}
    else{ ref.appendChild(document.createTextNode(opts.inner));}//Safari.
    //YAHOO.util.Element.setContent(ref, opts.inner);
  }
  if (typeof ref!=="undefined"){
    //var where = opts.where!=="undefined" ? opts.where : "head";
    document.getElementsByTagName("head")[0].appendChild(ref);
  }
};
TLW.tl = null;
TLW.resizeTimer = null;
TLW.onResize = function() {
  if (TLW.resizeTimer === null) {
    TLW.resizeTimer = window.setTimeout(function() {
      TLW.resizeTimer = null;
      TLW.tl.layout();
    }, 500); //milliseconds.
  }
};
