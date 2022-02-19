<?php

namespace Com\Jpexs\Image;

use Com\Jpexs\Stream\StreamReader;

/**
 * Class for reading animated cursors (.ani)
 * 
 * @implements Iterator<IconImage>
 */
class AniReader implements \Iterator {

    /**
     * 
     * @var resource
     */
    private $stream;

    private const AF_ICON = 1;

    /**
     * @var int[]
     */
    private $rate = [];

    /**
     * @var int[]
     */
    private $sequence = [];

    /**
     * @var array|null
     */
    private $header;

    /**
     * @var string|null
     */
    private $name = null;

    /**
     * @var string|null
     */
    private $artist = null;

    /**
     * 
     * @var IconImage[]
     */
    private $iconImages = [];
    private $iterablePosition = 0;

    private function __construct(string $filename) {
        $this->stream = fopen($filename, "rb");
        $this->readAni();
        fclose($this->stream);
    }

    /**
     * format:
     * RIFF( 'ACON'
     *       LIST( 'INFO'
     *           INAM( <name> )
     *           IART( <artist> )
     *       )
     *       anih( <anihdr> )
     *       [rate( <rateinfo> )  ]
     *       ['seq '( <seq_info> )]
     *   LIST( 'fram' icon( <icon_file> ) ... )
     *   )
     */
    private function readAni() {
        $reader = new StreamReader($this->stream);
        $riffId = $reader->read(4);
        if ($riffId !== "RIFF") {
            return;
        }
        $fileSize = $reader->readDWord();
        $formType = $reader->read(4);
        if ($formType !== "ACON") {
            return;
        }

        while ($reader->getPosition() < $fileSize + 8) {
            $this->readChunk($reader);
        }
    }

    private function readChunk(StreamReader $reader) {
        $chunkId = $reader->read(4);
        $chunkSize = $reader->readDWord();
        $chunkData = $reader->read($chunkSize);

        $subStream = fopen("php://memory", "r+");
        fwrite($subStream, $chunkData);
        rewind($subStream);

        $subReader = new StreamReader($subStream);

        switch ($chunkId) {
            case "LIST":
                $subReader->read(4); //listType
                while ($subReader->getPosition() < $chunkSize) {
                    $this->readChunk($subReader);
                }
                break;
            case "INAM":
                $this->name = $chunkData;
                break;
            case "IART":
                $this->artist = $chunkData;
                break;
            case "anih":
                $this->header = [
                    "cbSize" => $subReader->readDWord(),
                    "nFrames" => $subReader->readDWord(),
                    "nSteps" => $subReader->readDWord(),
                    "iWidth" => $subReader->readDWord(),
                    "iHeight" => $subReader->readDWord(),
                    "iBitCount" => $subReader->readDWord(),
                    "nPlanes" => $subReader->readDWord(),
                    "iDispRate" => $subReader->readDWord(),
                    "bfAttributes" => $subReader->readDWord()
                ];
                break;
            case "rate":
                $this->rate = [];
                for ($i = 0; $i < $this->header["nSteps"]; $i++) {
                    $this->rate[] = $subReader->readDword();
                }
                break;
            case "seq ":
                $this->sequence = [];
                for ($i = 0; $i < $this->header["nSteps"]; $i++) {
                    $this->sequence[] = $subReader->readDword();
                }
                break;
            case "icon":
                $this->iconImages[] = IconImage::createFromStream($subStream, 0);
                break;
        }
    }

    /**
     * 
     * @return int[]
     */
    public function getRate(): array {
        return $this->rate;
    }

    /**
     * 
     * @return int[]
     */
    public function getSequence(): array {
        return $this->sequence;
    }

    public function getHeader(): ?array {
        return $this->header;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function getArtist(): ?string {
        return $this->artist;
    }

    public static function createFromAniFile(string $filename): self {
        return new AniReader($filename);
    }

    public function rewind(): void {
        $this->iterablePosition = 0;
    }

    public function current() {
        return $this->getIconImage($this->iterablePosition);
    }

    public function key() {
        return $this->iterablePosition;
    }

    public function next(): void {
        ++$this->iterablePosition;
    }

    public function valid(): bool {
        return isset($this->iconImages[$this->iterablePosition]);
    }

    public function getWidth(): int {
        return $this->header["iWidth"];
    }

    public function getHeight(): int {
        return $this->header["iHeight"];
    }

    public function getColorsBitCount(): int {
        return $this->header["iBitCount"];
    }

    public function getDispRate(): int {
        return $this->header["iDispRate"];
    }

    public function getNumSteps(): int {
        return $this->header["nSteps"];
    }

    public function getIconImage(int $imageIndex): IconImage {
        return $this->iconImages[$imageIndex];
    }

    public function getIconImageCount(): int {
        return count($this->iconImages);
    }

}
