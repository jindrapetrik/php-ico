<?php

namespace Com\Jpexs\Image;

use Com\Jpexs\Stream\StreamWriter;

class AniWriter {

    private const AF_ICON = 1;

    /**
     * 
     * @var int
     */
    private $defaultFrameRate = 18;

    /**
     * 
     * @var resource[]|GdImage[]
     */
    private $images = [];

    /**
     * 
     * @var int[]
     */
    private $hotSpotsX = [];

    /**
     * 
     * @var int[]
     */
    private $hotSpotsY = [];

    /**
     * 
     * @var int[]
     */
    private $frameRates = [];

    /**
     * 
     * @var int[]
     */
    private $sequence = [];

    /**
     * 
     * @var string|null
     */
    private $name = null;

    /**
     * 
     * @var string|null
     */
    private $artist = null;

    public function getDefaultFrameRate(): int {
        return $this->defaultFrameRate;
    }

    public function setDefaultFrameRate(int $frameRate): void {
        $this->defaultFrameRate = $frameRate;
    }

    /**
     * 
     * @param resource|GdImage $image
     * @param int $hotSpotX
     * @param int $hotSpotY
     * @param int|null $frameRate
     */
    public function addImage($image, int $hotSpotX, int $hotSpotY, ?int $frameRate = null) {
        $imageId = count($this->images);
        $this->images[] = $image;
        $this->hotSpotsX[] = $hotSpotX;
        $this->hotSpotsY[] = $hotSpotY;
        if (count($this->sequence) > 0 && $frameRate !== null) {
            trigger_error("Cannot set framerate to image as animation is in sequence mode");
        } else {
            if ($frameRate === null) {
                $frameRate = $this->defaultFrameRate;
                if (count($this->frameRates) == 0) {
                    return;
                }
            }
            if (count($this->frameRates) == 0) {
                for ($i = 0; $i < $imageId; $i++) {
                    $this->frameRates[] = $this->defaultFrameRate;
                }
                $this->frameRates[] = $frameRate;
            }
        }
    }

    /**
     * 
     * @param int[] $sequence
     * @param int|int[]|null $frameRates Null = default framerate    
     */
    public function setSequence(array $sequence, $frameRates = null) {
        if (is_array($frameRates)) {
            if (count($frameRates) !== count($sequence)) {
                trigger_error("Sequence and frameRates array count does not match");
                return;
            }
            $this->frameRates = $frameRates;
        } else {
            if ($frameRates === null) {
                $frameRates = $this->defaultFrameRate;
            }
            $this->frameRates = [];
            for ($i = 0; $i < count($sequence); $i++) {
                $this->frameRates[] = $frameRates;
            }
        }
        $this->sequence = $sequence;
    }

    /**
     * 
     * @param string $filename
     * @return bool True on success, False on failure
     */
    public function createToFile(string $filename): bool {
        $data = $this->createAsString();
        if ($data === null) {
            return false;
        }

        $f = @fopen($filename, "w");
        if ($f === false) {
            trigger_error("Cannot write animation to file \"$filename\"");
            return false;
        }
        fwrite($f, $data);
        fclose($f);

        return true;
    }

    /**
     * 
     * @return bool True on success, False on failure
     */
    public function createToPrint(): bool {
        $data = $this->createAsString();
        if ($data === null) {
            return false;
        }
        echo $data;
        return true;
    }

    public function createAsString(): ?string {
        if (count($this->images) === 0) {
            trigger_error("No images added to ani file");
            return null;
        }
        $writer = new StreamWriter();

        $aconData = "ACON";

        if ($this->name !== null || $this->artist !== null) {
            $infoListData = "INFO";
            if ($this->name !== null) {
                $name = $this->name . chr(0);
                if (strlen($this->name) % 2 === 0) { //weird, but it must be
                    $name .= chr(0);
                }
                $inam = "INAM" . $writer->inttodword(strlen($name)) . $name;
                $infoListData .= $inam;
            }
            if ($this->artist !== null) {
                $artist = $this->artist . chr(0);
                if (strlen($this->artist) % 2 === 0) { //weird, but it must be
                    $artist .= chr(0);
                }                
                $iart = "IART" . $writer->inttodword(strlen($artist)) . $artist;
                $infoListData .= $iart;
            }

            $infoList = "LIST" . $writer->inttodword(strlen($infoListData)) . $infoListData;
            $aconData .= $infoList;
        }

        $nFrames = count($this->images);
        $nSteps = count($this->frameRates);
        if ($nSteps === 0) {
            $nSteps = $nFrames;
        }

        $anihData = $writer->inttodword(36) .
                $writer->inttodword($nFrames) .
                $writer->inttodword($nSteps) .
                $writer->inttodword(imagesx($this->images[0])) .
                $writer->inttodword(imagesy($this->images[0])) .
                $writer->inttodword($this->getImageBitCount($this->images[0])) .
                $writer->inttodword(1) .
                $writer->inttodword($this->defaultFrameRate) .
                $writer->inttodword(self::AF_ICON);

        $anih = "anih" . $writer->inttodword(strlen($anihData)) . $anihData;
        $aconData .= $anih;

        if (count($this->frameRates) > 0) {
            $rateData = "";
            foreach ($this->frameRates as $frameRate) {
                $rateData .= $writer->inttodword($frameRate);
            }
            $rate = "rate" . $writer->inttodword(strlen($rateData)) . $rateData;
            $aconData .= $rate;
        }

        if (count($this->sequence) > 0) {
            $seqData = "";
            foreach ($this->sequence as $imageId) {
                $seqData .= $writer->inttodword($imageId);
            }
            $seq = "seq " . $writer->inttodword(strlen($seqData)) . $seqData;
            $aconData .= $seq;
        }

        $framListData = "fram";
        foreach ($this->images as $imageId => $image) {
            $iconWriter = new IconWriter();
            $iconData = $iconWriter->createCursorAsString($image, $this->hotSpotsX[$imageId], $this->hotSpotsY[$imageId]);
            $icon = "icon" . $writer->inttodword(strlen($iconData)) . $iconData;
            $framListData .= $icon;
        }
        $framList = "LIST" . $writer->inttodword(strlen($framListData)) . $framListData;

        $aconData .= $framList;

        return "RIFF" . $writer->inttodword(strlen($aconData)) . $aconData;
    }

    /**
     * 
     * @param resource|GdImage $img
     * @return int
     */
    private function getImageBitCount($img): int {
        $usedColorCount = imagecolorstotal($img);

        if ($usedColorCount === 0) {
            $bitCount = 24;
        } elseif (($usedColorCount > 0) && ($usedColorCount <= 2)) {
            $bitCount = 1;
        } elseif (($usedColorCount > 2) && ($usedColorCount <= 16)) {
            $bitCount = 4;
        } elseif (($usedColorCount > 16) && ($usedColorCount <= 256)) {
            $bitCount = 8;
        }

        if ($bitCount === 24) {
            //search for alpha channel
            for ($x = 0; $x < $width; $x++) {
                for ($y = 0; $y < $height; $y++) {
                    $rgba = imagecolorat($img, $x, $y);
                    if (($rgba & 0x7F000000) >> 24) {
                        $bitCount = 32;
                        break 2;
                    }
                }
            }
        }
        return $bitCount;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getArtist(): string {
        return $this->artist;
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function setArtist(string $artist): void {
        $this->artist = $artist;
    }

}
