<?php
include_once '../check_login.php';
include_once '../lib/odbc.php';
include_once '../lib/indexing2.php';
include_once '../db/db_common.php';

function xmlEnableDisableODBCMapping( $user, $en, $db_doc, $db_dept ){
	//check if in modules and save variable
	$cab = $en['cab_name'];
	$enabled = $en['enabled'];
	$db_name = $user->db_name;
	$sArr = array( 'count(id)' );
	$wArr = array( 
		'dir'=>'searchResODBC',
		'department'=>$db_name );
	$module_exists = getTableInfo($db_doc, 'modules', $sArr, $wArr, 'queryOne' ); 
	//if in modules update it to disabled
	if( $module_exists ){
		$updateArr = array( 'enabled'=> (int)$enabled );
		$wArr = array( 
			'department' => $db_name, 
			'dir'=>'searchResODBC' );
		updateTableInfo( $db_doc,'modules',$updateArr,$wArr );
	} else {
		$insArr = array( 
			'arb_name'=>'Search Results ODBC',
			'real_name'=>'searchResODBC',
			'dir'=>'searchResODBC',
			'enabled'=>(int)$enabled,
			'department'=>$db_name );
		$res = $db_doc->extended->autoExecute( 'modules', $insArr );
		if( PEAR::isError( $res ) )
			print_r( $res );
	}
	//check if in settings
	$sArr = array('value');
	$wArr = array( 
		'k'		=> "indexing_$cab",
		'department'	=> $user->db_name 
		     );
	$odbc_setting_exists = getTableInfo( $db_doc,'settings',$sArr,$wArr,'queryOne');
	if( $enabled ) {
		if(!$odbc_setting_exists) {
			$uArr = array( 
				'k'		=> "indexing_$cab",
				'value'		=> 'odbc_auto_complete',
				'department'	=> $user->db_name );
			$db_doc->extended->autoExecute( 'settings', $uArr );
		} elseif($odbc_setting_exists != 'odbc_auto_complete') {
			$uArr = array('value'=>'odbc_auto_complete');
			$wArr = array( 
				'k' 		=> "indexing_$cab",
				'department'	=> $user->db_name );
			updateTableInfo($db_doc,'settings',$uArr,$wArr);
		}
	} else {
		if($odbc_setting_exists == 'odbc_auto_complete') {
			$wArr = array( 
				'k' 		=> "indexing_$cab",
				'department'	=> $user->db_name );
			deleteTableInfo($db_doc,'settings',$wArr);
		}
	}
}

function updateOdbcMappingLevel($db,$cab,$level,$auto_comp_id,&$odbc_fields,$fkList) {
	$mapping_is_single_level = true;
	if(is_array($fkList)) {
		$fkArr = array_keys($fkList);
	} else {
		$fkArr = array();
	}

	$wArr = array(	'level'	=> (int)$level,
					'odbc_auto_complete_id'	=> (int)$auto_comp_id );
	$odbc_info = getTableInfo($db,'odbc_mapping',array(),$wArr,'queryAll');
	$tmp_odbc_fields = array();
	foreach($odbc_info AS $row) {
		$tmp_odbc_fields[$row['odbc_name']] = array(
							'pk'			=> (($row['previous_value']) ? 1 : 0), 
							'fk'			=> (in_array($row['odbc_name'],$fkArr) ? 1 : 0),
							'quoted'		=> (($row['quoted']) ? 1 : 0),
							'op'			=> (($row['where_op']) ? $row['where_op'] : ''),
							'id'			=> $row['id'], 
							'odbc_trans'		=> $row['odbc_name'], 
							'level'			=> $row['level'] );
	}

	//this foreach will update any necessary information
	foreach($odbc_fields AS $key => $value) {
		if(array_key_exists($key,$tmp_odbc_fields)) {
			$diffArr = array_diff_assoc($value,$tmp_odbc_fields[$key]);
			if(sizeof($diffArr) > 0) {
				if(array_key_exists('fk',$diffArr)) {
					//this will delete all levels attached to the fk that was unselected
					if($diffArr['fk'] == 0) {
						$deleteLevelArr = array();
						recLevelLinking($db,$level,$auto_comp_id,$deleteLevelArr);
						foreach($deleteLevelArr AS $lev) {
							$wArr = array(	'level'					=> (int)$lev,
											'odbc_auto_complete_id'	=> (int)$auto_comp_id );
							deleteTableInfo($db,'odbc_mapping',$wArr);
						}

						if( isSet($diffArr['pk']) ) {
							$diffArr['previous_value'] = $diffArr['pk'];
							unSet($diffArr['pk']);
						}
						if( isSet($diffArr['op']) ) {
							$diffArr['where_op'] = $diffArr['op'];
							unSet($diffArr['op']);
						}

						unset($diffArr['fk']);
						if(sizeof($diffArr) > 0) {
							updateTableInfo($db,'odbc_mapping',$diffArr,array('id'=>(int)$tmp_odbc_fields[$key]['id']));
						}
					} 
				}

			}
			unset($odbc_fields[$key]);
			unset($tmp_odbc_fields[$key]);
		}
	}

	foreach($tmp_odbc_fields as $v) {
		if($v['fk'] == 1) {
			$deleteLevelArr = array();
			recLevelLinking($db,$level,$auto_comp_id,$deleteLevelArr);
			foreach($deleteLevelArr AS $lev) {
				$wArr = array(	'level'					=> (int)$lev,
								'odbc_auto_complete_id'	=> (int)$auto_comp_id );
				deleteTableInfo($db,'odbc_mapping',$wArr);
			}
		}
		deleteTableInfo($db,'odbc_mapping',array('id'=>(int)$v['id']));
	}
	
	return $mapping_is_single_level;
}

