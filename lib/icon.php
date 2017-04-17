<?php
    // ouput is a png image example in php manual
    // arguments to the php file are body color and img file name with path
    // get values are bgcolor and imgPath
    
    header("Content-type: image/png");
    // get hex color from url and convert to RGB values
    $bgHexColor = $_GET['bgcolor'];
    $bgHexColor = str_replace("#","", $bgHexColor);
    $intRed = hexdec(substr($bgHexColor, 0, 2));
    $intGreen = hexdec(substr($bgHexColor, 2, 2));
    $intBlue = hexdec(substr($bgHexColor, 4, 2));
       
    $im = imagecreatefrompng($_GET['imgPath']);
    //gets pallette index for transparent pixels
    $transIndex = imagecolorexactalpha($im, 255, 255, 255, 0);
    //should set index of transparent pixels too requested bgcolor - untested
    imagecolorset($im, $transIndex, $intRed, $intGreen, $intBlue);
    imagepng($im);
    imagedestroy($im);
       
    //for conversion from hex bgcolor to RGB values
    
?>