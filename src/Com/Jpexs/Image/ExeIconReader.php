<?php

namespace Com\Jpexs\Image;

use Com\Jpexs\Stream\StreamReader;
use \Com\Jpexs\Stream\StreamWriter;

/**
 * @implements Iterator<string, IconReader>
 */
class ExeIconReader implements \Iterator {

    private const RT_ICON = 3;
    private const RT_GROUP_ICON = self::RT_ICON + 11;

    /**
     * 
     * @var array<string, IconReader>
     */
    private $iconData = [];

    /**
     * 
     * @var resource
     */
    private $stream;

    private function __construct(string $filename) {
        $this->stream = fopen($filename, "rb");
        $this->readIcons();
        fclose($this->stream);
    }

    public static function createFromExeFile(string $filename): self {
        return new ExeIconReader($filename);
    }

    private function readIcons() {
        $reader = new StreamReader($this->stream);
        $mz = $reader->read(2);
        if ($mz !== "MZ") {
            trigger_error("Invalid EXE header - MZ expected");
            return false;
        }
        $reader->seek(60);
        $offsetToNewHeader = $reader->readDWord();
        $reader->seek($offsetToNewHeader);
        $pe = $reader->read(2);
        if ($pe !== "PE") {
            trigger_error("Invalid EXE header - PE expected");
            return false;
        }
        $reader->skip(4);
        $numberOfSections = $reader->readWord();
        $reader->skip(12);
        $sizeOfOptionalHeader = $reader->readWord();
        $posMagic = $reader->getPosition() + 2;
        $reader->seek($posMagic + $sizeOfOptionalHeader);

        $rsrcVirtualAddress = null;
        $startOfRsrc = null;

        for ($i = 0; $i < $numberOfSections; $i++) {
            $sectionName[$i] = trim($reader->read(8));
            $reader->readDWord(); //virtualSize
            $virtualAddress[$i] = $reader->readDWord();
            $reader->readDWord(); //physicalSize
            $physicalOffset[$i] = $reader->readDWord();
            $reader->skip(16);
            if ($sectionName[$i] === ".rsrc") {
                $rsrcVirtualAddress = $virtualAddress[$i];
                $reader->seek($physicalOffset[$i]);
                $startOfRsrc = $physicalOffset[$i];
                $resDirEntry = [];
                $this->readResDirectoryEntry($startOfRsrc, $rsrcVirtualAddress, $reader, $resDirEntry, $physicalOffset[$i]);
                $iconCounts = [];
                $icons = [];
                $groupIdentifiers = [];
                foreach ($resDirEntry["Subdir"] as $key => $val) {
                    if ($key == self::RT_GROUP_ICON) {
                        $grp = 0;
                        foreach ($resDirEntry["Subdir"][$key]["Subdir"] as $groupIdentifier => $val2) {
                            foreach ($resDirEntry["Subdir"][$key]["Subdir"][$groupIdentifier]["Subdir"] as $key3 => $val3) {
                                $reader->seek($val3["DataOffset"]);
                                $reader->readWord(); //reserved
                                $reader->readWord(); //type
                                $iconCount = $reader->readWord();
                                $iconCounts[] = $iconCount;
                                for ($s = 0; $s < $iconCount; $s++) {
                                    $icons[$grp][$s]["Width"] = $reader->readByte();
                                    $icons[$grp][$s]["Height"] = $reader->readByte();
                                    $icons[$grp][$s]["ColorCount"] = $reader->readWord();
                                    $icons[$grp][$s]["Planes"] = $reader->readWord();
                                    $icons[$grp][$s]["BitCount"] = $reader->readWord();
                                    $icons[$grp][$s]["BytesInRes"] = $reader->readDWord();
                                    $icons[$grp][$s]["IconId"] = $reader->readWord();
                                }
                                $reader->seek($val3["DataOffset"]);
                                $grp++;
                                $groupIdentifiers[] = $groupIdentifier;
                            }
                        }
                    }
                }
                foreach ($resDirEntry["Subdir"] as $key => $val) {
                    if ($key == self::RT_ICON) {
                        foreach ($resDirEntry["Subdir"][$key]["Subdir"] as $key2 => $val2) {
                            for ($grp = 0; $grp < count($icons); $grp++) {
                                for ($s = 0; $s < count($icons[$grp]); $s++) {
                                    foreach ($resDirEntry["Subdir"][$key]["Subdir"][$icons[$grp][$s]["IconId"]]["Subdir"] as $key3 => $val3) {
                                        $reader->seek($val3["DataOffset"]);
                                        $icons[$grp][$s]["Data"] = $reader->read($val3["DataSize"]);
                                        $icons[$grp][$s]["DataSize"] = $val3["DataSize"];                                        
                                    }
                                }
                            }
                        }
                    }
                }

                $writer = new StreamWriter();

                for ($grp = 0; $grp < count($icons); $grp++) {
                    $iconData = "";
                    $iconData .= $writer->inttoword(0);
                    $iconData .= $writer->inttoword(1);
                    $iconData .= $writer->inttoword(count($icons[$grp]));
                    $offset = 6 + 16 * count($icons[$grp]);
                    for ($s = 0; $s < count($icons[$grp]); $s++) {
                        $iconData .= $writer->inttobyte($icons[$grp][$s]["Width"]);
                        $iconData .= $writer->inttobyte($icons[$grp][$s]["Height"]);
                        $iconData .= $writer->inttoword($icons[$grp][$s]["ColorCount"]);
                        $iconData .= $writer->inttoword($icons[$grp][$s]["Planes"]);
                        $iconData .= $writer->inttoword($icons[$grp][$s]["BitCount"]);
                        $iconData .= $writer->inttodword($icons[$grp][$s]["BytesInRes"]);
                        $iconData .= $writer->inttodword($offset);
                        $offset += $icons[$grp][$s]["DataSize"];
                    }
                    for ($s = 0; $s < count($icons[$grp]); $s++) {
                        $iconData .= $icons[$grp][$s]["Data"];
                    }

                    $this->iconData[$groupIdentifiers[$grp]] = $iconData;
                }
            }
        }        
    }

