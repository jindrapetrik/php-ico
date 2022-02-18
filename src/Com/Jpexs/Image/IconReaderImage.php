<?php

namespace Com\Jpexs\Image;

use Com\Jpexs\Stream\StreamReader;

class IconReaderImage {

    /**
     * 
     * @var int
     */
    private $width;

    /**
     * 
     * @var int
     */
    private $height;

    /**
     * 
     * @var int|null
     */
    private $colorsBitCount;

    /**
     * 
     * @var resource
     */
    private $stream;

    /**
     * 
     * @var int
     */
    private $iconId;
    
    /**
     * 
     * @var int
     */
    private $streamPos = 0;
    
    /**
     * 
     * @var array
     */
    private $iconData;

    /**
     * 
     * @param resource $stream
     * @param int $iconId
     */
    private function __construct($stream, int $iconId) {        
        $this->stream = $stream;                
        $this->iconId = $iconId;
        $this->streamPos = ftell($stream);
        $this->readBasicInfo();
        $this->iconData = $this->readImage();
    }

    private function readBasicInfo(): void {
        $reader = new StreamReader($this->stream);
        $reader->seek($this->streamPos + 6 + $this->iconId * 16);
        $width = $reader->readByte();
        $height = $reader->readByte();

        if ($width === 0) {
            $width = 256;
        }
        if ($height === 0) {
            $height = 256;
        }
        $this->width = $width;
        $this->height = $height;
        $reader->skip(4);
        $this->colorsBitCount = $reader->readWord();       
        $reader->seek($this->streamPos);             
    }

    public static function createFromStream($stream, int $iconId) {
        return new IconReaderImage($stream, $iconId);
    }

    private function readImage(): array {        
        $reader = new StreamReader($this->stream);
        $reader->seek($this->streamPos + 6 + $this->iconId * 16 + 8);
        $dataLength = $reader->readDWord();
        $offset = $reader->readDWord();
        $reader->seek($offset);      
        
        $width = $this->width;
        $height = $this->height;

        $icon["info"] = [];
        $icon["info"]["header_size"] = $reader->readLongInt();
        if ($icon["info"]["header_size"] === 0x474E5089) {
            //it's a PNG file
            $icon["png_data"] = chr(0x89) . "PNG" . $reader->read($dataLength - 4);
            return $icon;
        } else {
            $icon["info"]["image_width"] = $reader->readLongInt();
            $icon["info"]["image_height"] = $reader->readLongInt();
            $icon["info"]["number_of_image_planes"] = $reader->readWord();
            $icon["info"]["bits_per_pixel"] = $reader->readWord();
            $icon["info"]["compression_method"] = $reader->readLongInt();
            $icon["info"]["size_of_bitmap"] = $reader->readLongInt();
            $icon["info"]["horz_resolution"] = $reader->readLongInt();
            $icon["info"]["vert_resolution"] = $reader->readLongInt();
            $icon["info"]["num_color_used"] = $reader->readLongInt();
            $icon["info"]["num_significant_colors"] = $reader->readLongInt();
        }

        $biBitCount = $this->colorsBitCount;

        if ($biBitCount <= 8) {

            $numColors = pow(2, $biBitCount);

            for ($b = 0; $b < $numColors; $b++) {
                $icon["palette"][$b]["b"] = $reader->readByte();
                $icon["palette"][$b]["g"] = $reader->readByte();
                $icon["palette"][$b]["r"] = $reader->readByte();
                $reader->readByte();
            }

            $remainder = (4 - ceil(($width / (8 / $biBitCount))) % 4) % 4;

            for ($y = $height - 1; $y >= 0; $y--) {
                $reader->setCurrentBit(0);
                for ($x = 0; $x < $width; $x++) {
                    $c = $reader->readBits($biBitCount);
                    $icon["data"][$x][$y] = $c;
                }
                if ($reader->getCurrentBit() != 0) {
                    $reader->readByte();
                }
                for ($g = 0; $g < $remainder; $g++) {
                    $reader->readByte();
                }
            }
        } elseif ($biBitCount == 24) {
            $remainder = $width % 4;

            for ($y = $height - 1; $y >= 0; $y--) {
                for ($x = 0; $x < $width; $x++) {
                    $b = $reader->readByte();
                    $g = $reader->readByte();
                    $r = $reader->readByte();
                    $icon["data"][$x][$y]["r"] = $r;
                    $icon["data"][$x][$y]["g"] = $g;
                    $icon["data"][$x][$y]["b"] = $b;
                }
                for ($z = 0; $z < $remainder; $z++) {
                    $reader->readByte();
                }
            }
        } elseif ($biBitCount == 32) {
            $remainder = $width % 4;

            for ($y = $height - 1; $y >= 0; $y--) {
                for ($x = 0; $x < $width; $x++) {
                    $b = $reader->readByte();
                    $g = $reader->readByte();
                    $r = $reader->readByte();
                    $alpha = $reader->readByte();
                    $icon["data"][$x][$y]["r"] = $r;
                    $icon["data"][$x][$y]["g"] = $g;
                    $icon["data"][$x][$y]["b"] = $b;
                    $icon["data"][$x][$y]["alpha"] = $alpha;
                }
                for ($z = 0; $z < $remainder; $z++) {
                    $reader->readByte();
                }
            }
        }

        //Mask
        $remainder = (4 - ceil(($width / (8 / 1))) % 4) % 4;
        for ($y = $height - 1; $y >= 0; $y--) { 
            $reader->setCurrentBit(0);
            for ($x = 0; $x < $width; $x++) {
                $c = $reader->readBits(1);
                $icon["mask"][$x][$y] = $c;
            }
            if ($reader->getCurrentBit() != 0) {
                $reader->readByte();
            }
            for ($g = 0; $g < $remainder; $g++) {
                $reader->readByte();
            }
        }
        return $icon;
    }

