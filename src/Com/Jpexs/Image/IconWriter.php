<?php

namespace Com\Jpexs\Image;

use Com\Jpexs\Stream\StreamWriter;

class IconWriter {

    /**
     * Creates ico file from image resource(s)
     * @param resource|resource[]|GdImage|GdImage[] $images Target Image resource (Can be array of image resources/GdImages)
     * @return string|false String data of icon, False on failure
     */
    public function createAsString($images) {

        $writer = new StreamWriter();
        
        if (!is_array($images)) {
            $images = [$images];
        }
        $imageCount = count($images);

        $ret = "";

        $ret .= $writer->inttoword(0); //PASSWORD
        $ret .= $writer->inttoword(1); //SOURCE
        $ret .= $writer->inttoword($imageCount); //ICONCOUNT

        $fullSize = 0;
        for ($q = 0; $q < $imageCount; $q++) {
            $img = $images[$q];

            $width = imagesx($img);
            $height = imagesy($img);

            $colorCount = imagecolorstotal($img);

            $transparent = imagecolortransparent($img);
            $isTransparent = $transparent != -1;

            if ($isTransparent) {
                $colorCount--;
            }

            if ($colorCount == 0) {
                $colorCount = 0;
                $bitCount = 24;
            }
            if (($colorCount > 0)&&($colorCount <= 2)) {
                $colorCount = 2;
                $bitCount = 1;
            }
            if (($colorCount > 2)&&($colorCount <= 16)) {
                $colorCount = 16;
                $bitCount = 4;
            }
            if (($colorCount > 16)&&($colorCount <= 256)) {
                $colorCount = 0;
                $bitCount = 8;
            }

            //ICONINFO:
            $ret .= $writer->inttobyte($width);
            $ret .= $writer->inttobyte($height);
            $ret .= $writer->inttobyte($colorCount);
            $ret .= $writer->inttobyte(0); //RESERVED

            $planes = 0;
            if ($bitCount >= 8) {
                $planes = 1;
            }

            $ret .= $writer->inttoword($planes);
            if ($bitCount >= 8) {
                $WBitCount = $bitCount;
            }
            if ($bitCount == 4) {
                $WBitCount = 0;
            }
            if ($bitCount == 1) {
                $WBitCount = 0;
            }
            $ret .= $writer->inttoword($WBitCount); //BITS

            $remainder = (4 - ($width / (8 / $bitCount)) % 4) % 4;
            $remainderMask = (4 - ($width / 8) % 4) % 4;

            $size = 40 + ($width / (8 / $bitCount) + $remainder) * $height + (($width / 8 + $remainderMask) * $height);
            if ($bitCount < 24) {
                $size += pow(2, $bitCount) * 4;
            }
            $ret .= $writer->inttodword($size); //SIZE
            $offset = 6 + 16 * $imageCount + $fullSize;
            $ret .= $writer->inttodword($offset); //OFFSET
            $fullSize += $size;
        }

        for ($q = 0; $q < $imageCount; $q++) {
            $img = $images[$q];
            $width = imagesx($img);
            $height = imagesy($img);
            $colorCount = imagecolorstotal($img);

            $transparent = imagecolortransparent($img);
            $isTransparent = $transparent != -1;

            if ($isTransparent) {
                $colorCount--;
            }
            if ($colorCount == 0) {
                $colorCount = 0;
                $bitCount = 24;
            }
            if (($colorCount > 0) && ($colorCount <= 2)) {
                $colorCount = 2;
                $bitCount = 1;
            }
            if (($colorCount > 2) && ($colorCount <= 16)) {
                $colorCount = 16;
                $bitCount = 4;
            }
            if (($colorCount > 16) && ($colorCount <= 256)) {
                $colorCount = 0;
                $bitCount = 8;
            }

            //ICONS
            $ret .= $writer->inttodword(40); //HEADSIZE
            $ret .= $writer->inttodword($width); //
            $ret .= $writer->inttodword(2 * $height); //
            $ret .= $writer->inttoword(1); //PLANES
            $ret .= $writer->inttoword($bitCount);   //
            $ret .= $writer->inttodword(0); //Compress method

            $remainderMask = ($width / 8) % 4;

            $remainder = ($width / (8 / $bitCount)) % 4;
            $size = ($width / (8 / $bitCount) + $remainder) * $height + (($width / 8 + $remainderMask) * $height);

            $ret .= $writer->inttodword($size); //SIZE

            $ret .= $writer->inttodword(0); //HPIXEL_M
            $ret .= $writer->inttodword(0); //V_PIXEL_M
            $ret .= $writer->inttodword($colorCount); //UCOLORS
            $ret .= $writer->inttodword(0); //DCOLORS

            $cc = $colorCount;
            if ($cc == 0) {
                $cc = 256;
            }

            if ($bitCount < 24) {
                $colorTotal = imagecolorstotal($img);
                if ($isTransparent) {
                    $colorTotal--;
                }

                for ($p = 0; $p < $colorTotal; $p++) {
                    $color = imagecolorsforindex($img, $p);
                    $ret .= $writer->inttobyte($color["blue"]);
                    $ret .= $writer->inttobyte($color["green"]);
                    $ret .= $writer->inttobyte($color["red"]);
                    $ret .= $writer->inttobyte(0); //RESERVED
                }

                for ($p = $colorTotal; $p < $cc; $p++) {
                    $ret .= $writer->inttobyte(0);
                    $ret .= $writer->inttobyte(0);
                    $ret .= $writer->inttobyte(0);
                    $ret .= $writer->inttobyte(0); //RESERVED
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
                            $ret .= $writer->inttobyte(bindec($bWrite));
                            $bWrite = "";
                        }
                    }

                    if ((strlen($bWrite) < 8)&&(strlen($bWrite) != 0)) {
                        $sl = strlen($bWrite);
                        for ($t = 0; $t < 8 - $sl; $t++) {
                            $sl .= "0";
                        }
                        $ret .= $writer->inttobyte(bindec($bWrite));
                    }
                    for ($z = 0; $z < $remainder; $z++) {
                        $ret .= $writer->inttobyte(0);
                    }
                }
            }

