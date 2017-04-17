<?php
include_once '../check_login.php';
include_once '../lib/settings.php';

if( $logged_in == 1 && strcmp( $user->username, "" )!=0 ) {
	$fpath = $DEFS['DATA_DIR']."/".$user->db_name;
	if($_GET['username']) {
		$fpath .= "/personalInbox/".$_GET['username'];
	}

	if($_GET['foldname']) {
		$fpath .= "/".$_GET['foldname'];
	}	

	$delID = ($_GET['delegateID']) ? $_GET['delegateID'] : 0;

	$fArr = glob($fpath."/*"); 
	usort($fArr,"strnatcasecmp");

	$curPage = $fArr[$_GET['page']];

	$folderCt = 0;
	$fileList = array();
	foreach($fArr AS $f) {
		if(is_file($f)) {
			$fileList[] = basename($f);
		} else {
			$folderCt++;
		}
	}

	$tp = count($fileList);
	$page = ($_GET['page'] - $folderCt);
	$fStr = "'".implode("','",$fileList)."'";

	//get user settings for whether or not to display delete cabinets
	$userSettings = new Usrsettings($user->username, $user->db_name);
	//retrieves whether or not to be able to delete and warn
    $glbSettings = new GblStt( $user->db_name, $db_doc);

	//retrive user settings to view how many results per page   
	$pagedisplay = $userSettings->get( 'results_per_page' );
	if( $pagedisplay == NULL ) 
		$pagedisplay = 25;
?>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="../lib/style.css">
	<script>
		var inbox = '<?php echo $_GET['inbox']; ?>'; 
		var user = '<?php echo $_GET['user']; ?>';
		var folder = '<?php echo $_GET['foldname']; ?>'; 
		var username = '<?php echo $_GET['username']; ?>'; 
		var delegateID = '<?php echo $delID; ?>';
		var fArr = new Array(<?php echo $fStr; ?>);
		var inboxPage=0;
		if(top.document.getElementById('mainFrame')) {
			if (top.mainFrame.document.getElementById('page')){
				inboxPage=top.mainFrame.document.getElementById('page').value - 1;
			}
		}
		var pagedisplay=<?php echo $pagedisplay; ?>;
		var pPage=<?php echo $page; ?>;
		var page = (inboxPage * pagedisplay)+(pPage-1);

		function refresh() {
			if(top.document.getElementById('mainFrameSet')) {
				top.document.getElementById('mainFrameSet').setAttribute('cols','100%,*');
			} else {
				top.window.close();
			}
		}

		function changePage(p) {
			if(p == 1) {
				page++;
			} else if(p == -1) {
				page--;
			} else {
				page = p;
			}
			
			if(page < 0) {
				page = 0;
			} else if(page >= fArr.length) {
				page = fArr.length - 1;
			}
			var name = fArr[page];
			var getStr = '?inbox='+inbox+'&name=' + name
					 + '&user='+user+'&foldname=' + escape(folder) + '&username='+ username
					 + '&delegateID='+delegateID+'&tmp=0/' + name;
			document.getElementById('pageNum').value = page + 1;
			parent.tFrame.location = 'displayInbox.php?'+getStr;	
			if(top.mainFrame) {
				top.mainFrame.selectRow(name);
			}
			/* check to see if you need to change the inboxPage */
			if ((page+1)<=(inboxPage*pagedisplay)) {
				/* goto previous page */
						var mySearch="page="+(inboxPage+1);
						var newAction=top.mainFrame.document.filename.action.replace(mySearch, "page="+inboxPage);
						top.mainFrame.document.filename.action=newAction;
			    	top.mainFrame.document.filename.action += "&selectRow="+name;
						top.mainFrame.document.filename.submit();
			}
			else if ((page+1)>((inboxPage+1)*pagedisplay)) {
				/* goto next page */
						var mySearch="page="+(inboxPage+2);
						var newAction=top.mainFrame.document.filename.action.replace("page="+(inboxPage+1),mySearch);
						top.mainFrame.document.filename.action=newAction;
			    	top.mainFrame.document.filename.action += "&selectRow="+name;
						top.mainFrame.document.filename.submit();
			}
		}

		function changePageOnEnter(e) {
			var evt = (e) ? e : event;
			var charCode = (evt.keyCode) ? evt.keyCode : evt.charCode;
			if(charCode == 13) {
				changePage(parseInt(document.getElementById('pageNum').value)-1);	 
			}
			return true;
		}
		function deleteAndChangePage() {
/*** start	by unchecking */		
			var type = 'fileCheck:';
		 	var el;
	  	i = 1;
      while(el = top.mainFrame.document.getElementById(type + i)) {
      	el.checked = false;
      	i++;
      }
/* then check the one to be deleted */
			i = page-(inboxPage * pagedisplay) + 1;			      
      el = top.mainFrame.document.getElementById(type + i);
      el.checked = true;
/**/      

			page++;
			
			if(page < 0) {
				page = 0;
			} else if(page >= fArr.length) {
				page = fArr.length - 2;
			}

			var name = fArr[page];
			var getStr = '?inbox='+inbox+'&name=' + name
					 + '&user='+user+'&foldname=' + escape(folder) + '&username='+ username
					 + '&delegateID='+delegateID+'&tmp=0/' + name;
			document.getElementById('pageNum').value = page + 1;
			
			parent.tFrame.location = 'displayInbox.php?'+getStr;	
			if(top.mainFrame) {
				top.mainFrame.selectRow(name);
			}
    	top.mainFrame.document.filename.action += "&delete=1&selectRow="+name;
			top.mainFrame.document.filename.submit();
			
		}
  function askUserB() {
		message = "This file will be removed (not really just testing)";
		answer = window.confirm(message);
		if(answer == true) {
			deleteAndChangePage();
		}
	}

	</script>
</head>
<body class="tealbg" style="margin:0px;padding-left:2px">
	<div onclick="refresh()" style="width:25%;float:left">
		<span style="font-size:9pt;cursor:pointer">Back</span>
	</div>
	<div style="width:50%;float:left;text-align:center">
        <img src="../energie/images/begin_button.gif" alt="First" title="First" onclick="changePage(0)">
        <img src="../energie/images/back_button.gif" alt="Previous" title="Previous" onclick="changePage(-1)">
<script>document.write('<input type="text" id="pageNum" value="'+(page+1)+'" style="width:30px" onkeypress="changePageOnEnter(event)" />');</script>
		<span>of</span>
		<span id="totalPages"><?php echo $tp; ?></span>
        <img src="../energie/images/next_button.gif" alt="Next" title="Next" onclick="changePage(1)">
        <img src="../energie/images/end_button.gif" alt="Last" title="Last" onclick="changePage(<?php echo count($fileList)-1; ?>)">
	</div>

<?php
		if($_GET['username']) {
			$delSetting = 'deletePersonalInbox';
		} else {
			$delSetting = 'deletePublicInbox';
		}
		$canDel = '';
		$delInbox = $glbSettings->get($delSetting);
		if($delInbox === '1'){
			$canDel = true;
		}
			
		//get user settings for whether or not to display delete cabinets
		$uDelInbox = $userSettings->get($delSetting);
		if($uDelInbox === '1') {
			$canDel = true;
		} elseif($uDelInbox === '0') {
			$canDel = false;
		}
		if($canDel === '') {
			$canDel = false;
		}
		$delPage = 0;
		$delPage = ($glbSettings->get('inboxDelOnePage')) ? 1 : 0;
		if(!$delPage) {
		    $delPage = ($userSettings->get('inboxDelOnePage')) ? 1 : 0;
		}
		if($delPage && $canDel) {
echo<<<ENERGIE
	<input onclick="askUserB()" type="button" name="delete" value="Delete This Page" style="font-size:9pt;float:right">
ENERGIE;
}
?>	

</body>
</html>
<?php
}
?>
