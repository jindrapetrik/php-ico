# php-ico
Icon manipulation library for PHP.

## Usage
Full example is available in the file [index.php](index.php).

### List all images of an icon
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

### Display icon image
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

### Generate icon
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

### List of EXE icons
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

### Display EXE icon image
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

### Export EXE icon
```php
include_once '<lib_path>/includes/autoload.php';

use Com\Jpexs\Image\ExeIconReader;

$exeReader = ExeIconReader::createFromExeFile("samples/test.exe");
$iconId = "CAT";
echo $exeReader->saveIcon($iconId, "./out.ico");
```

## License
The library is licensed under GNU/LGPL v2.1, see [LICENSE](LICENSE)
for details.

## Author
Jindra Petřík aka JPEXS

## Changelog
Changes in versions are logged in the file [CHANGELOG.md](CHANGELOG.md)

