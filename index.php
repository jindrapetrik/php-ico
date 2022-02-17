<?php


require_once __DIR__.'/Com/Jpexs/Image/IconReader.php';
require_once __DIR__.'/Com/Jpexs/Image/ExeIconReader.php';
require_once __DIR__.'/Com/Jpexs/Image/IconWriter.php';
use Com\Jpexs\Image\IconReader;
use Com\Jpexs\Image\IconWriter;
use Com\Jpexs\Image\ExeIconReader;
use Com\Jpexs\Image\IconReaderImage;

$testIcoFile = "./test.ico";
$testExeFile = "./test.exe";


$action = "html";
if (array_key_exists("action", $_GET)) {
    $action = $_GET["action"];
}


if ($action === "html") {
    echo '<body bgcolor="#8f8">';
        
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
        
        $iconReader = IconReader::createFromIcoFile($testIcoFile);       
        
        foreach($iconReader as $imageId => $image) {
            /**
             * @var IconReaderImage $icon
             */
            echo $image->getWidth() . " x " . $image->getHeight();
            echo ", " . $bitNames[$image->getColorsBitCount()] . "<br>";
            echo '<img src="index.php?action=icon_to_png&imageId=' . $imageId. '" /> <br />';
        }    
        
        echo '<h1>List of icons of test.exe</h1>';
        
        $exeReader = ExeIconReader::createFromExeFile($testExeFile);
        
        echo $exeReader->getIconCount() . " icon groups<br>";
        foreach ($exeReader as $iconId => $icon) {
            echo "<h2>icon group $iconId</h2>";
            foreach($icon as $imageId => $image) {
                /**
                 * @var IconReaderImage $icon
                 */
                echo $image->getWidth() . " x " . $image->getHeight();
                echo ", " . $bitNames[$image->getColorsBitCount()] . "<br>";
                echo '<img src="index.php?action=exe_icon_to_png&iconId=' . $iconId . "&imageId=" . $imageId. '" /> <br />';
            }   
        }
        
        echo '</body>';
}
if ($action === "icon_to_png")
{
    header("Content-type: image/png");
    $iconReader = IconReader::createFromIcoFile($testIcoFile);
    /**
     * @var IconReaderImage $icon
     */
    $image = $iconReader->getImage($_GET["imageId"] ?? 0);
    $imageResource = $image->getImage();
    imagepng($imageResource);
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
    
    $writer = new IconWriter();
    $writer->createToPrint($images);
}

if ($action === "exe_icon_to_png")
{
    header("Content-type: image/png");
    $exeReader = ExeIconReader::createFromExeFile($testExeFile);
    /**
     * @var IconReaderImage $imageReader
     */
    $imageReader = $exeReader->getIcon($_GET["iconId"] ?? 0)->getImage($_GET["imageId"] ?? 0);
    $imageResource = $imageReader->getImage();
    imagepng($imageResource);
}