function recLevelLinking($db,$level,$auto_id,&$deleteLevelArr) {
	$sArr = array('level');
	$wArr = array(	"odbc_trans_level" => (int)$level,
					"odbc_auto_complete_id" => (int)$auto_id );
	$levelArr = getTableInfo($db,'odbc_mapping',$sArr,$wArr,'queryCol');
	foreach($levelArr AS $newLevel) {
		$deleteLevelArr[] = $newLevel;
		recLevelLinking($db,$newLevel,$auto_id,$deleteLevelArr);
	}	
}

function checkOdbcAutoComplete( $db, $connID, $cabinet, $location = "") {
	//check odbc mapping for this entry	
	$whereArr = array( 
			'connect_id' => (int)$connID,
			'cabinet_name' => $cabinet,
			);
	if( $location != "" ) {
		$whereArr[0]['location'] = $location;
	}
	$selArr = array( 'id' );		
	$row = getTableInfo($db,'odbc_auto_complete',$selArr,$whereArr,'queryOne');
	return $row;
}

function createODBCAutoComplete( 
			$db, 
			$cab, 
			$connID, 
			$location = '', 
			$searchVal = '',
			$tablename = '' ){
	lockTables( $db, array('odbc_auto_complete') );
	$insArr = array( 'cabinet_name' => $cab,
			'connect_id' => (int)$connID,
	);

	if($location != '') {
		$insArr['location'] = $location;
	}
	if($searchVal != '') {
		$insArr['lookup_field'] = $searchVal;
	}
	if($tablename != '') {
		$insArr['table_name'] = $tablename;
	}
	$res = $db->extended->autoExecute('odbc_auto_complete',$insArr);
	$id = getTableInfo( $db, 'odbc_auto_complete', array('MAX(id)'), array(), 'queryOne' );
	unlockTables( $db );
	return $id;		
}

function removeMapping($user, $en, $db_doc, $db_dept) {
	$connID = $en['connection_id'];
	$cab = $en['cab_name'];
	$auto_id = checkOdbcAutoComplete($db_dept,$connID,$cab);
	$wArr = array(	'connect_id' => (int)$connID,
						'cabinet_name' => $cab );
	$row = getTableInfo($db_dept,'odbc_auto_complete',array('id'),$wArr,'queryCol');
	if(sizeof($row) > 0) {
		$whereArr = array('k' => 'indexing_'.$cab);
		deleteTableInfo($db_doc,'settings',$whereArr);
	}

	foreach($row AS $auto_id) {
		$whereArr = array(	'cabinet_name'			=> $cab,
							'odbc_auto_complete_id' => (int)$auto_id );
		deleteTableInfo($db_dept,'odbc_mapping',$whereArr);
	}
	deleteTableInfo($db_dept, 'odbc_auto_complete', $wArr);

	if (substr (PHP_VERSION, 0, 1) == '4') {
		$doc = domxml_new_doc ('1.0');
		$root = $doc->create_element ('ROOT');
		$root->set_attribute ('page', 8);
		$doc->append_child ($root);
		$xmlStr = $doc->dump_mem (false);
	} else {
		$doc = new DOMDocument ();
		$root = $doc->createElement ('ROOT');
		$root->setAttribute ('page', 8);
		$doc->appendChild ($root);
		$xmlStr = $doc->saveXML ();
	}
	header ('Content-type: text/xml');
	echo $xmlStr;	
}

