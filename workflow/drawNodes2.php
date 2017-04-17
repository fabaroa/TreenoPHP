<?php
require_once '../check_login.php';
error_reporting(E_ALL);
define('BOX_SIDE', '75');
define('HEAD_SIZE', '5');
define('BRUSH', '3');
$allNodes = $_SESSION['workflow_nodes'];
$numStates = $_SESSION['workflow_states'];
$states = array();
$globalCt = 0;
$j = 0;
for($i = 1; $i <= $numStates; $i++) {
	if($allNodes[$j]['state'] == $i) {
			$states[$i] = 0;
	}
	while(isset($allNodes[$j]) and $allNodes[$j]['state'] == $i) {
		$states[$i]++;
		$j++;
	}
}
$maxNodes = 0;
for($i = 1; $i <= $numStates; $i++) {
	if($states[$i] > $maxNodes) {
		$maxNodes = $states[$i];
	}
}

$board = array_pad(array(),
				   $numStates * 2 + 1,
				   array_pad(array(),
							 $maxNodes * 2 + 1,
							 0));
$boxLocs = array();

$j = 0;
for($i = 1; $i <= $numStates; $i++) {
	$row = $i * 2 - 1;
	for($k = 1; $k <= $states[$i]; $k++) {
		$col = $k * 2 - 1;
		$boxLocs[$allNodes[$j]['id']] = array('row' => $row, 'col' => $col);
		$board[$row][$col] = $allNodes[$j]['id'];
		$j++;
	}
}
$image = @imagecreatetruecolor(($maxNodes * 2 + 1) * BOX_SIDE,
							   ($numStates * 2 + 1) * BOX_SIDE);

$backgroundColor = imagecolorallocate($image, 255, 255, 255);
imagefilltoborder($image, 0, 0, $backgroundColor, $backgroundColor);
$boxColor = imagecolorallocate($image, 127, 127, 127);
$nextColor = imagecolorallocate($image, 51, 153, 51);
$prevColor = imagecolorallocate($image, 255, 0, 0);
imagesetthickness($image, BRUSH);

foreach($allNodes as $myNode) {
	drawNode($image, $boxLocs[$myNode['id']], $boxColor);
	if($myNode['id'] != $myNode['next'] and $myNode['next'] != 0) {
		$points = findPath($boxLocs[$myNode['id']],
						   $boxLocs[$myNode['next']],
						   $board);
		drawPath($image, $points, $nextColor, $board);
	}
	if($myNode['id'] != $myNode['prev'] and $myNode['next'] != 0) {
		$points = findPath($boxLocs[$myNode['id']], 
						   $boxLocs[$myNode['prev']],
						   $board);
		drawPath($image, $points, $prevColor, $board);
	}
}

header('Content-type: image/png');
imagepng($image);


function drawNode($image, $loc, $color) {
	$x = $loc['col'] * BOX_SIDE;
	$y = $loc['row'] * BOX_SIDE;
	imagefilledrectangle($image, $x, $y, $x + BOX_SIDE, $y + BOX_SIDE, $color);
}
$q = 0;
function drawPath($image, $points, $color, $board) {
	global $q;
	$posArr = array();
	$oldPos = array();
	$j = 0;
	if(count($points) > 1) {
		$startPos = getStartPos($points[0],
 							    $points[1],
								$image);
#		print_r($startPos);
		for($i = 1; $i < count($points) - 1; $i++) {
			$endPos = getNext($points[$i],
							  $points[$i - 1],
							  $startPos,
							  $j - 1,
							  $posArr,
							  $image);
#			print_r($endPos);
			$posArr[$j++] = array($startPos, $endPos);
			$startPos = $endPos;
		}
		$endPos = getEndPos($points[count($points) - 2],
							$points[count($points) - 1], $startPos,
							$j - 1, $posArr, $image);
#		print_r($endPos);
		$posArr[$j++] = array($startPos, $endPos);

	}
if($q == 13 || $q == 1) {
	foreach($posArr as $pos) {
		imageline($image,
				  $pos[0]['x'],
				  $pos[0]['y'],
				  $pos[1]['x'],
				  $pos[1]['y'],
				  $color);
	}
}
/*if($q == 25) {
	header('Content-type: image/png');
	imagepng($image);
	die();
}*/
	$q++;
}

