<?php

namespace Com\Jpexs\Stream;

class StreamWriter { 

    public function inttobyte(int $n): string {
        return chr($n);
    }

    public function inttodword(int $n): string {
        return chr($n & 255) . chr(($n >> 8) & 255) . chr(($n >> 16) & 255) . chr(($n >> 24) & 255);
    }

    public function inttoword(int $n): string {
        return chr($n & 255) . chr(($n >> 8) & 255);
    }
}