function xmlSetODBCMapping( $user, $en, $db_doc, $db_dept )
{
	$missingArray = array ();
	//get cabinet_name
	$cab = $en['cab_name'];
	if( $cab == "" ){
		$missingArray[] = 'cab_name';
	}
	//get odbc_tablename
	$odbc_tablename = $en['odbc_table'];
	if( $odbc_tablename == "" ){
		$missingArray[] = 'odbc_table';
	}
	//get odbc leve
	$odbc_level = $en['odbc_level'];
	if( $odbc_level == "" ){
		$missingArray[] = 'odbc_level';
	}
	//get connection id
	$connID = $en['connection_id'];
	if( $connID == "" ){
		$missingArray[] = 'connection_id';
	}
	//get odbc_fields
	$odbc_field_count = $en['odbc_field_count'];
	if( $odbc_field_count == "" ){
		$missingArray[] = 'odbc_field_count';
	}
	//return error xml	
	if( sizeof( $missingArray ) > 0 ){
		$errorXML = "<error>missing fields: ";
		$errorXML .= implode( " ", $missingArray );
		$errorXML .= "</error>";
		header ('Content-type: text/xml');
		echo $errorXML;	
	}

	$auto_id = checkOdbcAutoComplete($db_dept,$connID,$cab);
	if( !$auto_id ) {
		$auto_id = createODBCAutoComplete( $db_dept, $cab, $connID );
	}

	$lookup_field = "";
	//grab per field items here!
	$insertArray = array();
	for( $i = 0; $i< $odbc_field_count; $i++ ){
		$odbc_fields[$en["odbc_fieldname$i"]] = array( 
					'pk' 		=> $en["pk$i"],
					'quoted'	=> $en["quoted$i"],
					'fk'		=> ($en['fk'.$i]) ? 1 : 0,
					'op'		=> ($en['op'.$i]) ? $en['op'.$i] : '');
	
		if( ($en['odbc_level'] == 1) AND ($en["pk$i"] == 1) ) {
			$lookup_field = $en["odbc_fieldname$i"];
		}

		if(isset($en['odbc_trans_value']) && ($en["odbc_trans_value"] == $en['odbc_fieldname'.$i])) {
			$wArr = array(	'odbc_trans' => $en['odbc_trans_name'],
							'level' => (int)$en['odbc_level'],
							'odbc_auto_complete_id' => (int)$auto_id );
			//make each insert array
			$sArr = array(	'odbc_name'		=> "{$en['odbc_fieldname'.$i]}", 
							'table_name'	=> $odbc_tablename,
							'quoted'		=> (int)$en["quoted$i"]);
			updateTableInfo($db_dept,'odbc_mapping',$sArr,$wArr,0,0,1);
		}
	}
	if( $lookup_field != "" ) {
		updateTableInfo($db_dept, 'odbc_auto_complete', array('lookup_field' => $lookup_field),
			array('id' => (int)$auto_id));
	}

	$whereArr = array(	"odbc_trans != ''",
						"odbc_auto_complete_id=".(int)$auto_id );
	$fkList = getTableInfo($db_dept,'odbc_mapping',array('odbc_trans','level'),$whereArr,'getAssoc');
	$mapping_is_single_level = updateOdbcMappingLevel($db_dept,$cab,$odbc_level,$auto_id,$odbc_fields,$fkList);
	//key is odbc_trans and odbc_name

	foreach( $odbc_fields as $key => $value ) {
		if( $value['fk']==1 ) {
			$mapping_is_single_level = false;
			if( !nextLevelMappingExists( $db_dept, $auto_id, $key ) ) {
				addNextLevelMapping( $db_dept, $auto_id, $cab, $key, $odbc_level );
			}
		} 
		//make each insert array
		$insertArray = array( 
			'cabinet_name' => $cab,
			'level' => (int)$odbc_level,
			'odbc_name' => $key, 
			'docutron_name' => $key,
			'table_name' => $odbc_tablename,
			'where_op' => "{$value['op']}",
			'quoted' => (int)$value['quoted'],
			'previous_value' => (int)$value['pk'],
			'odbc_auto_complete_id' => (int)$auto_id
				);//info that should be inserted
		if (isset ($value['odbc_trans'])) {
			$insertArray['odbc_trans'] = "{$value['odbc_trans']}";
		}
		$res = $db_dept->extended->autoExecute('odbc_mapping',$insertArray);	
		dbErr($res);
	}
	$docInfo = array ();
	$docInfo['auto_id'] = $auto_id;
	//CASE FOR SINGLE LEVEL
	if ($mapping_is_single_level and !xmlCheckNextLevel ($db_dept, $en, $auto_id)) {
		$indice = getCabinetInfo ($db_dept, $cab);
		$docInfo['page'] = 5;
		$docInfo['indices'] = $indice;
		
		//get all odbc_field names
		$sArr = array ();
		$wArr = array ('cabinet_name'			=> $cab,
						'odbc_auto_complete_id' => (int)$auto_id);
						//'odbc_trans'			=> '' );	
		$flds = getTableInfo($db_dept,'odbc_mapping',$sArr,$wArr,'queryAll');
		$docInfo['odbcInfo'] = array ();
		
		foreach ($flds as $row) {
			$tmpArr = array (
					'odbc_name' => $row['odbc_name'],
					'id' => $row['id'],
					);
			if($row['odbc_name'] != $row['docutron_name']) {
				$tmpArr['value'] = $row['docutron_name'];
			} else {
				$tmpArr['value'] = '';
			}
			$docInfo['odbcInfo'][] = $tmpArr;
		}

		if (substr (PHP_VERSION, 0, 1) == '4') {
			$doc = domxml_new_doc ('1.0');
			$root = $doc->create_element ('ROOT');
			$root->set_attribute ('auto_id', $docInfo['auto_id']);
			$doc->append_child ($root);
			$root->set_attribute( 'page', 5 );
			$doc->append_child( $root );
			foreach ($docInfo['indices'] as $index) {
				$el = $doc->create_element ('INDEX');
				$el->append_child ($doc->create_text_node ($index));
				$root->append_child ($el);
			}
			foreach ($docInfo['odbcInfo'] as $odbc) {
				$el = $doc->create_element ('ODBC_NAME');
				$el->append_child ($doc->create_text_node ($odbc['odbc_name']));
				$el->set_attribute ('id', $odbc['id']);
				$el->set_attribute ('value', $odbc['value']);
				$root->append_child ($el);
			}
			$xmlStr = $doc->dump_mem (false);
		} else {
			$doc = new DOMDocument ();
			$root = $doc->createElement ('ROOT');
			$root->setAttribute ('auto_id', $docInfo['auto_id']);
			$root->setAttribute ('page', 5);
			$doc->appendChild ($root);
			foreach ($docInfo['indices'] as $index) {
				$el = $doc->createElement ('INDEX');
				$el->appendChild ($doc->createTextNode ($index));
				$root->appendChild ($el);
			}
			foreach ($docInfo['odbcInfo'] as $odbc) {
				$el = $doc->createElement ('ODBC_NAME');
				$el->appendChild ($doc->createTextNode ($odbc['odbc_name']));
				$el->setAttribute ('id', $odbc['id']);
				$el->setAttribute ('value', $odbc['value']);
				$root->appendChild ($el);
			}
			$xmlStr = $doc->saveXML ();
		}
		
		header ('Content-type: text/xml');
		echo $xmlStr;	
	} else {
		$en1['connection_id']	= $en['connection_id'];
		$en1['cab_name']		= $en['cab_name'];
		$en1['odbc_level']		= $en['odbc_level']+1;
		$odbc_trans = $en["odbc_fieldname".($en['odbc_level']-1)];
		//make xml for multiple levels
		
		if (substr (PHP_VERSION, 0, 1) == '4') {
			$doc = domxml_new_doc ('1.0');
			$root = $doc->create_element ('ROOT');
			$root->set_attribute ('auto_id', $docInfo['auto_id']);
			$root->set_attribute ('page', 4);
			$root->set_attribute ('multi_level', 1);
			$root->set_attribute ('odbc_level', $en1['odbc_level']);
			$root->set_attribute ('odbc_trans', $odbc_trans);
			$doc->append_child ($root);
		} else {
			$doc = new DOMDocument ();
			$root = $doc->createElement ('ROOT');
			$root->setAttribute ('auto_id', $docInfo['auto_id']);
			$root->setAttribute ('page', 4);
			$root->setAttribute ('multi_level', 1);
			$root->setAttribute ('odbc_level', $en1['odbc_level']);
			$root->setAttribute ('odbc_trans', $odbc_trans);
			$doc->appendChild ($root);
		}
		
		xmlGetODBCCabinetList($user,$en1,$doc,$root, $db_doc, $db_dept);
	}
}

