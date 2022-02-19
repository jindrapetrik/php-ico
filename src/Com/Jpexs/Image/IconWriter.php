<?php

namespace Com\Jpexs\Image;

use Com\Jpexs\Stream\StreamWriter;

class IconWriter {
    
    public const TYPE_ICON = 1;
    
    public const TYPE_CURSOR = 2;
    
    private const BI_RGB = 0;

    /**
     * Creates ico file from image resource(s)
     * @param resource|resource[]|GdImage|GdImage[] $images Target Image resource (Can be array of image resources/GdImages)
     * @return string|null String data of icon, null on failure
     */
    public function createAsString($images): ?string {
        return $this->createAsStringInternal($images);
    }
    
    private function createAsStringInternal($images, $hotSpotX = null, $hotSpotY = null): ?string {

        $writer = new StreamWriter();
        
        if (!is_array($images)) {
            $images = [$images];
        }
        $imageCount = count($images);

        $ret = "";

        //HEADER
        $ret .= $writer->inttoword(0); //idReserved
        $ret .= $writer->inttoword($hotSpotX === null ? self::TYPE_ICON : self::TYPE_CURSOR); //idType
        $ret .= $writer->inttoword($imageCount); //idCount       

        $imageData = "";
        $imageDataSizes = [];
        $bitCounts = [];
        $paletteColorCounts = [];
        $widths = [];
        $heights = [];
        for ($i = 0; $i < $imageCount; $i++) {
            $img = $images[$i];
            $width = imagesx($img);
            $height = imagesy($img);
            
            $widths[] = $width;
            $heights[] = $height;
            
            if ($width > 256) {
                trigger_error("Width of the icon must be 256 or lower");
                return null;
            }
            if ($height > 256) {
                trigger_error("Height of the icon must be 256 or lower");
                return null;
            }
         
            $currentImageData = "";
            if ($width === 256 && $height === 256) {
                //store as PNG
                $stream = fopen('php://memory','r+');
                imagepng($img,$stream);
                rewind($stream);
                $currentImageData = stream_get_contents($stream);
                $imageDataSizes[] = strlen($currentImageData);
                $imageData .= $currentImageData;
                continue;
            }            
            
            $usedColorCount = imagecolorstotal($img);

            $transparent = imagecolortransparent($img);
            
            if ($usedColorCount === 0) {
                $paletteColorCount = 0;
                $bitCount = 24;
            } elseif (($usedColorCount > 0) && ($usedColorCount <= 2)) {
                $paletteColorCount = 2;
                $bitCount = 1;
            } elseif (($usedColorCount > 2) && ($usedColorCount <= 16)) {
                $paletteColorCount = 16;
                $bitCount = 4;
            } elseif (($usedColorCount > 16) && ($usedColorCount <= 256)) {
                $paletteColorCount = 256;
                $bitCount = 8;
            }                        
            
            if ($bitCount === 24) {                
                //search for alpha channel
                for ($x = 0; $x < $width; $x++) {
                    for ($y = 0; $y < $height; $y++) {
                        $rgba = imagecolorat($img, $x, $y);
                        if(($rgba & 0x7F000000) >> 24) {
                            $bitCount = 32;
                            break 2;
                        }
                    }
                }                
            }
            
            $paletteColorCounts[] = $paletteColorCount;
            $bitCounts[] = $bitCount;

            //BITMAPINFOHEADER
            $currentImageData .= $writer->inttodword(40); //biSize
            $currentImageData .= $writer->inttodword($width); //biWidth
            $currentImageData .= $writer->inttodword(2 * $height); //biHeight
            $currentImageData .= $writer->inttoword(1); //biPlanes
            $currentImageData .= $writer->inttoword($bitCount);   //biBitCount
            $currentImageData .= $writer->inttodword(self::BI_RGB); //biCompression

            $remainderMask = ($width / 8) % 4;

            $remainder = ($width / (8 / $bitCount)) % 4;
            $size = ($width / (8 / $bitCount) + $remainder) * $height + (($width / 8 + $remainderMask) * $height);

            $currentImageData .= $writer->inttodword($size); //biSizeImage

            $currentImageData .= $writer->inttodword(0); //biXPelsPerMeter
            $currentImageData .= $writer->inttodword(0); //biYPelsPerMeter
            $currentImageData .= $writer->inttodword($paletteColorCount); //biClrUsed
            $currentImageData .= $writer->inttodword(0); //biClrImportant

            if ($bitCount < 24) {                
                for ($j = 0; $j < $usedColorCount; $j++) {
                    $color = imagecolorsforindex($img, $j);
                    $currentImageData .= $writer->inttobyte($color["blue"]);
                    $currentImageData .= $writer->inttobyte($color["green"]);
                    $currentImageData .= $writer->inttobyte($color["red"]);
                    $currentImageData .= $writer->inttobyte(0); //RESERVED
                }

                for ($j = $usedColorCount; $j < $paletteColorCount; $j++) {
                    $currentImageData .= $writer->inttobyte(0);
                    $currentImageData .= $writer->inttobyte(0);
                    $currentImageData .= $writer->inttobyte(0);
                    $currentImageData .= $writer->inttobyte(0); //RESERVED
                }
            }

            if ($bitCount <= 8) {
                for ($y = $height - 1; $y >= 0; $y--) {
                    $bWrite = "";
                    for ($x = 0; $x < $width; $x++) {
                        $color = imagecolorat($img, $x, $y);
                        if ($color == $transparent) {
                            $color = imagecolorexact($img, 0, 0, 0);
                        }
                        if ($color == -1) {
                            $color = 0;
                        }
                        if ($color > pow(2, $bitCount) - 1) {
                            $color = 0;
                        }

                        $bWrite .= $this->decbinx($color, $bitCount);
                        if (strlen($bWrite) == 8) {
                            $currentImageData .= $writer->inttobyte(bindec($bWrite));
                            $bWrite = "";
                        }
                    }

                    if ((strlen($bWrite) < 8)&&(strlen($bWrite) != 0)) {
                        $sl = strlen($bWrite);
                        for ($t = 0; $t < 8 - $sl; $t++) {
                            $sl .= "0";
                        }
                        $currentImageData .= $writer->inttobyte(bindec($bWrite));
                    }
                    for ($z = 0; $z < $remainder; $z++) {
                        $currentImageData .= $writer->inttobyte(0);
                    }
                }
            }

            if ($bitCount >= 24) {
                for ($y = $height - 1; $y >= 0; $y--) {
                    for ($x = 0; $x < $width; $x++) {
                        $color = imagecolorsforindex($img, imagecolorat($img, $x, $y));
                        $currentImageData .= $writer->inttobyte($color["blue"]);
                        $currentImageData .= $writer->inttobyte($color["green"]);
                        $currentImageData .= $writer->inttobyte($color["red"]);
                        if ($bitCount == 32) {
                            $opacity = 255 - round($color["alpha"] * 255 / 127);
                            $currentImageData .= $writer->inttobyte($opacity);
                        }
                    }
                    for ($z = 0; $z < $remainder; $z++) {
                        $currentImageData .= $writer->inttobyte(0);
                    }
                }
            }

            //MASK
            for ($y = $height - 1; $y >= 0; $y--) {
                $byteCount = 0;
                $bOut = "";
                for ($x = 0; $x < $width; $x++) {
                    if (($transparent != -1) && (imagecolorat($img, $x, $y) == $transparent)) {
                        $bOut .= "1";
                    } else {
                        $bOut .= "0";
                    }
                }
                for ($j = 0; $j < strlen($bOut); $j += 8) {
                    $byte = bindec(substr($bOut, $j, 8));
                    $byteCount++;
                    $currentImageData .= $writer->inttobyte($byte);
                }
                $remainder = $byteCount % 4;
                for ($z = 0; $z < $remainder; $z++) {
                    $currentImageData .= $writer->inttobyte(0xff);
                }
            }
            $imageDataSizes[] = strlen($currentImageData);
            $imageData .= $currentImageData;
        }
        
        $fullSize = 0;
        for ($i = 0; $i < $imageCount; $i++) {            
            $width = $widths[$i];
            $height = $heights[$i];

            $paletteColorCount = $paletteColorCounts[$i];
            $bitCount = $bitCounts[$i];
            
            //ICONDIRENTRY
            $ret .= $writer->inttobyte($width === 256 ? 0 : $width); //bWidth
            $ret .= $writer->inttobyte($height === 256 ? 0 : $height); //bHeight
            $ret .= $writer->inttobyte($paletteColorCount); //bColorCount
            $ret .= $writer->inttobyte(0); //bReserved
            
            if ($hotSpotX !== null) {
                $ret .= $writer->inttoword($hotSpotX); //wHotSpotX
                $ret .= $writer->inttoword($hotSpotY); //wHotSpotY
            } else {           
                $ret .= $writer->inttoword(1); //wPlanes
                $ret .= $writer->inttoword($bitCount); //wBitCount
            }

            $remainder = (4 - ($width / (8 / $bitCount)) % 4) % 4;
            $remainderMask = (4 - ($width / 8) % 4) % 4;

            $size = $imageDataSizes[$i];
            $ret .= $writer->inttodword($size); //dwBytesInRes
            $offset = 6 + 16 * $imageCount + $fullSize;
            $ret .= $writer->inttodword($offset); //dwImageOffset
            $fullSize += $size;
        }
        $ret .= $imageData;

        return $ret;
   }
    
