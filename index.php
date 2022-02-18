<?php

include_once './includes/autoload.php';

use Com\Jpexs\Image\IconReader;
use Com\Jpexs\Image\IconWriter;
use Com\Jpexs\Image\ExeIconReader;

$testIcoFile = "./samples/test.ico";
$testExeFile = "./samples/test.exe";

$action = "html";
if (array_key_exists("action", $_GET)) {
    $action = $_GET["action"];
}

if ($action === "html") {
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>JPEXS Icon library samples</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body bgcolor="#8f8">';

    $bitNames = [
        1 => "1bit - black and white",
        4 => "4bit - 16 colors",
        8 => "8bit - 256 colors",
        24 => "24bit - True colors",
        32 => "32bit - True colors + alpha channel"
    ];

    echo '<h1>JPEXS Icon library samples</h1>';
    echo '<h2>1) Icon generation</h2>';
    echo '<a href="index.php?action=generate_icon">Download icon</a>';

    echo '<h2>2) List of icons of ' . $testIcoFile . ':</h2>';

    $iconReader = IconReader::createFromIcoFile($testIcoFile);

    foreach ($iconReader as $imageId => $iconImage) {
        echo $iconImage->getWidth() . " x " . $iconImage->getHeight();
        echo ", " . $bitNames[$iconImage->getColorsBitCount()] . "<br>";
        echo '<img src="index.php?action=icon_to_png&imageId=' . $imageId . '" /> <br />';
    }

    echo '<h2>3) List of icons of ' . $testExeFile . '</h2>';

    $exeReader = ExeIconReader::createFromExeFile($testExeFile);

    echo $exeReader->getIconCount() . " icon groups<br>";
    foreach ($exeReader as $iconId => $icon) {
        echo "<h3>icon group $iconId</h3>";
        echo '<a href="index.php?action=exe_icon_export&iconId=' . $iconId . '">export ICO file</a><br>';
        foreach ($icon as $imageId => $iconImage) {
            echo $iconImage->getWidth() . " x " . $iconImage->getHeight();
            echo ", " . $bitNames[$iconImage->getColorsBitCount()] . "<br>";
            echo '<img src="index.php?action=exe_icon_to_png&iconId=' . $iconId . "&imageId=" . $imageId . '" /> <br />';
        }
    }

    echo '</body>
        </html>';
    exit;
}

if ($action === "icon_to_png") {
    header("Content-type: image/png");
    $iconReader = IconReader::createFromIcoFile($testIcoFile);
    $iconImage = $iconReader->getIconImage($_GET["imageId"]);
    $image = $iconImage->getImage();
    imagepng($image);
    exit;
}

if ($action === "generate_icon") {
    header("Content-type: image/x-icon");
    header('Content-Disposition: attachment; filename="generated.ico"');

    $sizes = [48, 32, 16];

    $images = [];
    foreach ($sizes as $size) {
        $image = imagecreatetruecolor($size, $size);
        $white = imagecolorallocate($image, 255, 255, 255);
        $red = imagecolorallocate($image, 255, 0, 0);
        imagefilledrectangle($image, 0, 0, $size, $size, $white);
        imagefilledellipse($image, $size / 2, $size / 2, $size, $size, $red);
        $images[] = $image;
    }

    $writer = new IconWriter();
    $writer->createToPrint($images);
    exit;
}

if ($action === "exe_icon_to_png") {
    header("Content-type: image/png");
    $exeReader = ExeIconReader::createFromExeFile($testExeFile);
    $iconImage = $exeReader->getIcon($_GET["iconId"])->getIconImage($_GET["imageId"]);
    $image = $iconImage->getImage();
    imagepng($image);
    exit;
}

if ($action === "exe_icon_export") {    
    
    $iconId = $_GET["iconId"];
    header("Content-type: image/x-icon");
    header('Content-Disposition: attachment; filename="' . $iconId . '.ico"');

    $exeReader = ExeIconReader::createFromExeFile($testExeFile);
    echo $exeReader->getIconData($iconId);
    exit;
}