function getODBCColumns($db,$connID,$odbc_table_name,&$cols,&$refCols,$auto_id='',$level='', $db_doc) {
	//need to get fk
	if($auto_id) {
		$whereArr = array(	"odbc_trans != ''",
					"odbc_trans_level=".(int)$level,
					"odbc_auto_complete_id=".(int)$auto_id );
		$fks = getTableInfo($db,'odbc_mapping',array('odbc_trans'),$whereArr,'queryCol');
	} else {
		$fks = array ();
	}

	$db_object = getODBCObject($connID,$db_doc);
	$results = $db_object->queryAll('||SQLColumns|||'.$odbc_table_name);
	if(!PEAR::isError($results)) {
		foreach($results AS $res) {
			$num = sizeof($cols);
			$cols[$num]['odbc_name'] = $res['column_name'];
		
			if(in_array($res['column_name'],$fks)) {
				$cols[$num]['fk'] = 1;
			} else {
				$cols[$num]['fk'] = 0;
			}
			$refCols[$res['column_name']] = $num;
		}
	} else {
		$results = getTableColumnInfo ($db_object, $odbc_table_name);
		if(!PEAR::isError($results)) {
			foreach($results AS $res) {
				$num = sizeof($cols);
				$cols[$num]['odbc_name'] = $res;
			
				if(in_array($res,$fks)) {
					$cols[$num]['fk'] = 1;
				} else {
					$cols[$num]['fk'] = 0;
				}
				$refCols[$res] = $num;
			}
		}
	}
}