    /**
     * Creates icon to target file
     * @param resource|resource[]|GdImage|GdImage[] $images Target Image resource (Can be array of image resources/GdImages)     
     * @param string $filename Output file
     * @return bool True on success, False on failure
     */
    public function createToFile($images, string $filename): bool
    {
        $data = $this->createAsString($images);
        if ($data === null) {
            return false;
        }
        
        $f = @fopen($filename, "w");
        if ($f === false) {
            trigger_error("Cannot write icon to file \"$filename\"");
            return false;
        }
        fwrite($f, $data);
        fclose($f);
        
        return true;
    }
    
    private function decbinx($d, $n) {
        $bin = decbin($d);
        $sbin = strlen($bin);
        for ($j = 0; $j < $n - $sbin; $j++) {
            $bin = "0$bin";
        }
        return $bin;
    }
    
    /**
     * Creates icon and prints it to standard output. 
     * Note: use proper header("Content-type: image/x-icon")
     * @param resource|resource[]|GdImage|GdImage[] $images Target Image resource (Can be array of image resources/GdImages)     
     * @return bool True on success, False on failure
     */
    public function createToPrint($images): bool
    {
        $data = $this->createAsString($images);
        if ($data === null) {
            return false;
        }
        echo $data;
        return true;
    }
    