function getNext($to, $from, &$startPos, $oldIdx, &$posArr, $image) {
	if(inSameCol($from, $to)) {
		$pos['x'] = $startPos['x'];
		$pos['y'] = floor($to['row'] * BOX_SIDE + (BOX_SIDE * 0.5));
		if(isset($posArr[$oldIdx])) {
			if(goingUp($from, $to)) {
				while(!isClear($pos['x'], $pos['y'], $image)) {
					$startPos['x'] += HEAD_SIZE * 2;
					$pos['x'] += HEAD_SIZE * 2;
				}
			} else {
				while(!isClear($pos['x'], $pos['y'], $image)) {
					$startPos['x'] -= HEAD_SIZE * 2;
					$pos['x'] -= HEAD_SIZE * 2;
				}
			}
			$posArr[$oldIdx][1] = $startPos;
		}
	} else {
		$pos['x'] = floor($to['col'] * BOX_SIDE + (BOX_SIDE * 0.5));
		$pos['y'] = $startPos['y'];
		if(isset($posArr[$oldIdx])) {
			if(goingRight($from, $to)) {
				while(!isLineClear($startPos, $pos, $to, $from, $image)) {
					$startPos['y'] += HEAD_SIZE * 2;
					$pos['y'] += HEAD_SIZE * 2;
				}
			} else {
				while(!isLineClear($startPos, $pos, $to, $from, $image)) {
					$startPos['y'] += HEAD_SIZE * 2;
					$pos['y'] += HEAD_SIZE * 2;
				}
			}
			$posArr[$oldIdx][1] = $startPos;
		}
	}
	return $pos;
}

function getStartPos($from, $to, $image) {
	$pos['x'] = 0;
	$pos['y'] = 0;
	$tmpEnd = $pos;
	if(inSameCol($from, $to)) {
		if(goingUp($from, $to)) {
			$pos['x'] = floor($from['col'] * BOX_SIDE + (BOX_SIDE * 0.5));
			$pos['y'] = $from['row'] * BOX_SIDE;
			$tmpEnd['x'] = $pos['x'];
			$tmpEnd['y'] = floor($to['row'] * BOX_SIDE + BOX_SIDE * 0.5);
		} else {
			$pos['x'] = floor($from['col'] * BOX_SIDE + (BOX_SIDE * 0.5));
			$pos['y'] = $from['row'] * BOX_SIDE + BOX_SIDE;
			$tmpEnd['x'] = $pos['x'];
			$tmpEnd['y'] = floor($to['row'] * BOX_SIDE + BOX_SIDE * 0.5);
		}
		$midPos['x'] = ($pos['x'] + $tmpEnd['x']) / 2;
		$midPos['y'] = ($pos['y'] + $tmpEnd['y']) / 2;
#		print_r($midPos);
		while(!isClear($midPos['x'], $midPos['y'], $image)) {
			$pos['x'] += HEAD_SIZE * 2;
			$midPos['x'] += HEAD_SIZE * 2;
		}
	} else {
		if(goingRight($from, $to)) {
			$pos['x'] = $from['col'] * BOX_SIDE + BOX_SIDE;
			$pos['y'] = floor($from['row'] * BOX_SIDE + (BOX_SIDE * 0.5));
			$tmpEnd['x'] = floor($to['col'] * BOX_SIDE  + BOX_SIDE * 0.5);
			$tmpEnd['y'] = $pos['y'];
		} else {
			$pos['x'] = $from['col'] * BOX_SIDE;
			$pos['y'] = floor($from['row'] * BOX_SIDE + (BOX_SIDE * 0.5));
			$tmpEnd['x'] = floor($to['col'] * BOX_SIDE + BOX_SIDE * 0.5);
			$tmpEnd['y'] = $pos['y'];
		}
		$midPos['x'] = floor(($pos['x'] + $tmpEnd['x']) / 2);
		$midPos['y'] = floor(($pos['y'] + $tmpEnd['y']) / 2);
#		echo "midPos: ";
#		print_r($midPos);
		while(!isClear($midPos['x'], $midPos['y'], $image)) {
			$pos['y'] += HEAD_SIZE * 2;
			$midPos['y'] += HEAD_SIZE * 2;
		}
	}
	return $pos;
}