function fillColumnData($db,&$cols,$refCols,$wArr='') {
	$sArr = array(	'odbc_name',
					'odbc_trans',
					'previous_value',
	//				'logical_op',
	//				'grouping',
					'quoted' );
	if($wArr) {
		$odbcInfo = getTableInfo($db,'odbc_mapping',$sArr,$wArr,'queryAll');	
		foreach($odbcInfo AS $odbcEntry) {
			$num = 0;
			foreach($odbcEntry AS $key => $value) {
				if($key == 'odbc_name') {
					$num = $refCols[$value];
					unset($refCols[$value]);
				} else {
					$cols[$num][$key] = $value;
				}
			}
		}
	}
	
	unset($sArr[0]);
	foreach($refCols AS $num) {
		foreach($sArr AS $key) {
			$cols[$num][$key] = '';
		}
	}
}

function xmlCheckNextLevel( $db, $en, $auto_id )
{
	//checks to see if there is another level
	$whereArr = array(	'odbc_auto_complete_id'	=> (int)$auto_id,
				'level'			=> (int)$en['odbc_level']+1 );
	return getTableInfo($db,'odbc_mapping',array('COUNT(level)'),$whereArr,'queryOne');	
}

function nextLevelMappingExists( $db, $auto_id, $odbc_trans )
{
	//checks for a specific level
	//check if the mapping exists that uses this odbc_trans
	//return true if it exists
	$whereArr = array(	'odbc_auto_complete_id'	=> (int)$auto_id,
						'odbc_trans'			=> $odbc_trans );
	return getTableInfo($db,'odbc_mapping',array('COUNT(id)'),$whereArr,'queryOne');	
}

function addNextLevelMapping( $db, $auto_id, $cab, $odbc_trans,$odbc_trans_level ) 
{
	//adds the next level of odbc mapping without odbc_table/odbc_name/docutron_name
	$insertArr = array(	"cabinet_name"			=> $cab,
						"level"					=> (int)getNextLevel($db,$auto_id),
						"previous_value"		=> 1,
						"odbc_trans"			=> $odbc_trans,
						"odbc_trans_level"		=> (int)$odbc_trans_level,
						"where_op"				=> "=",
						"odbc_auto_complete_id"	=> (int)$auto_id );
	$db->extended->autoExecute('odbc_mapping',$insertArr);
}

function getNextLevel($db, $auto_id) {
	$whereArr = array('odbc_auto_complete_id'=>(int)$auto_id);
	$max = getTableInfo($db,'odbc_mapping',array('MAX(level)+1'),$whereArr,'queryOne');	
	if($max) {
		return $max;
	}
	return 2;
}

