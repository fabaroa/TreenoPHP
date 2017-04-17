<?php
// $Id: menuSlide.php 14657 2012-02-06 13:48:38Z acavedon $

include_once '../check_login.php';
include_once '../settings/settings.php';
include_once '../modules/modules.php';
include_once '../departments/depfuncs.php';
include_once '../lib/quota.php';
include_once '../lib/licenseFuncs.php';

if( $logged_in == 1 && strcmp( $user->username, "" )!=0 )
{
	if(isSet($_GET['restore'])) {
   		$user->access = array();
        $user->cabArr = array();
        $user->fillUser();
        $user->restore = 0;
		$user->doc_id = 0;
        setSessionUser($user, false);
    }

	$backToResults = $trans['Back To Results'];
	$licenseList = getTableInfo ($db_doc, 'licenses', array (),
		array (), 'queryAll', array ('arb_department' => 'ASC'));
	$arbList = array ();
	foreach ($licenseList as $row) {
		$arbList[$row['real_department']] = $row['arb_department'];
		if ($row['real_department'] == $user->db_name) {
			$quota_allowed = $row['quota_allowed'];
			$quota_used = $row['quota_used'];
		}
	}
	uasort ($arbList, 'strnatcasecmp');

	$DO_users = DataObject::factory('users', $db_doc);
	$DO_users->get('username', $user->username);
	$depList = array_keys($DO_users->departments);

    $used = adjustQuota( $quota_used );
    if( $quota_used != 0 && $quota_allowed != 0) {
        $pUsed = round(($quota_used / $quota_allowed) * 100, 2);
    } else {
        $pUsed = 0;
    }

	$gblStt = new GblStt($user->db_name, $db_doc);
	$frameWidth = 250;
	if($gblStt->get('frame_width')) {
		$frameWidth = $gblStt->get('frame_width');
	}

    $settings = new Usrsettings($user->username,$user->db_name);
	$searchPanelView = $settings->get('searchPanelView');
	if($searchPanelView == NULL) {
		$searchPanelView = 0;
	}

	echo<<<ENERGIE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" class="tealbg">
<head>
<title>Menu Bar</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
<script type="text/javascript" src="func.js"></script>
<script type="text/javascript" src="../lib/settings.js"></script>
<script>
	var winObj = '';
	var frameWidth = "$frameWidth";
	var tg = $searchPanelView;
 	function switchDepartments() {
		var xmlhttp = getXMLHTTP();
		var newDep = document.getElementById('changeDep').value;	
		var URL = '../departments/departmentActions.php?switchDep=1&newDep='+newDep;
		xmlhttp.open('POST',URL,true);
		xmlhttp.send(null);
		xmlhttp.onreadystatechange = function () {
			if(xmlhttp.readyState == 4) {
				var location = parent.searchPanel.window.location;
				parent.topMenuFrame.window.location = '../energie/menuSlide.php';
				var loc = location.toString();
				if(loc.indexOf('leftAdmin') != -1) {
					var xmlDoc = xmlhttp.responseXML;
					var myURLInfo = xmlDoc.getElementsByTagName('default');
					var myURL = myURLInfo[0].getAttribute ('url');
					if (myURL) {
						parent.mainFrame.window.location = myURL; 
					} else {
						parent.mainFrame.window.location = '../modules/fileInfo.php';
					}
					parent.searchPanel.window.location = '../secure/leftAdmin.php';
				} else if(loc.indexOf('inboxSelect1') != -1) {
					parent.mainFrame.window.location = '../secure/inbox1.php?new=1&type=1';
					parent.searchPanel.window.location = '../secure/inboxSelect1.php';
					parent.bottomFrame.window.location = 'bottom_white.php';
				} else {
					parent.mainFrame.window.location = '../energie/search_frame.php';
					parent.searchPanel.window.location = '../energie/searchPanel.php';

				}
			}
		};
	}

	function refreshQuota() {
        var xmlhttp = getXMLHTTP();
        var URL = '../departments/departmentActions.php?refreshQuota=1';
        xmlhttp.open('POST',URL,true);
        xmlhttp.send(null);
        xmlhttp.onreadystatechange = function () {
            if(xmlhttp.responseXML) {
                var XML = xmlhttp.responseXML;
                var q = XML.getElementsByTagName('QUOTA');
                if(q.length) {
                    clearDiv(getEl('viewQuota'));
                    var quota = q[0].firstChild.nodeValue;
                    var pUsed = q[0].getAttribute('percentUsed');
                    getEl('viewQuota').appendChild(document.createTextNode('Space Used: '+quota+' ('+pUsed+'%)'));
                }
            }
        };

    }

	function showQuotaMessage() {
        getEl('quotaMess').style.display = 'block';
    }

    function hideQuotaMessage() {
        getEl('quotaMess').style.display = 'none';
    }

   function restoreDefault(type) {
		if(type == 1) {
			parent.mainFrame.window.location='../energie/home.php?restore=1';
	   	}
	    window.location='../energie/menuSlide.php?restore=1';
	    parent.searchPanel.window.location = '../energie/searchPanel.php';
	    removeBackButton();
   }
	function openPublishing() {
		if(winObj) {
			if(!winObj.closed) {
				winObj.focus();
				return;
			}
		}

		var attArr = new Array(	"height=650",
								"width=415",
								"status=no",
								"titlebar=no",
								"toolbar=no",
								"location=no" );
		winObj = window.open('../publishing/viewPubWizard.html','publishing',attArr.join(","));
	}

	function addItem(cabinet,doc_id,file_id) {
		if(winObj && !winObj.closed) {
			winObj.focus();
			winObj.saveItem(cabinet,doc_id,file_id);
			return;
		} else {
			var attArr = new Array(	"height=650",
									"width=415",
									"status=no",
									"titlebar=no",
									"toolbar=no",
									"location=no" );
			winObj = window.open('../publishing/viewPubWizard.html','publishing',attArr.join(","));
		}
		popupWait(cabinet,doc_id,file_id);
	}

	function popupWait(cabinet,doc_id,file_id) {
		if(!winObj.initLoad) {
			setTimeout(function() {popupWait(cabinet,doc_id,file_id)},10);
		} else {
			winObj.saveItem(cabinet,doc_id,file_id);
		}
	}

  function maximizeView() {
	parent.document.getElementById('afterMenu').setAttribute('cols','260,*');
	var imgEl = top.searchPanel.document.getElementById('imgView');
	imgEl.src = '../images/left.GIF';
	imgEl.title = 'minimize';
	imgEl.alt = 'minimize';
	imgEl.onclick = function() { toggleSearchPanel(1) };
	top.searchPanel.document.getElementById('outerDiv').style.display = 'block';
  }

function minimizeView() {
	parent.document.getElementById('afterMenu').setAttribute('cols','20,*');
	var imgEl = top.searchPanel.document.getElementById('imgView');
	imgEl.src = '../images/right.GIF';
	imgEl.title = 'maximize';
	imgEl.alt = 'maximize';
	imgEl.onclick = function() { toggleSearchPanel(0) };
	top.searchPanel.document.getElementById('outerDiv').style.display = 'none';
}
function toggleSearchPanel(toggle) {
	tg = toggle;
	var xmlhttp = getXMLHTTP();
	xmlhttp.open('POST', '../secure/inboxMove.php?searchPanelView='+toggle, true);
	xmlhttp.setRequestHeader('Content-Type',
						'application/x-www-form-urlencoded');
	xmlhttp.send(null);
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4) {
			if(xmlhttp.responseText) {
				if(toggle) {	
					minimizeView();
				} else {
					maximizeView();
				}
			}
		}
	};
}

