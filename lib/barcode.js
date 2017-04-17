function printDocutronBarcode(cabinet, docID, subfolder, workflow, uid, department, tabID)
{
	if(!boolBC) {	
		boolBC = true;
		var urlStr = '../barcode/getBarcode.php?cabinet=' + cabinet;

		if(docID) {
			urlStr += "&docID=" + docID;
			
			if(tabID) {
				urlStr += "&tabID="+tabID;
			}
			if(subfolder) {
				if (subfolder == '__all') {
					urlStr += "&printAll=1";
				} else {
					urlStr += "&subfolder=" + escape(subfolder);
				}
			}
		}
		if(workflow) {
			urlStr += "&wf=" + workflow;
		}
			
		if(uid) {
			urlStr += "&uid=" + uid;
		}
		
		if(department) {
			urlStr += "&dept=" + department;
		}

		parent.leftFrame1.window.location = urlStr; 
	//	var newWindow = window.open(urlStr, 'barcodeWnd','height=200,width=300');
	//	newWindow.focus();

		setTimeout(function() { boolBC=false }, 10000);
	} else {
		if(window.addMessage) { 
			addMessage('You have already printed barcode.  Please wait 10 seconds');
		} else {
			alertBox('You have already printed barcode.  Please wait 10 seconds');
		}
	}
}

function printUserBarcode(username)
{
		
	//cz 10-06-2011
	//var windowName = window.name;
	//var count = parent.window.frames.length;
	//var childrenName = "Children of " + parent.name + ": ";
	//for(var indexWindow = 0; indexWindow < count; indexWindow++)
	//{
	//	childrenName +=  parent.window.frames[indexWindow].name + ", ";
	//}
	//alert("Number of frames in parent window: " + count + ". " + childrenName);


	var urlStr = '../barcode/getBarcode.php?username=' + username; 
	parent.leftFrame1.window.location = urlStr; 
}