function updateODBCMapping( $user, $en, $db_doc, $db_dept)
{
	$mapping_count = $en['mapping_count'];
	$auto_id = $en['auto_id'];
	//set ones without id to and id and set the docutron name to ""
	//you don't have the variable $cab or $auto_id
	$row_ids = getOdbcMappingIds( $db_dept, $auto_id );
	//get id list from en and delete them from row_ids
	for( $i=0; $i < $mapping_count; $i++ ) {
		$en_ids[$en["id$i"]] = $en["id$i"];
	}
	$ct = sizeof($row_ids);
	for($i=0;$i<$ct;$i++) {
		if(in_array($row_ids[$i],$en_ids) ){
			unset($row_ids[$i]);
		}
	}
	$row_ids = array_values( $row_ids );
	$counter = $mapping_count;
	foreach( $row_ids as $id ) {
		$en["id$counter"] = $id;
		$en["docutron_name$counter"] = (string)"";
		$counter++;
	}
	
	for( $i = 0; $i < $counter; $i++ ) {
		$updateArr = array('docutron_name'=>"{$en["docutron_name$i"]}");
		$whereArr = array( 'id' => (int)$en["id$i"] );
		updateTableInfo( $db_dept,'odbc_mapping',$updateArr,$whereArr,0,0,1 );
	}

	$whereArr = array('previous_value' => 1, 'odbc_trans' => '', 'cabinet_name' => $en['cab_name']);
	$lookup = getTableInfo($db_dept,'odbc_mapping',array('docutron_name'),$whereArr,'queryOne');
	if($lookup != NULL AND $lookup != "") {
		$updateArr = array('lookup_field' => $lookup);
		$whereArr = array('id'  => (int)$auto_id);
		updateTableInfo($db_dept,'odbc_auto_complete',$updateArr,$whereArr,0,0,1);
	}

	$indice = array ();
	if (isset($en['searchValue'])) {
		$page = 5;
		$gblStt = new GblStt ($user->db_name, $db_doc);
		$row = searchAutoComplete($db_dept,'odbc_auto_complete','',
			$en['searchValue'],$en['cab_name'], $db_doc,
			'', $user->db_name, $gblStt);
		$indice = getCabinetInfo ($db_dept, $en['cab_name']);
	} else {
		$page = 6;
	}
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$doc = domxml_new_doc ('1.0');
		$root = $doc->create_element ('ROOT');
		$doc->append_child ($root);
		if ($indice) {
			$el = $doc->create_element ('TEST');
			$root->append_child($el);
			foreach ($indice as $ind) {
				$el2 = $doc->create_element ('KEY');
				$el2->append_child ($doc->create_text_node ($ind));
				$el->append_child ($el2);
				$el2 = $doc->create_element ('KEY');
				$el2->append_child ($doc->create_text_node ($row[$ind]));
				$el->append_child ($el2);
			}
		}
		$root->set_attribute ('page', $page);
		$xmlStr = $doc->dump_mem (false);
	} else {
		$doc = new DOMDocument ();
		$root = $doc->createElement ('ROOT');
		$doc->appendChild ($root);
		if ($indice) {
			$el = $doc->createElement ('TEST');
			$root->appendChild($el);
			foreach ($indice as $ind) {
				$el2 = $doc->createElement ('KEY');
				$el2->appendChild ($doc->createTextNode ($ind));
				$el->appendChild ($el2);
				$el2 = $doc->createElement ('KEY');
				if (isset ($row[$ind])) {
					$myTxt = $row[$ind];
				} else {
					$myTxt = '';
				}
				$el2->appendChild ($doc->createTextNode ($myTxt));
				$el->appendChild ($el2);
			}
		}
		$root->setAttribute ('page', $page);
		$xmlStr = $doc->saveXML ();
	}
	header ('Content-type: text/xml');
	echo $xmlStr;	
}

function getOdbcMappingIds( $db, $auto_id )
{
	$sArr = array( 'id' );
	$wArr = array('odbc_auto_complete_id'=>(int)$auto_id );
	return getTableInfo( $db, 'odbc_mapping', $sArr, $wArr, 'queryCol' );
}

/*function xmlGetODBCLevel( $cabinet, $level, $db )
{
	$selArr = array( 
		'id', 
		'cabinet_name', 
		'level', 
		'odbc_name', 
		'docutron_name',
		'table_name',
		'previous_value',
		'odbc_trans',
		'where_op',
		'odbc_auto_complete_id',
		'logical_op',
		'grouping',
		'quoted' );
		
	//return getTableInfo( $db, 'odbc_mapping', 
}*/


/*function xmlInsertODBCMappingRow( $db, $rowArr )
{
	//insert row into odbc_mapping tool
}
*/


function odbcMappingView($connID, $cabinet, $user, $db_object) {
	$headerArr = array(
			'level'			=> 'Level',
			'cabinet_name'		=> 'Cabinet',
			'docutron_name'		=> 'Cabinet Indice Name',
			'table_name'		=> 'ODBC Table',
			'odbc_name'		=> 'ODBC Field Name',
			'odbc_trans'		=> 'ODBC Trans'	
			  );

	$whereArr = array(
			'cabinet_name'=>$cabinet,
			'connect_id'=>(int)$connID);
	$odbcAutoCompID = getTableInfo(
			$db_object,
			'odbc_auto_complete'
			,array('id'),
			$whereArr,'queryOne');
	$whereArr = array(
			'cabinet_name'=>$cabinet,
			'odbc_auto_complete_id'=>(int)$odbcAutoCompID);
	$odbcMapList = getTableInfo(
			$db_object,
			'odbc_mapping',
			array_keys($headerArr),
			$whereArr,
			'queryAll',
			array('level'=>'ASC'));
 	if (substr (PHP_VERSION, 0, 1) == '4') {
 		$xmlDoc = domxml_new_doc ('1.0');
 		$root = $xmlDoc->create_element ('ROOT');
 		$xmlDoc->append_child ($root);
 		foreach (array_values ($headerArr) as $entry) {
 			$el = $xmlDoc->create_element ('HEADER');
 			$root->append_child ($el);
 			$el->set_attribute ('index', $entry);
 		}
 
 		foreach ($odbcMapList as $entry) {
 			$el = $xmlDoc->create_element ('ENTRY');
 			$root->append_child ($el);
 			foreach ($entry as $key => $value) {
 				$el->set_attribute ($key, $value);
 			}
 		}
 		$xmlStr = $xmlDoc->dump_mem (false);
 	} else {
 		$xmlDoc = new DOMDocument ();
 		$root = $xmlDoc->createElement ('ROOT');
 		$xmlDoc->appendChild ($root);
 		foreach (array_values ($headerArr) as $entry) {
 			$el = $xmlDoc->createElement ('HEADER');
 			$root->appendChild ($el);
 			$el->setAttribute ('index', $entry);
 		}
 
 		foreach ($odbcMapList as $entry) {
 			$el = $xmlDoc->createElement ('ENTRY');
 			$root->appendChild ($el);
 			foreach ($entry as $key => $value) {
 				$el->setAttribute ($key, $value);
 			}
  		}
 		$xmlStr = $xmlDoc->saveXML ();
  	}
	header ('Content-type: text/xml');
	echo $xmlStr;	
}

