<?php

require_once("../ClearQR.php");

$dataDir = dirname(__FILE__) . '/../data/';
$imagesDir = dirname(__FILE__) . '/../images/';

$clearQR = new drewjw81\ClearQR($dataDir, $imagesDir);

$clearQR->setColor('#000000');

$errorCorrection = "M";
$version = 1;

$dataString = "http://github.com/drewjw81/";


$clearQR->setColor('#0000ff');

$clearQR->setWidth(500);
$image = $clearQR->generateImage($dataString, $errorCorrection, $version);

ob_end_clean();
header("Content-type: image/png");
echo $image->getimageblob();
$image->destroy();