    private function readResDirectoryEntry(int $startOfRsrc, int $rsrcVirtualAddress, StreamReader $reader, &$parentRes, $offset) {
        $lastPos = $reader->getPosition();
        $res = [];
        $reader->seek($offset);
        //IMAGE_RESOURCE_DIRECTORY
        $reader->readDWord(); //characteristics
        $reader->readDWord(); //timeDateStamp
        $reader->readWord(); //majorVersion
        $reader->readWord(); //minorVersion
        $numberOfNamedEntries = $reader->readWord();
        $numberOfIdEntries = $reader->readWord();
        for ($q = 0; $q < $numberOfNamedEntries + $numberOfIdEntries; $q++) {
            //IMAGE_RESOURCE_DIRECTORY_ENTRY
            $resName = $reader->readDWord();
            $lastPos2 = $reader->getPosition();
            if ($resName >= 0x80000000) {
                //String Name
                $resNameOffset = $resName - 0x80000000;
                $reader->seek($startOfRsrc + $resNameOffset);
                $stringLength = $reader->readWord();
                $identificatorUtf16 = $reader->read($stringLength * 2);
                $identificator = iconv("UTF16", "UTF8", $identificatorUtf16);
                $reader->seek($lastPos2);
            } else {
                //Integer Id
                $identificator = $resName;
            }

            $resOffsetToData = $reader->readDWord();
            if ($resOffsetToData >= 0x80000000) {
                $subResOffset = $resOffsetToData - 0x80000000;
                $this->readResDirectoryEntry($startOfRsrc, $rsrcVirtualAddress, $reader, $res[$identificator], $startOfRsrc + $subResOffset);
            } else {
                $RawDataOffset = $resOffsetToData;
                $lastPos2 = $reader->getPosition();
                $reader->seek($startOfRsrc + $RawDataOffset);
                //IMAGE_RESOURCE_DATA_ENTRY
                $OffsetToData = $reader->readDWord();
                $res[$identificator]["DataOffset"] = $startOfRsrc - $rsrcVirtualAddress + $OffsetToData;
                $res[$identificator]["DataSize"] = $reader->readDWord();
                $reader->readDWord(); //codepage
                $reader->readDWord(); //reserved
                $reader->seek($lastPos2);
            }
        }
        $reader->seek($lastPos);
        $parentRes["Subdir"] = $res;
    }         
    
    public function getIconCount(): int {
        return count($this->iconData);
    }
    
    /**
     * 
     * @return IconReader|null
     */
    public function getIcon(string $iconIndex): ?IconReader {
        $data = $this->getIconData($iconIndex);
        if ($data === null) {
            return null;
        }
        $stream = fopen("php://memory", "r+");
        fwrite($stream, $data);
        rewind($stream);        
        return IconReader::createFromStream($stream);  
    }

    public function getIconData(string $iconIndex): ?string {
        if (!array_key_exists($iconIndex, $this->iconData)) {
            return null;
        }
        return $this->iconData[$iconIndex];
    }
    
    public function saveIcon(string $iconIndex, string $filename): bool {
        if (!array_key_exists($iconIndex, $this->iconData)) {
            return null;
        }
        file_put_contents($filename, $this->iconData[$iconIndex]);
        return true;
    }
    
    public function rewind(): void {
        reset($this->iconData);
    }
    
    public function current() {
        return $this->getIcon($this->key());
    }
    
    public function key() {
        return key($this->iconData);
    }
    
    public function next(): void {
        next($this->iconData);
    }
    
    public function valid(): bool {
        return key($this->iconData) !== null;
    }
    
    public function getIconIndices() {
        return array_keys($this->iconData);
    }
}
