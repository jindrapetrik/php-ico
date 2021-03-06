# Icon library sample code

Here are some sample codes you can start with.

Full example is available in the file [index.php](index.php).

## List all images of an icon
```php
include_once '<lib_path>/includes/autoload.php';

use Com\Jpexs\Image\IconReader;

$iconReader = IconReader::createFromIcoFile("samples/test.ico");

foreach ($iconReader as $imageId => $iconImage) {
    echo "imageId " . $imageId . ": ";
    echo $iconImage->getWidth() . " x " . $iconImage->getHeight();
    echo ", color bit count: " . $iconImage->getColorsBitCount() . "<br>";     
}
```

## Display icon image
```php
include_once '<lib_path>/includes/autoload.php';

use Com\Jpexs\Image\IconReader;

header("Content-type: image/png");

$imageId = 0; //index of image in the icon
$iconReader = IconReader::createFromIcoFile("samples/test.ico");
$iconImage = $iconReader->getIconImage($imageId);
$image = $iconImage->getImage();
imagepng($image);
exit;
```

## Generate icon
```php
include_once '<lib_path>/includes/autoload.php';
use Com\Jpexs\Image\IconWriter;

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
```

## List of EXE icons
```php
include_once '<lib_path>/includes/autoload.php';
use Com\Jpexs\Image\ExeIconReader;

$exeReader = ExeIconReader::createFromExeFile("samples/test.exe");
echo $exeReader->getIconCount() . " icon groups<br>";
foreach ($exeReader as $iconId => $icon) {
    echo "<h3>icon group $iconId</h3>";
    foreach ($icon as $imageId => $iconImage) {
        echo $iconImage->getWidth() . " x " . $iconImage->getHeight();
        echo ", color bit count: " . $iconImage->getColorsBitCount() . "<br>";
    }
}
```

## Display EXE icon image
```php
include_once '<lib_path>/includes/autoload.php';

use Com\Jpexs\Image\ExeIconReader;

header("Content-type: image/png");
$exeReader = ExeIconReader::createFromExeFile("samples/test.exe");
$iconId = "CAT";
$imageId = 0;
$iconImage = $exeReader->getIcon($iconId)->getIconImage($imageId);
$image = $iconImage->getImage();
imagepng($image);
exit;
```

## Export EXE icon
```php
include_once '<lib_path>/includes/autoload.php';

use Com\Jpexs\Image\ExeIconReader;

$exeReader = ExeIconReader::createFromExeFile("samples/test.exe");
$iconId = "CAT";
echo $exeReader->saveIcon($iconId, "./out.ico");
```

## Get cursor info
```php
include_once '<lib_path>/includes/autoload.php';

use Com\Jpexs\Image\IconReader;

$iconReader = IconReader::createFromCurFile("samples/test.cur");
$cursorImage = $iconReader->getCursorImage();
echo $cursorImage->getWidth() . " x " . $cursorImage->getHeight();
echo ", color bit count: " . $cursorImage->getColorsBitCount() . "<br>";
echo "hotspot x: " . $cursorImage->getHotSpotX().", hotspot y:" . $cursorImage->getHotSpotY() . "<br>";
```

## Display cursor image
```php
include_once '<lib_path>/includes/autoload.php';

use Com\Jpexs\Image\IconReader;

header("Content-type: image/png");
$iconReader = IconReader::createFromCurFile("samples/test.cur");
$cursorImage = $iconReader->getCursorImage();
$image = $cursorImage->getImage();
imagepng($image);
```

## Generate cursor
```php
include_once '<lib_path>/includes/autoload.php';

use Com\Jpexs\Image\IconWriter;

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
$writer->createCursorToPrint($image, 5, 5); //hotspot 5, 5
```

## Get ANI file info
```php
include_once '<lib_path>/includes/autoload.php';

use Com\Jpexs\Image\AniReader;

$aniReader = AniReader::createFromAniFile("sample.ani");
echo $aniReader->getWidth() . " x " . $aniReader->getHeight();
echo ", color bit count: " . $aniReader->getColorsBitCount() . "<br>";
echo "name: " . $aniReader->getName() . "<br>";   
echo "artist: " . $aniReader->getArtist() . "<br>";
```

## Display ANI frames
```php
include_once '<lib_path>/includes/autoload.php';

use Com\Jpexs\Image\AniReader;

header("Content-type: image/png");
$aniReader = AniReader::createFromAniFile("sample.ani");
$imageId = 0; //which image
//you can foreach $aniReader to walk all images
// or get max index with $imageCount = $aniReader->getIconImageCount()
$iconImage = $aniReader->getIconImage($imageId);
$image = $iconImage->getImage();        
imagepng($image);
```

## Generate ANI file
```php
include_once '<lib_path>/includes/autoload.php';

use Com\Jpexs\Image\AniWriter;

$aniWriter = new AniWriter();
$aniWriter->setArtist("Jindra Petrik");
$aniWriter->setName("My Generated animated cursor");
$aniWriter->setDefaultFrameRate(5);

$steps = 10;

$startR = 0; $startG = 0; $startB = 255;
$endR = 0; $endG = 255; $endB = 0;

for ($i = 0; $i < $steps; $i++) {
    $image = imagecreate(32, 32);
    $background = imagecolorallocate($image, 255, 0, 255);
    imagefill($image, 0, 0, $background);

    $fillColor = imagecolorallocate($image, 
            round($startR + ($endR - $startR) * $i / $steps),
            round($startG + ($endG - $startG) * $i / $steps),
            round($startB + ($endB - $startB) * $i / $steps),
            );

    $black = imagecolorallocate($image, 0, 0, 0);
    $polygon = [
        5, 5,
        20, 5,
        5, 20,
    ];
    imagefilledpolygon($image, $polygon, count($polygon)/2, $fillColor);
    imagepolygon($image, $polygon, count($polygon)/2, $black);
    imagecolortransparent($image, $background);  
    $aniWriter->addImage($image, 5, 5);        
}      
$aniWriter->createToFile("out.ani");
```
