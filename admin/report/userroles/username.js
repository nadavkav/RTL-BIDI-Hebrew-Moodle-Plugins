/*
 * JavaScript for the username field type.
 *
 * @copyright &copy; 2007 The Open University
 * @author t.j.hunt@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package userrolesreport
 */

function add_username_autocomplete(fieldid, sesskey, url) {

    var dataSource = new YAHOO.util.XHRDataSource(url); 
    dataSource.responseType = YAHOO.util.XHRDataSource.TYPE_TEXT;
    dataSource.responseSchema = { recordDelim : "\n", fieldDelim : "\t" };
    dataSource.scriptQueryParam = 'prefix';
    dataSource.scriptQueryAppend = 'sesskey=' + sesskey;

    var autoComplete = new YAHOO.widget.AutoComplete('id_' + fieldid, fieldid + 'container', dataSource);
    autoComplete.forceSelection = true;
    autoComplete.maxResultsDisplayed = 20;

    autoComplete.formatResult = function(resultitem, query, resultmatch) {
        return resultitem[0] + " (" + resultitem[1] + ")";
    };
}