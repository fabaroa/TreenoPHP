<?php
require_once '../db/db_common.php';
require_once '../check_login.php';
require_once 'Image/Graph.php';

if($logged_in and $user->username and $user->isDepAdmin()) {
	$db = getDbObject('docutron');
	$date = $_GET['date'];
	$dept = $_GET['dept'];
	$beginning = strtotime("$date 00:00:00");
	$ending = strtotime("$date 23:59:59");
	$q = "SELECT currtime,num_used FROM license_util WHERE currtime >= $beginning AND currtime <= $ending AND department = '$dept' ORDER BY currtime ASC";
	$arr = $db->extended->getAssoc($q);
	$arbDept = getTableInfo ($db, 'licenses', array ('arb_department'), 
		array ('real_department' => $dept), 'queryOne');
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
?>