    /**
     * 
     * @param resource|GdImage $image
     * @return string|null String data of cursor, null on failure
     */
    public function createCursorAsString($image, int $hotSpotX, int $hotSpotY): ?string {
        return $this->createAsStringInternal($image, $hotSpotX, $hotSpotY);
    }
    
    /**
     * 
     * @param resource|GdImage $image
     * @param int $hotSpotX Cursor hot spot X
     * @param int $hotSpotY Cursor hot spot Y
     * @return bool True on success, False on failure
     */
    public function createCursorToPrint($image, int $hotSpotX, int $hotSpotY): bool {
        $data = $this->createCursorAsString($image, $hotSpotX, $hotSpotY);
        if ($data === null) {
            return false;
        }
        echo $data;
        return true;
    }
    
    /**
     * Creates cursor to target file
     * @param resource|GdImage $image Target Image resource     
     * @param string $filename Output file
     * @param int $hotSpotX Cursor hot spot X
     * @param int $hotSpotY Cursor hot spot Y
     * @return bool True on success, False on failure
     */
    public function createCursorToFile($image, string $filename, int $hotSpotX, int $hotSpotY): bool
    {
        $data = $this->createCursorAsString($image, $hotSpotX, $hotSpotY);
        if ($data === null) {
            return false;
        }
        
        $f = @fopen($filename, "w");
        if ($f === false) {
            trigger_error("Cannot write cursor to file \"$filename\"");
            return false;
        }        
        fwrite($f, $data);
        fclose($f);
        
        return true;
    }

}
