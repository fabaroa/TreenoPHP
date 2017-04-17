<?php
require_once '../db/db_common.php';
require_once '../lib/settings.php';
if( !isset($DEFS['SITEMAP_PASSWORD']))
	die('empty');
if( $_REQUEST['password']==$DEFS['SITEMAP_PASSWORD'] ){
$db = getDbObject('docutron');
echo "<pre>";
$arbDept = getTableInfo ($db, 'licenses', array ('arb_department,real_department'), 
	array (), 'getAll');
foreach( $arbDept as $dept ){
	$db_dept = getDbObject($dept['real_department']);
	$cabArr = getTableInfo( $db_dept, 'departments', array('*'),array(),'getAll');
//	print_r( $cabArr );
//	die();
	foreach( $cabArr as $cab ){
		$folders=getTableInfo($db_dept,$cab['real_name'],array('*'),array(),'getAll');
		//skip cabinets with no folders in them
		if( sizeof( $folders ) == 0 )
			continue;
		echo "<table border=1>";
		echo "<tr><td>Department</td><td>Cabinet</td><td>File Name</td><td>subfolder</td><td>Index Fields</tr></tr>";
		foreach($folders as $folder){
			$files = getTableInfo($db_dept,$cab['real_name']."_files",array('filename','subfolder'),array('doc_id'=>$folder['doc_id']),'getAll');	
//			print_r( $files );
			foreach( $files as $file ){
				if( $file['filename']!='' ){
				echo "<tr>";
				echo "<td>".$dept['arb_department']."</td>";
				echo "<td>".$cab['departmentname']."</td>";
				echo "<td><b>".$file['filename']."</b></td>";
				if( $file['subfolder']=='' ){
					echo "<td>-</td>";
				}else{
					echo "<td>".$file['subfolder']."</td>";
				}
				unset($folder['doc_id']);
				unset($folder['deleted']);
				unset($folder['location']);
				echo "<td>".implode( " ", $folder )."</td>";
				echo "</tr>";
				}
			}
		}
		echo "</table>";
		echo "<br><br>";
	}
	//print_r( $cabArr );
	$db_dept->disconnect();
}
//print_r( $arbDept );
//die();
}else{
	echo "empty";
}
/*
$myGraph =& Image_Graph::factory('graph', array(900, 600));
$Font =& $myGraph->addNew('ttf_font', $DEFS['DOC_DIR'].'/lib/Vera.ttf');
$Font->setSize(8);
$myGraph->setFont($Font);
// create the plotarea layout
$Title =& $myGraph->addNew('title', array("License Utilization in $arbDept, $date", 20));
$Plotarea =& $myGraph->addNew('plotarea', array('Image_Graph_Axis', 'Image_Graph_Axis'));

$xLabelArr = array ();

$i = $beginning;

for($i = $beginning; $i < $ending; $i += 3600) {
	$xLabelArr[] = $i;
}

$xAxis =& $Plotarea->getAxis('x');
$xAxis->setTitle ('Time', array ('angle' => 0));
$xAxis->setLabelInterval($xLabelArr);
$xAxis->setDataPreprocessor(Image_Graph::factory('Image_Graph_DataPreprocessor_Date', 'g:ia'));
$xAxis->setFontAngle (90);
$xAxis->forceMinimum ($beginning);
$xAxis->forceMaximum ($ending);


$dataSet =& Image_Graph::factory('dataset');
foreach($arr as $time => $used) {
	$dataSet->addPoint($time, (int)$used);
}

$maxY = $dataSet->maximumY();

$yAxis =& $Plotarea->getAxis('y');
$Grid =& $Plotarea->addNew('line_grid', IMAGE_GRAPH_AXIS_Y);
$yAxis->forceMaximum($maxY + $maxY*0.1);
$yAxis->setTitle ('Licenses Used', 'vertical');

$Plot =& $Plotarea->addNew('area', $dataSet);
$Plot->setLineColor('black');
$Plot->setFillStyle(Image_Graph::factory('gradient', array(IMAGE_GRAPH_GRAD_VERTICAL, '#003b6f', 'black')));
$myGraph->done();
}
*/
?>
