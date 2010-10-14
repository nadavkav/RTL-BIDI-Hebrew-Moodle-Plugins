/*
  Author: Daniel Lopez
  Date: 17 Nov 2004
  Expandable Listmenu Script

  Based on scripts, samples and ideas by:
  .- Ben Boyle
     http://inspire.server101.com/js/xc/
  .- Thomas Bakketun
     http://www.bakketun.net/listmenu/
  .- Daniel Nolan
     http://www.bleedingego.co.uk/webdev.php
*/
var menu_class = 'c_menu';
var breadcrumb_ul_id = 'breadcrumb';
var expand_all_id = 'expand_all';
var collapse_all_id = 'collapse_all';
var exclusive_class = 'c_exclusive';
var breadcrumb_li_class = 'c_breadcrumb';
var expand_all_class = 'c_expand';
var collapse_all_class = 'c_collapse';
var no_menu_class = 'c_no_menu';
var no_current_class = 'c_no_current';
var title_opens_branch_class = 'c_title_opens_branch';
var open_menu_class = 'c_open_menu';
var current_class = 'c_current';
var extra_class = 'c_ex';
var div_class = 'c_div';
var expanded_class = 'c_aO';
var compressed_class = 'c_aC';
var id_preffix = 'aO_';
var TEXT_NODE_TYPE = 3;
var ANCHOR_NODE_TYPE = 1;
var text_replaced_anchors = ' >>';
var text_with_symbols = '- ';
var title_opens_branch = false;

function initMenu()
{
  performAction();
}

function performAction(action)
{
  if (document.getElementById && document.createElement)
  {
    var m = document.getElementsByTagName('ul');
    var d, p, i, j, u;
    for (i = 0; i < m.length; i++)
    {
      if (elementHasClass(m[i],menu_class))
      {
        var exclusive = elementHasClass(m[i],exclusive_class);
        var current = !elementHasClass(m[i],no_current_class);
        title_opens_branch = elementHasClass(m[i],title_opens_branch_class);
        var currentNode;
        u = m[i].getElementsByTagName('ul');
        for (j = 0; j < u.length; j++)
        {
          if (!elementHasClass(u[j],no_menu_class))
          {
            d = u[j].getAttribute('id');
            p = u[j].parentNode;
            var original = firstNonEmptyChild(p);
            if(action==null)
            {
              var div = getAlternateNode(d,p,u[j].title,exclusive);
              p.replaceChild(div, original);
              var aO = firstNonEmptyChild(div);
              if(current)
              {
                if(aO.getAttribute('old_href')==window.location)
                {
                  div.className = div.className + ' ' + current_class;
                  currentNode = div;
                }
                var count_a;
                var contains_current = false;
                var as = u[j].getElementsByTagName('a');
                for (count_a = 0; count_a < as.length; count_a++)
                {
                  if(as[count_a].href==window.location)
                  {
                    contains_current = true;
                    as[count_a].className = current_class;
                    currentNode = as[count_a].parentNode;
                  }
                }
              }
              var count_u;
              var contains_open = false;
              var us = u[j].getElementsByTagName('ul');
              for (count_u = 0; count_u < us.length; count_u++)
              {
                if(elementHasClass(us[count_u],open_menu_class))
                {
                  contains_open = true;
                }
              }
              if (!contains_open && !contains_current && !elementHasClass(u[j],open_menu_class))
              {
                swapNode(aO);
              }
            }
            else if(action=='expand')
            {
              expandNode(original);
            }
            else if(action=='collapse')
            {
              compressNode(original);
            }
          }
        }
        if(currentNode)
        {
          createBreadCrumb(currentNode);
        }
        if(action==null)
        {
          createExpandAll();
          createCollapseAll();
        }
      }
    }
  }
}

function expandAll()
{
  performAction('expand');
}

function collapseAll()
{
  performAction('collapse');
}


function compressNode(theNode, list)
{
  if(list==null)
  {
    list = theNode.nextSibling;
    if(list.nodeType == TEXT_NODE_TYPE)
    {
     list = list.nextSibling;
    }
  }
  list.style.display='none';
  if(elementHasClass(theNode,current_class))
  {
    theNode.className = compressed_class + ' ' + current_class;;

  }
  else
  {
    theNode.className = compressed_class;
  }
}

function expandNode(theNode, list)
{
  if(list==null)
  {
    list = theNode.nextSibling;
    if(list.nodeType == TEXT_NODE_TYPE)
    {
     list = list.nextSibling;
    }
  }
  list.style.display='block';
  if(elementHasClass(theNode,current_class))
  {
    theNode.className = expanded_class + ' ' + current_class;;
  }
  else
  {
    theNode.className = expanded_class;
  }
}

function createBreadCrumb(node)
{
  var breadcrumb_ul = document.getElementById(breadcrumb_ul_id);
  if(breadcrumb_ul)
  {
    addParentPath(node,breadcrumb_ul);
  }
}

function createDoAll(do_all_id,do_all_function,do_all_class)
{
  var do_all_a = document.getElementById(do_all_id);
  if(do_all_a && do_all_a.nodeType==ANCHOR_NODE_TYPE)
  {
    do_all_a.setAttribute('href', 'javascript:' + do_all_function + ';');
    do_all_a.className = do_all_class;
  }
}

function createExpandAll()
{
  createDoAll(expand_all_id,'expandAll()',expand_all_class);
}

function createCollapseAll()
{
  createDoAll(collapse_all_id,'collapseAll()',collapse_all_class);
}