function normalMode() {
	top.document.getElementById('mainFrameSet').setAttribute('cols','*,'+frameWidth);
	top.topMenuFrame.document.getElementById('fullScreen').style.display = 'block';
	top.topMenuFrame.document.getElementById('exitFullScreen').style.display = 'none';
	if(tg == 1) {
		minimizeView();
	} else {
		maximizeView();
	}
}
</script>
</head>
ENERGIE;

    //if the user isn't admin and has hotlink settings set, use those
    //otherwise, use the defaults
    $dispDepName = $gblStt->get('displayDepartmentName');
    if(!$dispDepName) {
        $dispDepName = $settings->get('displayDepartmentName');
        if(!$dispDepName) {
            if($dispDepName != "0" && $dispDepName != "") {
                $dispDepName = 1;
			}
		}
	}

    if( $settings != null && $settings->get( 'enabledLinks' ) != null ) {
		$tempLinks  = $settings->get( 'enabledLinks' );
		$enabledLinks = explode("!_DELIMITER_!", $tempLinks);
    } else {
		$enabledLinks = array( 'todo', 'home', 'inbox', 'settings' );
    }

	$dispQuota = ($gblStt->get('displayQuota')) ? $gblStt->get('displayQuota') : $settings->get('displayQuota');
    if($dispQuota) {
        $disp = 'block';
    } else {
        $disp = 'none';
    }

	echo "\n<body style=\"text-align: right; margin: 0\">\n";
	echo<<<ENERGIE
