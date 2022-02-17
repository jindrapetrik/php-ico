<?php

$action = "html";
if (array_key_exists("action", $_GET)) {
    $action = $_GET["action"];
}

include './ico.php';
use Com\Jpexs\Image\Icon;

$testFile = "./test.ico";

if ($action === "html") {
        $iconLib = new Icon();
        $icons = $iconLib->getIconsInfo($testFile);
        
        $bitNames = [
          1 => "1bit - black and white",
          4 => "4bit - 16 colors",
          8 => "8bit - 256 colors",
          24 => "24bit - True colors",
          32 => "32bit - True colors + alpha channel"
        ];
        
        echo '<h1>Icon generation</h1>';
        echo '<a href="index.php?action=generate_icon">Download icon</a>';
        
        echo '<h1>List of icons of test.ico:</h1>';
        foreach($icons as $icon) {
            echo $icon["width"] . " x " . $icon["height"];
            echo ", " . $bitNames[$icon["bit_count"]] . "<br>";
            echo '<img src="index.php?action=icon_to_png&width=' . $icon["width"] . '&bit_count=' . $icon["bit_count"] . '" /> <br />';
        }    
}
if ($action === "icon_to_png")
{
    header("Content-type: image/png");
    $image = imagecreatefromico($testFile, $_GET["bit_count"] ?? Icon::COLORS_TRUE_COLOR, $_GET["width"] ?? 32);
    imagepng($image);
}
if ($action === "generate_icon")
{
    header("Content-type: image/x-icon");
    header('Content-Disposition: attachment; filename="generated.ico"');
    
    $sizes = [48, 32, 16];
    
    $images = [];
    foreach ($sizes as $size) {
        $image = imagecreatetruecolor($size, $size);
        $white = imagecolorallocate($image, 255, 255, 255);
        $red = imagecolorallocate($image, 255, 0, 0);
        imagefilledrectangle($image, 0, 0, $size, $size, $white);
        imagefilledellipse($image, $size/2, $size/2, $size, $size, $red);
        $images[] = $image;
    }
    
    imageico($images);    
}