function xmlGetODBCCabinetList ($user, $en, &$doc, &$root, $db_doc, $db) {
 	global $DEFS;
 	$docInfo = array ();
 	$docInfo['ROOT'] = array ();
 	$docInfo['ROOT']['attribs'] = array ();
 	$docInfo['TABLE'] = array ();
 	$error = false;
 	$auto_id = checkOdbcAutoComplete ($db, $en['connection_id'], $en['cab_name']);
 	$odbc_table_name = '';
 	if ($auto_id) {
 		$wArr = array ('odbc_auto_complete_id'	=> (int) $auto_id,
 					   'level'					=> (int) $en['odbc_level']);
 		$odbc_table_name = getTableInfo ($db, 'odbc_mapping',
 				array('table_name'), $wArr, 'queryOne');
 		if (PEAR::isError($odbc_table_name)) {
 			echo "error when connecting to odbc_mapping table";
 		}
 
 		$wArr = array ('odbc_auto_complete_id='.(int) $auto_id,
 					   'level='.(int) $en['odbc_level'],
 					   "odbc_trans != ''");
 		$trans = getTableInfo ($db, 'odbc_mapping',
 				array('odbc_trans','odbc_name'), $wArr, 'queryRow');
 		if (!PEAR::isError ($trans)) {
 			if( sizeof ($trans) > 0) {
 				$docInfo['ROOT']['attribs']['odbc_trans'] = $trans['odbc_trans'];
 				$docInfo['ROOT']['attribs']['odbc_trans_value'] = $trans['odbc_name'];
 			}
 		} else {
 			echo "foreign keys are broken";
 		}
 	}
 
 	$db_odbc = getODBCObject ($en['connection_id'], $db_doc);
 	if (!$db_odbc) {
 		$error = true;
 		$docInfo['ROOT']['attribs']['error'] = 'Error connecting to server';
 		//DUMP
 	} else {
 		$results = $db_odbc->queryAll ('||SQLTables');
  		if(!PEAR::isError($results)) {
  			foreach($results AS $res) {
 				$docInfo['TABLE'][] = array ('KEY' => 'odbc_table', 'VALUE' =>
 							$res['table_name']);
  			}
  		} else {
 			$results = $db_odbc->manager->listTables();
 			if(!PEAR::isError ($results)) {
 				foreach($results AS $res) {
 					$docInfo['TABLE'][] = array ('KEY' => 'odbc_table', 'VALUE' =>
 							$res);
 				}
 			} else {
 				$docInfo['ROOT']['attribs']['error'] = 'Cannot retrieve the odbc tables';
 			}
 			$results = $db_odbc->manager->listViews();
 			if(!PEAR::isError ($results)) {
 				foreach($results AS $res) {
 					$docInfo['TABLE'][] = array ('KEY' => 'odbc_table', 'VALUE' =>
						$res);
 				}
 			}
  		}
  	}
 	if (substr (PHP_VERSION, 0, 1) == '4') {
 		if (!$doc) {
 			$doc = domxml_new_doc ('1.0');
 			$root = $doc->create_element ('ROOT');
 			$doc->append_child ($root);
 			$root->set_attribute ('multi_level', 0);
 		}
 		foreach ($docInfo['ROOT']['attribs'] as $key => $attrib) {
 			$root->set_attribute ($key, $attrib);
 		}
 		foreach ($docInfo['TABLE'] as $res) {
 			$tableEl = $doc->create_element ('TABLE');
 			$root->append_child($tableEl);
 			$el = $doc->create_element ('KEY');
 			$el->append_child ($el->create_text_node ($res['KEY']));
 			$tableEl->append_child ($el);
 			$el = $doc->create_element ('VALUE');
 			$el->append_child ($el->create_text_node ($res['VALUE']));
 			$tableEl->append_child ($el);
 		}
 		$xmlStr = $doc->dump_mem (false);
 	} else {
 		if (!$doc) {
 			$doc = new DOMDocument ();
 			$root = $doc->createElement ('ROOT');
 			$doc->appendChild ($root);
 			$root->setAttribute ('multi_level', 0);
 		}
 		foreach ($docInfo['ROOT']['attribs'] as $key => $attrib) {
 			$root->setAttribute ($key, $attrib);
 		}
 		foreach ($docInfo['TABLE'] as $res) {
 			$tableEl = $doc->createElement ('TABLE');
 			$root->appendChild ($tableEl);
 			$el = $doc->createElement ('KEY');
 			$el->appendChild ($doc->createTextNode ($res['KEY']));
 			$tableEl->appendChild ($el);

			$myTxt = (isSet($res['VALUE'])) ? $res['VALUE'] : "";
 			$el = $doc->createElement ('VALUE');
 			$el->appendChild ($doc->createTextNode ($myTxt));
 			$tableEl->appendChild ($el);
 		}
 		$xmlStr = $doc->saveXML();
 	}
 	
 	if($odbc_table_name and !$error) {
 		xmlGetODBCCabinetColumnList($user,$en,$doc,$root, $db_doc, $db);
 		if (substr (PHP_VERSION, 0, 1) == '4') {
 			$xmlStr = $doc->dump_mem(false);
 		} else {
 			$xmlStr = $doc->saveXML ();
 		}
 	}
	header ('Content-type: text/xml');
	echo $xmlStr;	
}

