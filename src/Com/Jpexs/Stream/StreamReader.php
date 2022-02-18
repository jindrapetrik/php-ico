<?php

namespace Com\Jpexs\Stream;

class StreamReader {

    /**
     * 
     * @var resource
     */
    private $stream;

    /**
     * 
     * @var int
     */
    private $currentBit = 0;

    /**
     * 
     * @var int
     */
    private $startPos = 0;

    /**
     * 
     * @param resource $stream
     */
    public function __construct($stream, $startPos = 0) {
        $this->stream = $stream;
        $this->startPos = $startPos;
    }

    private function decbin8(int $d): string {
        return $this->decbinx($d, 8);
    }

    private function decbinx(int $d, int $n): string {
        $bin = decbin($d);
        $sbin = strlen($bin);
        for ($j = 0; $j < $n - $sbin; $j++) {
            $bin = "0$bin";
        }
        return $bin;
    }

    private function retBits(int $byte, int $start, int $len): int {
        $bin = $this->decbin8($byte);
        $r = bindec(substr($bin, $start, $len));
        return $r;
    }

    public function getCurrentBit(): int {
        return $this->currentBit;
    }

    public function setCurrentBit(int $value): void {
        $this->currentBit = $value;
    }

    public function getPosition(): int {
        return ftell($this->stream) - $this->startPos;
    }

    public function seek(int $position, int $whence = SEEK_SET): bool {
        if ($whence === SEEK_END) {
            throw new RuntimeException("SEEK_END not implemented");
        }
        return fseek($this->stream, ($whence === SEEK_SET ? $this->startPos : 0) + $position, $whence) === 0;
    }

    public function skip(int $length): bool {
        return $this->seek($length, SEEK_CUR);
    }

    public function readBits(int $count): int {
        $byte = $this->readByte();
        $lastCBit = $this->currentBit;
        $this->currentBit += $count;
        if ($this->currentBit == 8) {
            $this->currentBit = 0;
        } else {
            fseek($this->stream, ftell($this->stream) - 1);
        }
        return $this->retBits($byte, $lastCBit, $count);
    }

    public function readByte(): int {
        return ord(fread($this->stream, 1));
    }

    public function readWord(): int {
        $b1 = $this->readByte();
        $b2 = $this->readByte();
        return $b2 * 256 + $b1;
    }

    public function readLongInt(): int {
        return $this->readDWord();
    }

    public function readDWord(): int {
        $b1 = $this->readWord();
        $b2 = $this->readWord();
        return $b2 * 65536 + $b1;
    }

    /**
     * @return string|false
     */
    public function read(int $length) {
        return fread($this->stream, $length);
    }

    public function close() {
        fclose($this->stream);
    }

}
