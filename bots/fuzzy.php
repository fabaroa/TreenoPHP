<?php

//$filename = $argv[1];
//saved output from above operation
//include '../db/db_engine.php';
include_once '../lib/settings.php';
include_once '../lib/utility.php';
include_once '../db/db_common.php';

echo "<form method=post >";
echo "<input type=text name=word><br>";
echo "<input type='radio' name='search_type' value='narrow' checked>Narrow";
echo "<input type='radio' name='search_type' value='wide'>Wide";
echo "<br><input type=submit name=submit>";
echo "</form>";
if (isset ($_POST['submit'])) {
	$db_object = getDbObject($user->db_name);
	//Get list of cabinets
	$allCabinets = getTableInfo($db_object, 'departments',
		array('real_name'), array('deleted' => 0), 'queryCol');

	$prefix = $DEFS['DATA_DIR']."/";

	print_r($_POST);
	$output = "accurate titlei 70 south rier roadbedforli1 h 03110 no3.66g.6qq6fax 6277356march 2 6, 2 o oqciti zens bone citi zens driveconser financerivers ide, ri o 2 9 l 5re. borrovers. street, nashua, newh h o 3 o 6 opropei-ty. streeto 3 o 6 oloan hiumber. (second ortage)dear sir or ,hadam.enclosed please rind a check in the amount of % representin the morteae pa)off on the abohe-referenced loan.the payoff is broken don as follos.total due. osaid stcond ortaeure has iecorded ith the hillsboroueurh registry of deeds at book g�63, phgje i 2o. recorded ondecember 9, 200i. please forard a discharge o m) attention at the ahoe address llpon)our receipt of these mortgacode(011d)epayuff funds. please contact this office at (603) 6g8-6446 if ,lou require assistance. if you are not the iecord holdei- of saidmortage. .thank v ou.the undersieurned heieby confi the request to full) close and dischare the abo1e referenced morteuraeure loan.hlo. ao4o575 f:le td no. ao4o575 f:ie id";
	//$output = str_replace( '(PICTURE)', '', $output );
	//$output = str_replace( "\n", "", $output );
	//$output = str_replace( "\t", "", $output );
	//$output = str_replace( "'", "", $output );
	//$output = str_replace( "\\", "", $output );
	//$output = str_replace( "/", "", $output );
	//$output = str_replace( "_", "", $output );
	//$output = str_replace( "?", "", $output );
	//$output = strtolower($output);
	if ($_POST['search_type'] == "narrow")
		$criteria = "400";
	else
		$criteria = "900";

	$needle = $_POST['word'];
	$cab_index = 0;
	foreach ($allCabinets as $cabinet_name) {
		$doc_index = 0;

		$whereArr = array(
			"display"	=> 1,
			"deleted"	=> 0
				 );
		$all_files = getTableInfo($db_object,$cabinet_name."_files",array(),$whereArr);
		while ($file = $all_files->fetchRow()) {
			//echo "string: $needle";

			$haystack = $file['ocr_context'];
			$file_match = false; //sets to true when a match in the file is found
			//echo "output lenght is ".strlen( $output )."<br>\n";
			// explode into words
			$hwords = preg_split("/[\s]+/", $haystack);
			$nwords = preg_split("/[\s\W]+/", $needle);
			//echo "You searched for $needle<br>\n";
			//echo "I found...<br>\n";
			//echo "<table border=1>\n";
			foreach ($hwords as $hkey => $hayword) {
				//echo "hwords as $hkey=>$hayword<br>";
				$hmp = metaphone($hayword);
				foreach ($nwords as $nkey => $needword) {
					// First or last letters of needle and haystack have to match (case insensitive)
					$nfirst = strtolower(substr($needword, 0, 1));
					$nlast = strtolower(substr($needword, -1));
					$hfirst = strtolower(substr($hayword, 0, 1));
					$hlast = strtolower(substr($hayword, -1));
					$nmp = metaphone($needword);
					//$distance = levenshtein ($hmp, $nmp);
					$distance = levenshtein($hayword, $needword);
					$n_len = strlen($nmp);
					$per = round(($distance / $n_len) * 1000);
					if ($per <= $criteria) {
						// Highlight word in haystack
						$file_match = true;
						$haystack = str_replace($hayword, "<b>$hayword</b>", $haystack);
						$haystack = str_replace("<b><b>", "<b>", $haystack);
						$haystack = str_replace("</b></b>", "</b>", $haystack);
					}
				}
			}
			if ($file_match) {
				//add document to array if it has not been added yet
				if ($results[$cabinet_name] == NULL || !in_array($file['id'], $results[$cabinet_name])) {
					$results[$cabinet_name][$doc_index] = $file['id'];
					$doc_index ++;
				}
			}
		}
	}
	echo "</table>";
	// echo the new haystack
	//echo $haystack;
	print_r($results);
	$db_object->disconnect ();
}
?>