<div id="exitFullScreen" style="display:none">
	<div id="paging" style="text-align:center;white-space:nowrap">
		<img id="viewMode" 
			class="buttons" 
			alt="Normal Mode" 
			title="Normal Mode" 
			src="../images/opnbr_24.gif" 
			style="position:absolute;left:0px"
			onclick="normalMode()"
		/>
		<table align="center">
			<tr>
				<td>
					<img id="firstPage" style="cursor:pointer;vertical-align:middle" 
						alt="First"
						title="First"
						width="16" 
						src="../energie/images/begin_button.gif" 
					/>
				</td>
				<td>
					<img id="prevPage" style="cursor:pointer;vertical-align:middle" 
						alt="Previous"
						title="Previous"
						width="16" 
						src="../energie/images/back_button.gif" 
					/>
				</td>
				<!--
				<td>
					<input style="height:12px;font-size:9pt" 
						id="newPage"
						type="text" 
						size="2" 
						value="" 
						onkeypress=""
					/>
					<input id="pageNum" 
						type="hidden" 
						totalPages=""
						value="" 
					/>
					<span id="pageDetail" style="vertical-align:middle;font-size:9pt"></span>
				</td>
				-->
				<td>
					<img id="nextPage" style="cursor:pointer;vertical-align:middle" 
						alt="Next"
						title="Next"
						width="16" 
						src="../energie/images/next_button.gif" 
					/>
				</td>
				<td>
					<img id="lastPage" style="cursor:pointer;vertical-align:middle" 
						alt="Last"
						title="Last"
						width="16" 
						src="../energie/images/end_button.gif" 
					/>
				</td>
			</tr>
		</table>
	</div>
</div>
<div id="fullScreen">
<table id="menuTable" style="text-align: right; margin:0px; width: 100%; white-space: nowrap">
<tr>
ENERGIE;
	if($dispDepName) {
        echo "<td style=\"text-align:left; width: 11em\" class=\"small_lnk\">";
        echo "<div style=\"white-space: nowrap\">Department:";
		if( $user->restore ) {
			$depList[] = $user->db_name;
		}

            if(sizeof($depList) > 1 ) {
                echo "  <select style='font-size:8pt' id='changeDep' onchange='switchDepartments()'>";
                foreach($arbList as $dep => $arb) {
                    if(in_array($dep,$depList)) {
                        if($dep == $user->db_name) {
                            echo "<option selected value='$dep'>$arb</option>";
                        } else {
                            echo "<option value='$dep'>$arb</option>";
                        }
                    }
                }
                echo "  </select>";
            } else {
                echo $arbList[$depList[0]];
            }
        echo "</td>";
    }
echo<<<ENERGIE
<td style='text-align:left;width:115px'>
<div id='viewQuota'
    onclick="refreshQuota()"
    onmouseover="showQuotaMessage()"
    onmouseout="hideQuotaMessage()"
    style='font-size:12px;white-space:nowrap;cursor:pointer;display:$disp'>Space Used: $used ($pUsed%)
</div>
</td>
<td id='quotaMess' style='display:none;color:red;text-align:left;font-size:10px'>Click to refresh quota</td>
<td id="backDiv" style="white-space: nowrap; text-align: left">
<table>
<tr>
<td class="mainMenu">
<div id="up" style="display: none;">
<img class="menuBtn" src="../images/move_16.gif" alt="Back to Results"/>
<span class="lnk">$backToResults</span>
</div>
</td>
<td class="mainMenu">
<div id="vers" style="display: none;">
<img class="menuBtn" width="20" src="../images/version.gif" alt="Back to Versioning"/>
<span class="lnk">Back To Versioning</span>
</div>
</td>
<td class="mainMenu">
<div id="docusign" style="display: none;">
<img class="menuBtn" width="20" src="../images/DocuSign.gif" alt="Back to Signing"/>
<span class="lnk">Back To Signing</span>
</div>
</td>
</tr>
</table>
</td>
ENERGIE;
	if(!isValidLicense($db_doc)) {
		$invalidLicense = "System License Expired.  System is in Read Only Mode";
		echo "<td>";
		echo "<div style='color:red'>$invalidLicense</div>";
		echo "</td>";
	}
