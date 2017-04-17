<?php
include_once '../check_login.php';
include_once '../classuser.inc';

if($logged_in==1 && strcmp($user->username,"")!=0 && $user->isDepAdmin()) {
	$gblStt = new GblStt($user->db_name, $db_doc);
	$user->setSecurity();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
    <title>View Auto Complete Table</title>
    <link rel="stylesheet" type="text/css" href="../lib/style.css"/>
    <script type="text/javascript" src="../lib/settings.js"></script>
	<script>
		var p = getXMLHTTP();
		var URL = '../secure/uploadActions.php';
		function selectCabinet(pg) {
			removeDefault(getEl('cabinetAC'));
            //clearDiv(errMsg);
            //showDivs([errMsg]);
			var cab = getEl('cabinetAC').value;
			var newURL = URL+'?cab='+cab+'&viewACTable=1';
			if(pg) {
				newURL += '&page='+pg;
			}
			p.open('POST', newURL, true);
            try {
                document.body.style.cursor = 'wait';
                //errMsg.appendChild(document.createTextNode('Please Wait....'));
                p.send(null);
            } catch(e) {
                //errMsg.appendChild(document.createTextNode('Error occured connecting'));
                document.body.style.cursor = 'default';
            }
            p.onreadystatechange = function() {
                if(p.readyState != 4)  {
                    return;
                }

				if(p.responseXML) {
					var tableEl = getEl('acTable');
					clearDiv(tableEl);
					var XML = p.responseXML;
	
					var page = XML.getElementsByTagName('PAGE');
					if(page) {
						var ct = parseInt(page[0].getAttribute('total'));
						showDivs([getEl('acResults')]);
						((ct > 1) ? showDivs([getEl('pageDiv')]) : hideDivs([getEl('pageDiv')]));
						var curPage = parseInt(page[0].firstChild.nodeValue);
						getEl('curPage').value = curPage;
						clearDiv(getEl('pageCount'));
						getEl('pageCount').appendChild(document.createTextNode(' of '+ct));
						if(ct > 1) {
							getEl('first').onclick = function() { selectCabinet(1)};	
							getEl('prev').onclick = function() {selectCabinet(curPage - 1)};	
							getEl('next').onclick = function() {selectCabinet(curPage + 1)};	
							getEl('last').onclick = function() {selectCabinet(ct)};	
						}
					}

					var header = XML.getElementsByTagName('HEADER');
					if(header) {
						var row = tableEl.insertRow(tableEl.rows.length);	
						row.className = 'header';
						for(var i=0;i<header.length;i++) {
							var col = row.insertCell(row.cells.length);
							var t = document.createTextNode(header[i].firstChild.nodeValue);
							col.appendChild(t);
						}
					}

					var entry = XML.getElementsByTagName('ENTRY');
					if(entry) {
						for(var j=0;j<entry.length;j++) {
							var row = tableEl.insertRow(tableEl.rows.length);	
							row.className = 'inboxRes';
							var indices = entry[j].getElementsByTagName('INDICE');
							if(indices) {
								for(var i=0;i<indices.length;i++) {
									var col = row.insertCell(row.cells.length);
									if(indices[i].firstChild) {
										var t = document.createTextNode(indices[i].firstChild.nodeValue);
										col.appendChild(t);
									}
								}
							}
						}
					}
                	document.body.style.cursor = 'default';
				}
			};
		}

		function onEnter(e) {
			code = (e.keyCode) ? e.keyCode : e.which;
			if(code == 13) {
				selectCabinet(getEl('curPage').value);
			}
		}

		function exportAC() {
			cab = getEl('cabinetAC').value;
			var newURL = URL+'?cab='+cab+'&exportAC=1';
			p.open('POST', newURL, true);
            try {
                document.body.style.cursor = 'wait';
                p.send(null);
            } catch(e) {
                document.body.style.cursor = 'default';
            }
            p.onreadystatechange = function() {
                if(p.readyState != 4)  {
                    return;
				}
				if(p.responseXML) {
					var XML = p.responseXML;
					var root = XML.getElementsByTagName('ROOT');
					var path = root[0].getAttribute('path');
					var filename = root[0].getAttribute('filename');
					parent.leftFrame1.window.location = 'downloadResults.php?path='+path+'&filename='+filename;
				}
			};	
		}
	</script>
	<style>
		div.hideDiv {
			display: none;
		}

		tr.header {
			font-weight: bold;
			color: white;
		    background-color: #003b6f;
			text-align: center;
		}

		tr.inboxRes {
		    background-color: #ebebeb;
			text-align: center;
			white-space: nowrap;
		}

		td img{
			cursor: pointer;
		}
	</style>
</head>
<body>
	<div class='mainDiv'>
        <div class='mainTitle'>
            <span>View Auto Complete Table</span>
        </div>
		<div style='padding:5px'>
			<select id='cabinetAC' onchange="selectCabinet()">
				<option value='__default'>Choose a Cabinet</option>
				<?php foreach($user->cabArr AS $r => $a) : ?>
					<?php if($gblStt->get('indexing_'.$r) && $gblStt->get('indexing_'.$r) != 'odbc_auto_complete' && $gblStt->get('indexing_'.$r) != 'sagitta_ws_auto_complete') : ?>
						<option value='<?php echo $r; ?>'><?php echo $a; ?></option>
					<?php endif; ?>
				<?php endforeach; ?>
			</select>
		</div>
	</div>	

	<div id='acResults' style='padding-top:10px' class='hideDiv'>
		<div style='width:10%;float:left'>
			<span><input type='button' onclick='exportAC()' name='B1' value='Export Results'></span>
		</div>
		<div style='margin-left:auto;margin-right:auto;width:80%' class='hideDiv' id='pageDiv'>
			<table style='margin-left:auto;margin-right:auto'>
				<tr class='paging'>
					<td style='vertical-align:top'><img id='first' src="../energie/images/begin_button.gif" border="0"></td>
					<td style='vertical-align:top'><img id='prev' src="../energie/images/back_button.gif" border="0"</td>
					<td>
						<input type='text' onkeypress='onEnter(event)' id='curPage' name='page' value='1' size='3'>
						<span id='pageCount'> of 1</span>
					</td>
					<td style='vertical-align:top'><img id='next' src="../energie/images/next_button.gif" border="0"></td>
					<td style='vertical-align:top'><img id='last' src="../energie/images/end_button.gif" border="0"></td>
				</tr>
			</table>
        </div>
		<table id='acTable' width='100%' 
				cellpadding='0' border='0' cellspacing='1' 
				class='results' align='left'>
		</table>
	</div>
</body>
</html>
<?php
	setSessionUser($user);
} else {
	logUserOut();
}
?>