//editOdbcMappingRow
function editOdbcMappingRow( $user, $id, $updateArray, $db  )
{
	$idArr = array( 'id'=>$id );
	updateTableInfo( $db,'odbc_mapping',$updateArray,$idArr ); 
}
//editOdbcMappingField
//addOdbcMapping
function xmlGetODBCCabinetColumnList ($user, $en, &$doc, &$root, $db_doc, $db) {
	$cols = array ();
	$refCols = array ();
	$odbcTable = '';
	
	$auto_id = checkOdbcAutoComplete ($db, $en['connection_id'],
			$en['cab_name']);
	if ($auto_id) {
		$wArr = array('odbc_auto_complete_id'	=> (int) $auto_id,
					  'level'					=> (int) $en['odbc_level']);
		$odbc_table_name = getTableInfo ($db, 'odbc_mapping',
				array('table_name'), $wArr, 'queryOne');
		if(PEAR::isError($odbc_table_name)) {
			echo "error when connecting to odbc_mapping table";
			$odbc_table_name = '';
		}
	} else {
		$odbc_table_name = '';
	}

	if (!isset ($en['odbc_table'])) {
		$en['odbc_table'] = '';
	}
	if ($odbc_table_name != $en['odbc_table']) {
		if ($en['odbc_table']) {
			$odbcTable = $en['odbc_table'];
			getODBCColumns ($db, $en['connection_id'], $en['odbc_table'], $cols,
					$refCols, '', '', $db_doc);
			fillColumnData ($db, $cols, $refCols);
		} else {
			$odbcTable = $odbc_table_name;
			getODBCColumns ($db, $en['connection_id'], $odbc_table_name, $cols,
					$refCols, $auto_id, $en['odbc_level'], $db_doc);
			fillColumnData ($db, $cols, $refCols, $wArr);
		}
	} else {
		$odbcTable = $odbc_table_name;
		getODBCColumns ($db, $en['connection_id'], $odbc_table_name, $cols,
				$refCols, $auto_id, $en['odbc_level'], $db_doc);
		fillColumnData ($db, $cols, $refCols, $wArr);
	}

	if (substr (PHP_VERSION, 0, 1) == '4') {
		if (!$doc) {
			$doc = domxml_new_doc ('1.0');
			$root = $doc->create_element ('ROOT');
			$doc->append_child ($root);
			$root->set_attribute ('multi_level', 0);
		}
		$root->set_attribute ('table_name', $odbcTable);
		foreach ($cols as $column) {
			$el = $doc->create_element ('COLUMN');
			foreach ($column as $key => $value) {
				$el2 = $doc->create_element ('KEY');
				$el2->append_child ($doc->create_text_node ($key));
				$el->append_child ($el2);
				$el2 = $doc->create_element ('VALUE');
				$el2->append_child ($doc->create_text_node ($value));
				$el->append_child ($el2);
			}
			$root->append_child ($el);
		}
	} else {
		if (!$doc) {
			$doc = new DOMDocument ();
			$root = $doc->createElement ('ROOT');
			$doc->appendChild ($root);
			$root->setAttribute ('multi_level', 0);
		}
		$root->setAttribute ('table_name', $odbcTable);
		foreach ($cols as $column) {
			$el = $doc->createElement ('COLUMN');
			foreach ($column as $key => $value) {
				$el2 = $doc->createElement ('KEY');
				$el2->appendChild ($doc->createTextNode ($key));
				$el->appendChild ($el2);
				$el2 = $doc->createElement ('VALUE');
				$el2->appendChild ($doc->createTextNode ($value));
				$el->appendChild ($el2);
			}
			$root->appendChild ($el);
		}
	}

	//return $doc;
}

?>
