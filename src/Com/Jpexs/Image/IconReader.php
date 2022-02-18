<?php

namespace Com\Jpexs\Image;

use Com\Jpexs\Stream\StreamReader;

/**
 * Class for reading icons (.ico) and cursors (.cur)
 * 
 * @implements Iterator<IconImage>
 */
class IconReader implements \Iterator {
    
    public const TYPE_ICON = 1;
    
    public const TYPE_CURSOR = 2;
    
    /**
     * 
     * @var IconImage[]
     */
    private $iconImages;
        
    private $iterablePosition = 0;
    
    /**
     * @see IconReader::TYPE_*
     * @var int
     */
    private $type;
    
    private function __construct($stream) {
        $reader = new StreamReader($stream);
        $reader->skip(2); //reserved
        $this->type = $reader->readWord();
        $count = $reader->readWord();                
        $iconImages = [];
        for ($i = 0; $i < $count; $i++) {
            $reader->seek(0);
            if ($this->type === self::TYPE_CURSOR) {
                $iconImage = CursorImage::createFromStream($stream, $i);               
            } else {
                $iconImage = IconImage::createFromStream($stream, $i);
            }
            $iconImages[] = $iconImage;
        }       
        $this->iconImages = $iconImages;
        fclose($stream);
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

    public static function createFromIcoFile(string $filename): self {        
        $stream = fopen($filename, "rb");
        return new IconReader($stream);
    } 
    
    public static function createFromCurFile(string $filename): self {        
        $stream = fopen($filename, "rb");
        return new IconReader($stream);
    } 
    
    public static function createFromStream($stream): self {        
        return new IconReader($stream);
    }  
    
    public function getIconImage(int $imageIndex): IconImage {
        return $this->iconImages[$imageIndex];
    }
    
    public function getIconImageCount(): int {
        return count($this->iconImages);
    }
     
    /**
     * @see IconReader::TYPE_*
     * @return int
     */
    public function getType(): int {
        return $this->type;
    }
    
    public function getCursorImage(): ?CursorImage {
        if ($this->type === self::TYPE_CURSOR) {
            return $this->iconImages[0];
        }
        return null;
    }
    
}