    /**
     * @return resource|GdImage
     */
    public function getImage() {   
        $icon = $this->iconData;
        if (array_key_exists("png_data", $icon)) {
            $image = imagecreatefrompng('data://image/png;base64,' . base64_encode($icon["png_data"]));
            imagesavealpha($image, true);
            return $image;
        }
                
        $isTransparent = false;
        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                if ($icon["mask"][$x][$y] == 1) {
                    $isTransparent = true;
                    break 2;
                }
            }
        }
        
        if ($this->colorsBitCount >= 24) {
            $image = imagecreatetruecolor($this->width, $this->height);
            if ($this->colorsBitCount == 32 || $isTransparent) {
                imagealphablending($image, false);
                imagesavealpha($image, true);             
            }
            for ($y = 0; $y < $this->height; $y++) {
                for ($x = 0; $x < $this->width; $x++) {
                    $r = $icon["data"][$x][$y]["r"];
                    $g = $icon["data"][$x][$y]["g"];
                    $b = $icon["data"][$x][$y]["b"];
                    if ($this->colorsBitCount == 32 || $icon["mask"][$x][$y] == 1) {                        
                        if ($icon["mask"][$x][$y] == 1) {
                            $alpha = 127;
                        }else{
                            $alpha = 127 - round($icon["data"][$x][$y]["alpha"] * 127 / 255);
                        }
                        
                        $color = imagecolorexactalpha($image, $r, $g, $b, $alpha);
                        if ($color == -1) {
                            $color = imagecolorallocatealpha($image, $r, $g, $b, $alpha);
                        }
                    } else {
                        $color = imagecolorexact($image, $r, $g, $b);
                        if ($color == -1) {
                            $color = imagecolorallocate($image, $r, $g, $b);
                        }
                    }

                    imagesetpixel($image, $x, $y, $color);
                }
            }
        } else {
            $image = imagecreate($this->width, $this->height);
            $palette = [];
            for ($p = 0; $p < count($icon["palette"]); $p++) {
                $palette[$p] = imagecolorallocate($image, $icon["palette"][$p]["r"], $icon["palette"][$p]["g"], $icon["palette"][$p]["b"]);
            }

            for ($y = 0; $y < $this->height; $y++) {
                for ($x = 0; $x < $this->width; $x++) {
                    imagesetpixel($image, $x, $y, $palette[$icon["data"][$x][$y]]);
                }
            }
            
            if ($isTransparent) {                                
                $colorsTotal = imagecolorstotal($image);
                
                if ($colorsTotal === 256)
                {
                    $image2 = imagecreatetruecolor($this->width, $this->height);
                    imagecopy($image2, $image, 0, 0, 0, 0, $this->width, $this->height);
                    imagedestroy($image);
                    $image = $image2;
                    imagealphablending($image, false);
                    imagesavealpha($image, true);
                    
                    $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
                    for ($y = 0; $y < $this->height; $y++) {
                        for ($x = 0; $x < $this->width; $x++) {
                            if ($icon["mask"][$x][$y] == 1) {
                                imagesetpixel($image, $x, $y, $transparent);
                            }
                        }
                    }
                }
                else
                {
                    //find new non existing color
                    $tr = 0;
                    $tg = 0;
                    $tb = 0;
                    while (imagecolorexact($image, $tr, $tg, $tb) > -1)
                    {
                        $tr++;
                        if($tr == 256) {
                            $tr = 0;
                            $tg++;

                            if($tg == 256) {
                                $tr = 0;
                                $tg = 0;                            
                                $tb++;
                            }
                        }                                    
                    }
                
                    $transparent = imagecolorallocate($image, $tr, $tg, $tb);
                    for ($y = 0; $y < $this->height; $y++) {
                        for ($x = 0; $x < $this->width; $x++) {
                            if ($icon["mask"][$x][$y] == 1) {
                                imagesetpixel($image, $x, $y, $transparent);
                            }
                        }
                    }
                    imagecolortransparent($image, $transparent);
                }
            }                
        }                     

        return $image;
    }

    /**
     * 
     * @return resource
     */
    public function getStream() {
        return $this->stream;
    }

    public function getStreamOffset(): int {
        return $this->streamOffset;
    }

    public function getWidth(): int {
        return $this->width;
    }

    public function getHeight(): int {
        return $this->height;
    }

    public function getColorsBitCount(): int {
        return $this->colorsBitCount;
    }

}
