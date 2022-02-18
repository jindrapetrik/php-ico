<?php

namespace Com\Jpexs\Stream;

class StreamWriter { 

    public function inttobyte(int $n): string {
        return chr($n);
    }

    public function inttodword(int $n): string {
        return pack("V", $n);
    }

    public function inttoword(int $n): string {
        return pack("v", $n);
    }
}
