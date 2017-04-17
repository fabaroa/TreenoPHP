<?php
include_once '../lib/utility.php';

//table info is the cab array of numbers to indices
//db_object db connection
//table you want to hash columns
//id value of the table
//id_name column that is the id field
//tableinfo is the columns names
//prehashTables tables for each field word
//prehashLists tables for each word list
function rehash( $db_object, $table, $id, $id_name, $tableInfo, $prehashTables, $prehashLists ) {
	//delete from lists all table_id
	foreach( $prehashLists as $list ) {
		deleteTableInfo ($db_object, $list,
			array ('table_id' => (int) $id));
	}
	$row = getTableInfo ($db_object, $table, array (),
		array ($id_name => (int) $id), 'queryRow');
	for( $i=0; $i < sizeof($tableInfo); $i++ ) {
		//forward words
		$hashWords = getHashWords( $row[$tableInfo[$i]] );
		insertHash( $db_object, $id, $hashWords, $prehashTables[$i], $prehashLists[$i] );
	}
}

function insertHash( $db_object, $table_id, $words, $hashTable, $listTable ) {
	//insert hash and id to the appropriate places
	//check if the word is in the table
	if( !is_array( $words ) )
		return;
	foreach( $words as $word ) {
		$rid = getTableInfo($db_object, $hashTable, array('id'), array('word' => $word), 'queryOne');
		if( !$rid ) {
			//lockTables($db_object, array($hashTable, $listTable);
			$res = $db_object->query( "INSERT INTO $hashTable VALUES ( '', '$word' )" );
			dbErr($res);
			$id = getTableInfo($db_object, $hashTable, array('MAX(id)'), array(), 'queryOne');
			$res = $db_object->query( "INSERT INTO $listTable VALUES ( '', $id, $table_id )" );
			dbErr($res);
			//unlockTables($db_object);
		} else {
			//lockTables($db_object, array($listTable));
			$res = 	$db_object->query( "INSERT INTO $listTable VALUES ( '', $rid, $table_id )" );
			dbErr($res);
			//unlockTables($db_object);
		}
	}	
}

function makeHashTables( $db_object, $prehashTables, $prehashLists ) {
	//check if tables exist
	foreach( $prehashTables as $t ) {
		makePrehashTable( $db_object, $t );
	}
	foreach( $prehashLists as $list ) {
		makePrehashList( $db_object, $list );
	}
	//get cabinet info
	//create tables for each field
	//if not make tables
	//if they exist do nothing
}

//TODO move this into the utility file
function makePrehashList( $db_object, $list ) {
	$createStatement = "CREATE TABLE $list ( id int(12) auto_increment, ";
	$createStatement .= "list_key int(12), table_id int(12), PRIMARY KEY ";
	$createStatement .= "( id ), KEY ( list_key ), KEY ( table_id ) )";
	$res = $db_object->query( $createStatement );
	dbErr($res);
}

//TODO move this into the utility file
function makePrehashTable( $db_object, $table ) {
	$createStatement = "CREATE TABLE $table ( id int(12) auto_increment, ";
	$createStatement .= "word VARCHAR(255) , PRIMARY KEY ";
	$createStatement .= "( id ), KEY ( word ) )";
	$res = $db_object->query( $createStatement );
	dbErr($res);
} 

function getHashTables ( $db_object, $table, $tableInfo, &$prehashTables, &$prehashLists ) {
	for($i=0; $i < sizeof($tableInfo); $i++ ) {
		$prehashTables[] = "$table"."_forward_$i";
		$prehashLists[] = "$table"."_flist_$i";
	}
}

function dropHashTables( $db_object, $prehashTables, $prehashLists ) {
	//drop all the hash tables
	//drop prehash tables
	foreach( $prehashTables as $table  ) {
		dropHashTable( $db_object, $table );
	}
	//drop prehashlists
	foreach( $prehashLists as $list ) {
		dropHashTable( $db_object, $list );
	}
}

function dropHashTable( $db_object, $table ) {
	$res = $db_object->query( "DROP TABLE $table" );
	dbErr($res);
}

//hack -zsf here
function searchHashTopLevel( $db_object,$search,$table,$prehashTables,$prehashLists) {
	$query = "SELECT $table.* FROM $table WHERE ";
	for($i=0;$i<sizeof($prehashTables);$i++) {
		$tmpQuery = "id=(SELECT table_id FROM $prehashLists[$i],$prehashTables[$i] WHERE word " . LIKE . " '$search%' ";
		$tmpQuery .= "AND $prehashTables[$i].id=list_key AND $prehashLists[$i].table_id=$table.id)";
		$hashSelect[] = $tmpQuery;
	}
	$query .= implode(" OR ",$hashSelect);
	echo $query."\n";
}

function getHashWords( $word ) {
	$wordList = array();
	$tmpWord = '';
	for( $i=0;$i<strlen($word)-1;$i++) {
		$newWord = substr( $word, $i, strlen( $word ) );
		if( !in_array($tmpWord,$wordList) ) {
			$wordList[] = $newWord;
		}
		for( $j=0;$j<strlen($newWord)-1;$j++) {
			$tmpWord = substr( $newWord, 0, strlen($newWord)-$j );
			if( !in_array($tmpWord,$wordList) ) {
				$wordList[] = $tmpWord;
			}
		}
	}
	return $wordList;
}

function searchWord( $db_object, $word, $cab, $index_number ) {
	
}

function getNumberHashResults( $db_object, $searchObj, $prehashTables, $prehashLists, $table, $table_id  ) {
	//$query = 
}
?>
