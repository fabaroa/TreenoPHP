var winTitle = "";
function pXML() {
	var xmlArr = {	"include" : "lib/windowTitle.php",
					"function" : "getWhiteLabel" }; 

	if(window.postXML) {
		postXML(xmlArr);
	}
}

function recXML(XML) {
	var label = XML.getElementsByTagName('LABEL');
	if(label.length > 0) {
		DEFAULT_TITLE = label[0].firstChild.nodeValue+' - '+winTitle;	
		if(arguments[1]) {
			top.document.title = arguments[1] + " - " + DEFAULT_TITLE;
		} else {
			top.document.title = DEFAULT_TITLE;
		}
	}
}

function repErr(req) {

}

/** allows setting of the window title multiple ways
 *   dispType == 1 - title is set to PageName and default, arg2 is pagename
 *   dispType == 2 - the title is set to describe the name of the current cabinet - passed as arg2
 *   dispType == 3 - the title is set to display the cabinet name and the current folder fields - arg2 - cabinet name; arg3 - folder string
 */
function setTitle(dispType) {
	switch (dispType) {
	case 1:
		winTitle = arguments[1];
		pXML();
		break;
	case 2:
	    myStr = arguments[1];
	    myStr = "Cabinet: " + myStr.replace(/_/g, " ");
	    top.document.title = myStr;
		break;
	case 3:
		myStr = arguments[1];
		myStr = "Cabinet: " + myStr.replace(/_/g, " ") + "         Folder: " + arguments[2];
		top.document.title = myStr;
		break;
	default:
		pXML();
		break;
	}
}
