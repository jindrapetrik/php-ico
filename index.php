<?php

include_once './includes/autoload.php';

use Com\Jpexs\Image\IconReader;
use Com\Jpexs\Image\IconWriter;
use Com\Jpexs\Image\ExeIconReader;

$testIcoFile = "./samples/test.ico";
$testCurFile = "./samples/test.cur";
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

    echo '<h2>4) Cursor display</h2>';
    
    $iconReader = IconReader::createFromCurFile($testCurFile);
    $cursorImage = $iconReader->getCursorImage();
    echo $cursorImage->getWidth() . " x " . $cursorImage->getHeight();
    echo ", " . $bitNames[$cursorImage->getColorsBitCount()] . "<br>";
    echo "hotspot x: " . $cursorImage->getHotSpotX().", hotspot y:" . $cursorImage->getHotSpotY() . "<br>";
    echo '<img src="index.php?action=cursor_to_png" /> <br />';
    
    echo '<h2>5) Generate cursor</h2>';
    echo '<div style="width:200px; height:200px; margin:auto; background-color: #f00; cursor: url(\'index.php?action=generate_cursor\'), auto;">sample div - hover to show cursor</div>';
    
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

    $sizes = [256, 128, 64, 48, 32, 16];

    $images = [];
    foreach ($sizes as $size) {        
        //alpha image
        $image = imagecreatetruecolor($size, $size);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        $red = imagecolorallocatealpha($image, 255, 0, 0, 64);
        imagefill($image, 0, 0, $transparent);
        imagefilledellipse($image, $size / 2, $size / 2, $size, $size, $red);
        $images[] = $image;
        
        //standard image with trasparent background
        $image = imagecreatetruecolor($size, $size);
        $transparent = imagecolorallocate($image, 255, 255, 255);
        $red = imagecolorallocate($image, 255, 0, 0);
        imagefill($image, 0, 0, $transparent);
        imagefilledellipse($image, $size / 2, $size / 2, $size, $size, $red);
        imagecolortransparent($image, $transparent);                
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

if ($action === "cursor_to_png") {    
    header("Content-type: image/png");
    $iconReader = IconReader::createFromCurFile($testCurFile);
    $cursorImage = $iconReader->getCursorImage();
    $image = $cursorImage->getImage();
    imagepng($image);
    exit;
}

if ($action === "generate_cursor") {
    header("Content-type: image/vnd.microsoft.icon");
    $writer = new IconWriter();
    $image = imagecreate(32, 32);
    $background = imagecolorallocate($image, 255, 0, 255);
    imagefill($image, 0, 0, $background);
    $blue = imagecolorallocate($image, 0, 0, 255);
    $yellow = imagecolorallocate($image, 255, 255, 0);
    $polygon = [
        5, 5,
        20, 5,
        5, 20,
    ];
    imagefilledpolygon($image, $polygon, count($polygon)/2, $blue);
    imagepolygon($image, $polygon, count($polygon)/2, $yellow);
    imagecolortransparent($image, $background);    
    $writer->createCursorToPrint($image, 5, 5);
    exit;
}