function getEndPos($from, $to, &$startPos, $oldIdx, &$posArr, $image) {
	$pos['x'] = 0;
	$pos['y'] = 0;
	if(inSameCol($from, $to)) {
		if(goingUp($from, $to)) {
			$pos['x'] = $startPos['x'];
			$pos['y'] = $to['row'] * BOX_SIDE + BOX_SIDE;
			if(isset($posArr[$oldIdx])) {
				$midPos['x'] = floor(($startPos['x'] + $pos['x']) / 2);
				$midPos['y'] = floor(($startPos['y'] + $pos['y']) / 2);
				while(!isClear($midPos['x'], $midPos['y'], $image)) {
					$startPos['x'] += HEAD_SIZE * 2;
					$midPos['x'] += HEAD_SIZE * 2;
					$pos['x'] += HEAD_SIZE * 2;
				}
				$posArr[$oldIdx][1] = $startPos;
			}
		} else {
			$pos['x'] = $startPos['x'];
			$pos['y'] = $to['row'] * BOX_SIDE;
			if(isset($posArr[$oldIdx])) {
				$midPos['x'] = floor(($startPos['x'] + $pos['x']) / 2);
				$midPos['y'] = floor(($startPos['y'] + $pos['y']) / 2);
				while(!isClear($midPos['x'], $midPos['y'], $image)) {
					$startPos['x'] -= HEAD_SIZE * 2;
					$midPos['x'] -= HEAD_SIZE * 2;
					$pos['x'] -= HEAD_SIZE * 2;
				}
				$posArr[$oldIdx][1] = $startPos;
			}
		}
	} else {
		if(goingRight($from, $to)) {
			$pos['x'] = $to['col'] * BOX_SIDE;
			$pos['y'] = $startPos['y'];
			if(isset($posArr[$oldIdx])) {
				$midPos['x'] = floor(($startPos['x'] + $pos['x']) / 2);
				$midPos['y'] = floor(($startPos['y'] + $pos['y']) / 2);
				while(!isClear($midPos['x'], $midPos['y'], $image)) {
					$startPos['y'] += HEAD_SIZE * 2;
					$midPos['y'] += HEAD_SIZE * 2;
					$pos['y'] += HEAD_SIZE * 2;
				}
				$posArr[$oldIdx][1] = $startPos;
			}
		} else {
			$pos['x'] = $to['col'] * BOX_SIDE + BOX_SIDE;
			$pos['y'] = $startPos['y'];
			if(isset($posArr[$oldIdx])) {
				$midPos['x'] = floor(($startPos['x'] + $pos['x']) / 2);
				$midPos['y'] = floor(($startPos['y'] + $pos['y']) / 2);
				while(!isClear($midPos['x'], $midPos['y'], $image)) {
					$startPos['y'] -= HEAD_SIZE * 2;
					$midPos['y'] -= HEAD_SIZE * 2;
					$pos['y'] -= HEAD_SIZE * 2;
				}
				$posArr[$oldIdx][1] = $startPos;
			}
		}
	}
	return $pos;
}

function isClear($x, $y, $image) {
	$colorArr = imagecolorsforindex($image, imagecolorat($image, $x, $y));
	if($colorArr['red'] != 255 or $colorArr['green'] != 255 or
		$colorArr['blue'] != 255) {
		return false;
	}
    return true;
}

