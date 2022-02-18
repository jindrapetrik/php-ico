<?php

namespace Com\Jpexs\Image;

use Com\Jpexs\Stream\StreamReader;

/**
 * @implements Iterator<IconReaderImage>
 */
class IconReader implements \Iterator {
    
    /**
     * 
     * @var IconReaderImage[]
     */
    private $images;
        
    private $iterablePosition = 0;
    
    private function __construct($stream) {
        $reader = new StreamReader($stream);
        $reader->skip(4);
        $count = $reader->readWord();                
        $iconImages = [];
        for ($i = 0; $i < $count; $i++) {
            $reader->seek(0);
            $iconImage = IconReaderImage::createFromStream($stream, $i);
            $iconImages[] = $iconImage;
        }       
        $this->images = $iconImages;
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
        return isset($this->images[$this->iterablePosition]);
    }

    public static function createFromIcoFile(string $filename): self {        
        $stream = fopen($filename, "rb");
        return new IconReader($stream);
    }  
    
    public static function createFromStream($stream): self {        
        return new IconReader($stream);
    }  
    
    public function getIconImage(int $imageIndex): IconReaderImage {
        return $this->images[$imageIndex];
    }
    
    public function getIconImageCount(): int {
        return count($this->images);
    }
       
}