function addParentPath(node,breadcrumb_ul)
{
  if(firstNonEmptyChild(firstNonEmptyChild(node)) && (firstNonEmptyChild(node).tagName=='DIV' || firstNonEmptyChild(node).tagName=='A'))
  {
	var text;
	var divNode;
	if(firstNonEmptyChild(node).tagName=='DIV')
	{
		divNode = firstNonEmptyChild(node);
	}
	else
	{
		divNode = node;
	}
	if(firstNonEmptyChild(divNode).nextSibling && firstNonEmptyChild(divNode).nextSibling.tagName=='A')
	{
		text = firstNonEmptyChild(firstNonEmptyChild(divNode).nextSibling).nodeValue;
	}
	else
	{
		text = firstNonEmptyChild(firstNonEmptyChild(divNode)).nodeValue;
	}
    newLi = document.createElement('li');
    var old_href = firstNonEmptyChild(divNode).getAttribute('old_href');
    if(old_href)
    {
      newA = document.createElement('a');
      newA.setAttribute('href', old_href);
      newA.appendChild(document.createTextNode(text));
      newLi.appendChild(newA);
    }
    else
    {
      newLi.appendChild(document.createTextNode(firstNonEmptyChild(firstNonEmptyChild(divNode)).nodeValue));
    }
    newLi.className = breadcrumb_li_class;
    if(firstNonEmptyChild(breadcrumb_ul))
    {
      breadcrumb_ul.insertBefore(newLi, firstNonEmptyChild(breadcrumb_ul));
    }
    else
    {
      breadcrumb_ul.appendChild(newLi);
    }
  }
  if(!elementHasClass(node.parentNode,menu_class))
  {
    addParentPath(node.parentNode,breadcrumb_ul);
  }
}

function expandParent(node)
{
  if(!elementHasClass(node,menu_class))
  {
    if(firstNonEmptyChild(node) && firstNonEmptyChild(firstNonEmptyChild(node)) && firstNonEmptyChild(firstNonEmptyChild(node)).parentNode)
    {
      expandNode(firstNonEmptyChild(firstNonEmptyChild(node)).parentNode,node.parentNode);
    }
    expandParent(node.parentNode);
  }
}

function swapNode(theNode,exclusive)
{
  var e = theNode.parentNode.nextSibling;
  if(e.nodeType == TEXT_NODE_TYPE)
  {
   e = e.nextSibling;
  }
  if(e.style.display=='none')
  {
    if(exclusive)
    {
      collapseAll();
    }
    expandNode(theNode,e);
    if(exclusive)
    {
      expandParent(theNode);
    }
  }
  else
  {
    compressNode(theNode,e);
  }
}

function getAlternateNode(id,parent,v_title,exclusive)
{
  var aElement;
  var divElement = document.createElement('div');
  divElement.style.display='block';
  divElement.className = div_class;
  if(firstNonEmptyChild(parent).nodeType == TEXT_NODE_TYPE)
  {
    aElement = document.createElement('a');
    aElement.appendChild(document.createTextNode(firstNonEmptyChild(parent).nodeValue));
    aElement.style.display='inline';
    divElement.appendChild(aElement);
  }
  else if (firstNonEmptyChild(parent).nodeType == ANCHOR_NODE_TYPE)
  {
    var original = firstNonEmptyChild(parent);
    aElement = document.createElement('a');
    aElement.setAttribute('old_href',original.href);
    aElement.style.display='inline';
    if(title_opens_branch)
    {
      aElement.appendChild(document.createTextNode(firstNonEmptyChild(original).nodeValue));
      var accesElement = document.createElement('a');
      accesElement.className = extra_class;
      accesElement.setAttribute('href',original.href);
      accesElement.appendChild(document.createTextNode(text_replaced_anchors));
      accesElement.style.display='inline';
      divElement.appendChild(accesElement);
      divElement.insertBefore(aElement,accesElement);
    }
    else
    {
      aElement.appendChild(document.createTextNode(text_with_symbols));
      var symbolElement = document.createElement('a');
      symbolElement.className = extra_class;
      symbolElement.setAttribute('href',original.href);
      symbolElement.appendChild(document.createTextNode(firstNonEmptyChild(original).nodeValue));
      symbolElement.style.display='inline';
      divElement.appendChild(symbolElement);
      divElement.insertBefore(aElement,symbolElement);
    }
  }
  aElement.setAttribute('href', 'javascript:void(0);');
  aElement.setAttribute('id', id_preffix + id);
  aElement.onclick = function()
  {
    swapNode(this, exclusive);
  }
  aElement.onfocus=function(){this.blur()};
  aElement.className = expanded_class;
  if(v_title)
  {
   aElement.setAttribute('title', v_title);
  }
  return divElement;
}

function elementHasClass( element, className )
{
  if ( ! element.className )
  {
    return false;
  }
  var re = new RegExp( "(^|\\s+)" + className + "($|\\s+)" );
  return re.exec( element.className );
}

function firstNonEmptyChild( element )
{
  if (element.firstChild && (element.firstChild.nodeType != TEXT_NODE_TYPE || (element.firstChild.nodeValue && element.firstChild.nodeValue.replace(/^\s*|\s*$/g,"") != '')))
  {
    return element.firstChild;
  }
  else if (element.firstChild)
  {
    return element.firstChild.nextSibling;
  }
  else
  {
   return element.firstChild;
  }
}

window.onload = initMenu;