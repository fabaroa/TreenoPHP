function onBodyLoad () {
	var myAjax = new Ajax.Request (
		'../lib/settingsFuncs.php?func=getDefaultPages',
		{ onComplete: getDefaultPagesResponse, method: 'get' }
	);
}

function getDefaultPagesResponse (origReq) {
        var xmlDoc = origReq.responseXML;
        var pgInfo = xmlDoc.getElementsByTagName ('page');
        var tbl = $('defaultPage_tbl');
        for (var i = 0; i < pgInfo.length; i++) {
                var myRow = tbl.insertRow(tbl.rows.length);
                var myEl = myRow.insertCell(myRow.cells.length);
                myEl.appendChild (document.createTextNode(pgInfo[i].getAttribute ('disp')));

                var myEl = myRow.insertCell (myRow.cells.length);

                var rdVal = pgInfo[i].getAttribute ('name');
                if(document.all) {
                        var inputStr = '<input type="radio" name="defaultPage_radio" value="'+rdVal+'"';
                        if (pgInfo[i].getAttribute ('current') == '1') {
                                inputStr += 'checked="checked"';
                        }
                        inputStr += '>';
                        myInp = document.createElement(inputStr);
                } else {
                        var myInp = document.createElement ('input');
                        myInp.type = 'radio';
                        myInp.name = 'defaultPage_radio';
                        myInp.value = rdVal;
                        if (pgInfo[i].getAttribute ('current') == '1') {
                                myInp.checked = true;
                        }
                }
                myEl.appendChild(myInp);
        }
        $('defaultPage_mainDiv').style.display = 'block';
}

Behaviour.addLoadEvent (onBodyLoad);

var myrules = {
	'#defaultPage_submit' : function (el) {
		el.onclick = function () {
			var xmlDoc = createDOMDoc ();
			var root = xmlDoc.createElement ('root');
			xmlDoc.appendChild (root);
			var myEls = document.getElementsByTagName ('input');
			for (var i = 0; i < myEls.length; i++) {
				if (myEls[i].type == 'radio' && myEls[i].checked) {
					var defEl = xmlDoc.createElement ('default');
					defEl.setAttribute ('name', myEls[i].value);
					root.appendChild (defEl);
					break;
				}
			}
			var myAjax = new Ajax.Request (
				'../lib/settingsFuncs.php?func=setDefaultPage',
				{ 
					onComplete: setDefaultPageResponse,
					method: 'post',
					postBody: domToString (xmlDoc)
				}
			);
		}
	}
};

Behaviour.register (myrules);

function setDefaultPageResponse (origReq) {
	$('defaultPage_errMsg').firstChild.nodeValue = origReq.responseXML.documentElement.firstChild.nodeValue;
}