            if ($bitCount >= 24) {
                for ($y = $height - 1; $y >= 0; $y--) {
                    for ($x = 0; $x < $width; $x++) {
                        $color = imagecolorsforindex($img, imagecolorat($img, $x, $y));
                        $ret .= $writer->inttobyte($color["blue"]);
                        $ret .= $writer->inttobyte($color["green"]);
                        $ret .= $writer->inttobyte($color["red"]);
                        if ($bitCount == 32) {
                            $ret .= $writer->inttobyte(0); //Alpha for self:XP_COLORS
                        }
                    }
                    for ($z = 0; $z < $remainder; $z++) {
                        $ret .= $writer->inttobyte(0);
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
                for ($p = 0; $p < strlen($bOut); $p += 8) {
                    $byte = bindec(substr($bOut, $p, 8));
                    $byteCount++;
                    $ret .= $writer->inttobyte($byte);
                }
                $remainder = $byteCount % 4;
                for ($z = 0; $z < $remainder; $z++) {
                    $ret .= $writer->inttobyte(0xff);
                }
            }
        }

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
        $f = @fopen($filename, "w");
        if ($f === false) {
            trigger_error("Cannot write icon to file \"$filename\"");
            return false;
        }
        $data = $this->createAsString($images);
        if ($data === false) {
            return false;
        }
        fwrite($f, $data);
        fclose($f);
        
        return true;
    }
    
    /**
     * Creates icon and prints it to standard output. 
     * Note: use proper header("Content-type: image/x-icon")
     * @param resource|resource[]|GdImage|GdImage[] $images Target Image resource (Can be array of image resources/GdImages)     
     */
    public function createToPrint($images): void
    {
        echo $this->createAsString($images);
    }

}