echo<<<ENERGIE
<td style="margin-left: auto; margin-right: 0; text-align: right">
<table style="display: table; margin-left: auto; margin-right: 0;">
<tr>

ENERGIE;
//for Hunt Construction make button for url
		if( $user->db_name=='client_files263' ) {
			$huntcabs = base64_encode(array_implode( '=', '&', $user->cabArr )) ;
			//error_log("HuntCab=".$huntcabs);
			echo "
 <td>
  <a href='http://bizdataserver.com/search.aspx?cabinets=".$huntcabs."' target='_blank'>Hunt Construction URL</a>
 </td>";
		}

		if( check_enable('workflow', $user->db_name) and in_array("todo",$enabledLinks) ) {
			if($user->restore) {
			echo<<<ENERGIE
 <td id="userObjTD" class="mainMenu" onclick="restoreDefault(1);">
  <img class="menuBtn" src="../images/ref_24.gif" alt="Default" />
  <span class="lnk">Restore Defaults</span>
 </td>
ENERGIE;
			}
		}
//loop through enabled, adding each link
	foreach( $enabledLinks as $link ) {
		if( check_enable('workflow', $user->db_name) and strcmp($link, "todo")==0 ) {

			echo<<<ENERGIE
 <td class="mainMenu" onclick="parent.mainFrame.window.location='../workflow/viewWFTodo.php';removeBackButton();">
  <img class="menuBtn" src="../images/notep_16.gif" alt="To Do" />
  <span class="lnk" style="vertical-align:15%">To Do</span>
 </td>
ENERGIE;
		}
		if( strcmp($link, "home")==0 ) {
			echo<<<ENERGIE
<td class="mainMenu" onclick="parent.mainFrame.window.location='search_frame.php';refresh(2)">
<img class="menuBtn" src="../images/srch_16.gif" alt="Home" />
<span class="lnk" style="vertical-align:15%">{$trans['Home']}</span>
</td>

ENERGIE;
		} else if( strcmp($link, "inbox")==0 && !$user->restore) {
			echo<<<ENERGIE
<td id="inboxTD" class="mainMenu" onclick="parent.mainFrame.window.location='inbox_refresh.php?OK=1'">
<img class="menuBtn" src="../images/move_16.gif" alt="Inbox" />
<span class="lnk" style="vertical-align:15%">{$trans['Inbox']}</span>
</td>

ENERGIE;
		} else if( strcmp($link, "settings")==0 && !$user->restore) {
			echo<<<ENERGIE
<td id="settingsTD" class="mainMenu" onclick="parent.mainFrame.window.location='../secure/admin.php';refresh(3)">
<img class="menuBtn" src="../images/prefs_16.gif" alt="Administration" />
<span class="lnk" style="vertical-align:15%">Administration</span>
</td>

ENERGIE;
		}
	} //end foreach( $enabledLinks as $link )
	echo<<<ENERGIE
<td class="mainMenu" onclick="top.window.location='../logout.php?manual=true';refresh(4)">
<img class="menuBtn" src="../images/close_16.gif" alt="Logout" />
<span class="lnk" style="vertical-align:15%">{$trans['Logout']}</span>
</td>
</tr>
</table>
</td>
ENERGIE;
	$displayUser = $trans['User'].": ".$user->username;
	if( $user->isAdmin() ) {
		if($user->isSuperUser() ) {
			$admintype = $trans['Super Administrator'];
		} else if( $user->isDepAdmin() ){
			$admintype = $trans['Dep Administrator'] ;
		} else {
			$admintype = $trans['Administrator'];
		}
	} else {
		$admintype = '';
	}
echo<<<ENERGIE
<td class="small_lnk" style="width: 11em" title="$admintype">
<div style="white-space: nowrap;font-size:9pt">$displayUser</div>
</td>
</tr>
</table>

ENERGIE;
	echo "<div>\n";
	echo "<input type=\"hidden\" name=\"promptResponse\"/>\n";
	echo "</div>\n";
	echo "</div>\n";
	echo "</body>\n";
	echo "</html>";

	setSessionUser($user);
} else {//we want to log them out
	logUserOut();
}

function array_implode( $glue, $separator, $array ) {
     if ( ! is_array( $array ) ) return $array;
     $string = array();
     foreach ( $array as $key => $val ) {
         if ( is_array( $val ) )
             $val = implode( ',', $val );
         $string[] = "{$key}{$glue}{$val}";
         
    }
     return implode( $separator, $string );
     
}
?>
