var xpos = 0;
var ypos = 0;
function getMousePosition(divObj,frame) {
        xpos = divObj.offsetLeft;
        ypos = divObj.offsetTop + divObj.offsetHeight;
        var el = divObj;
        while((el = el.offsetParent) != null) {
            xpos += el.offsetLeft;
            ypos += el.offsetTop;
        }
}

function requestHelp(divObj,key,lang,frame) {
        exitHelp();
        getMousePosition(divObj,frame);

		var myFrame = document.createElement('iframe');
		myFrame.id = 'helpDesc';
        myFrame.style.position = 'absolute';
        myFrame.style.top = ypos+'px';
        myFrame.style.left = xpos+'px';
        myFrame.style.width = '300px';
        myFrame.style.height = '200px';
        myFrame.style.marginHeight = 0;
        myFrame.style.marginWidth = 0;
        myFrame.style.backgroundColor = '#ffffff';
		myFrame.src = '../lib/help.php?k='+key+'&lang='+lang;

        document.body.appendChild(myFrame);
}

function exitHelp() {
    var divEl = top.mainFrame.document.getElementById('helpDesc');
    if(divEl) {
        divEl.parentNode.removeChild(divEl);
    }
}