function isLineClear($startPos, $endPos, $to, $from, $image) {
	$testArr = array();
	if(onSameRow($to, $from)) {
		$midPos['x'] = floor(($startPos['x'] + $endPos['x']) / 2);
		$midPos['y'] = $startPos['y'];
		$testArr[] = $midPos;
		$quartPos['x'] = floor(($startPos['x'] + $endPos['x']) / 4);
		$quartPos['y'] = $startPos['y'];
		$testArr[] = $quartPos;
		$q3Pos['x'] = floor(($startPos['x'] + $endPos['x']) * 0.75);
		$q3Pos['y'] = $startPos['y'];
		$testArr[] = $q3Pos;
		$testArr[] = $startPos;
		$testArr[] = $endPos;
		foreach($testArr as $arr) {
			$colorArr = imagecolorsforindex($image, imagecolorat($image,
																 $arr['x'],
																 $arr['y']));
			if($colorArr['red'] != 255 or $colorArr['green'] != 255 or
				$colorArr['blue'] != 255) {
				
				return false;
			}
		}
	} else {
		$midPos['x'] = $startPos['x'];
		$midPos['y'] = floor(($startPos['y'] + $endPos['y']) / 2);
		$testArr[] = $midPos;
		$quartPos['x'] = $startPos['x'];
		$quartPos['y'] = floor(($startPos['y'] + $endPos['y']) / 4);
		$testArr[] = $quartPos;
		$q3Pos['x'] = $startPos['x'];
		$q3Pos['y'] = floor(($startPos['y'] + $endPos['y']) * 0.75);
		$testArr[] = $q3Pos;
		$testArr[] = $startPos;
		$testArr[] = $endPos;
		foreach($testArr as $arr) {
			$colorArr = imagecolorsforindex($image, imagecolorat($image,
																 $arr['x'],
																 $arr['y']));
			if($colorArr['red'] != 255 or $colorArr['green'] != 255 or
				$colorArr['blue'] != 255) {
				
				return false;
			}
		}
	}
	return true;
}

function findPath($from, $to, $board) {
	$x1 = $from['col'];
	$y1 = $from['row'];
	$x2 = $to['col'];
	$y2 = $to['row'];
	$points = array($from);
	if(areAdjacent($board, $from, $to)) {
		//Do Nothing
	} elseif(onSameRow($from, $to)) {
		do {
			$randNum = rand(-1, 1);
		} while($randNum == 0);
		$stop = array('col' => $from['col'],
				      'row' => $from['row'] + $randNum);
		$points[] = $stop;
		$stop = array('row' => $stop['row'],
					  'col' => $to['col']);
		$points[] = $stop;
	} elseif(inSameCol($from, $to)) {
		do {
			$randNum = rand(-1, 1);
		} while($randNum == 0);
		$stop = array('col' => $from['col'] + $randNum,
					  'row' => $from['row']);
		$points[] = $stop;
		$stop = array('col' => $stop['col'],
					  'row' => $to['row']);
		$points[] = $stop;
	} else {
		if(goingUp($from, $to)) {
			$stop = array('row' => $from['row'] - 1,
						  'col' => $from['col']);
			$points[] = $stop;
			if($stop['row'] - 1 == $to['row']) {
				$stop = array('row' => $stop['row'],
							  'col' => $to['col']);
				$points[] = $stop;
			} else {
				$stop = array('row' => $stop['row'],
							  'col' => $to['col'] - 1);
				$points[] = $stop;
				$stop = array('row' => $to['row'] + 1,
							  'col' => $stop['col']);
				$points[] = $stop;
				$stop = array('row' => $stop['row'],
							  'col' => $to['col']);
				$points[] = $stop;
			}
		} else {
			$stop = array('row' => $from['row'] + 1,
						  'col' => $from['col']);
			$points[] = $stop;
			if($stop['row'] + 1 == $to['row']) {
				$stop = array('row' => $stop['row'],
							  'col' => $to['col']);
				$points[] = $stop;
			} else {
				$stop = array('row' => $stop['row'],
							  'col' => $to['col'] - 1);
				$points[] = $stop;
				$stop = array('row' => $to['row'] - 1,
							  'col' => $stop['col']);
				$points[] = $stop;
				$stop = array('row' => $stop['row'],
							  'col' => $to['col']);
				$points[] = $stop;
			}
		}
	}
	$points[] = $to;
	return $points;
}

function goingUp($from, $to) {
	return($from['row'] > $to['row']);
}

function goingRight($from, $to) {
	return($from['col'] < $to['col']);
}

function areAdjacent($board, $from, $to) {
	if($from['col'] == $to['col']) {
		for($i = $from['row'] + 1; $i < $to['row']; $i++) {
			if($board[$i][$from['col']] != 0) {
				return false;
			}
		}
		return true;
	} elseif($from['row'] == $to['row']) {
		for($i = $from['col'] + 1; $i < $to['col']; $i++) {
			if($board[$from['row']][$i] != 0) {
				return false;
			}
		}
		return true;
	}
	return false;
}

function onSameRow($from, $to) {
	return ($from['row'] == $to['row']);
}

function inSameCol($from, $to) {
	return ($from['col'] == $to['col']);
}

?>
