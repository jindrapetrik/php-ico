<?php

namespace Com\Jpexs\Image;

use Com\Jpexs\Stream\StreamReader;

class CursorImage extends IconImage {
    
    /**
     * @var int|null
     */
    private $hotSpotX = null;
    
    /**
     * @var int|null
     */
    private $hotSpotY = null;
    
    private function __construct($stream, int $iconId) {
        $this->init($stream, $iconId);
    }
    
    protected function init($stream, int $iconId) {
        $streamPos = ftell($stream);
        parent::init($stream, $iconId);
        $reader = new StreamReader($stream);
        $reader->seek($streamPos + 6 + $iconId * 16 + 4);
        $this->hotSpotX = $reader->readWord();
        $this->hotSpotY = $reader->readWord();        
        $reader->seek($streamPos);
    }
    
    public static function createFromStream($stream, int $iconId = 0) {
        return new CursorImage($stream, $iconId);
    }
    
    public function getHotSpotX(): ?int {
        return $this->hotSpotX;
    }

    public function getHotSpotY(): ?int {
        return $this->hotSpotY;
    }
}
