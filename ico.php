<?php

/**
 * @package com.jpexs.image.ico
 *
 * JPEXS ICO Image functions
 * @version 3.0
 * @author JPEXS
 * @copyright (c) JPEXS 2004-2022
 *
 * Webpage: http://www.jpexs.com
 * Email: jpexs@jpexs.com
 *
 * 
 * License:
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3.0 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.
 */

namespace Com\Jpexs\Image {

    class Icon {

        /** Image with maximum colors */
        public const MAX_COLOR = -2;

        /** Image with maximal size */
        public const MAX_SIZE = -2;
        public const COLORS_2 = 1;
        public const COLORS_16 = 4;
        public const COLORS_256 = 8;
        public const COLORS_TRUE_COLOR = 24;
        public const COLORS_TRUE_COLOR_ALPHA = 32;

        /**
         * 
         * @var int
         */
        private $currentBit = 0;

        /**
         * Reads image from a ICO file
         *
         * @param string $filename Target ico file to load
         * @param int $numColorBits Icon color bits - color count (see Icon::COLORS_* constants) (For multiple icons ico file) or Icon::MAX_COLOR
         * @param int $icoSize Icon width (For multiple icons ico file) or Icon::MAX_SIZE
         * @param int $alphaBgR Background color R value for alpha-channel images (Default is White)
         * @param int $alphaBgG Background color G value for alpha-channel images (Default is White)
         * @param int $alphaBgB Background color B value for alpha-channel images (Default is White)
         * @return resource|false Image resource
         */
        public function imageCreateFromIco(string $filename, int $numColorBits = 16, int $icoSize = 16, int $alphaBgR = 255, int $alphaBgG = 255, int $alphaBgB = 255) {
            $icons = $this->getIconsInfo($filename);

            $iconId = -1;

            $colMaxIconId = -1;
            $sizeMaxIconId = -1;

            for ($p = 0; $p < count($icons); $p++) {
                $icons[$p]["number_of_colors"] = pow(2, $icons[$p]["info"]["bits_per_pixel"]);
            }

            for ($p = 0; $p < count($icons); $p++) {
                if (($colMaxIconId == -1) || ($icons[$p]["bit_count"] >= $icons[$colMaxIconId]["bit_count"])) {
                    if (($icoSize == $icons[$p]["width"]) || ($icoSize === self::MAX_SIZE)) {
                        $colMaxIconId = $p;
                    }
                }

                if (($sizeMaxIconId == -1)or($icons[$p]["width"] >= $icons[$sizeMaxIconId]["width"])) {
                    if (($numColorBits == $icons[$p]["bit_count"]) || ($numColorBits === self::MAX_COLOR)) {
                        $sizeMaxIconId = $p;
                    }
                }

                if ($icons[$p]["bit_count"] == $numColorBits) {
                    if ($icons[$p]["width"] == $icoSize) {
                        $iconId = $p;
                    }
                }
            }

            if ($numColorBits == self::MAX_COLOR) {
                $iconId = $colMaxIconId;
            }
            if ($icoSize == self::MAX_SIZE) {
                $iconId = $sizeMaxIconId;
            }

            $colName = $numColorBits;

            if ($icoSize == self::MAX_SIZE) {
                $icoSize = "Max";
            }
            if ($colName == self::MAX_COLOR) {
                $colName = "Max";
            }
            if ($iconId == -1) {
                trigger_error("Icon with $colName color bits and $icoSize x $icoSize size doesn't exist in this file!");
                return false;
            }


            $this->readIcon($filename, $iconId, $icons);
            if (array_key_exists("png_data", $icons[$iconId])) {
                $img = imagecreatefrompng('data://image/png;base64,' . base64_encode($icons[$iconId]["png_data"]));
                imagesavealpha($img, true);
                return $img;
            }

            if ($icons[$iconId]["info"]["bits_per_pixel"] == 0) {
                $icons[$iconId]["info"]["bits_per_pixel"] = 24;
            }

            $biBitCount = $icons[$iconId]["info"]["bits_per_pixel"];
            if ($biBitCount == 0) {
                $biBitCount = 1;
            }

            $icons[$iconId]["bit_count"] = $icons[$iconId]["info"]["bits_per_pixel"];

            if ($icons[$iconId]["bit_count"] >= 24) {
                $img = imagecreatetruecolor($icons[$iconId]["width"], $icons[$iconId]["height"]);
                if ($icons[$iconId]["bit_count"] == 32):
                    $backcolor = imagecolorallocate($img, $alphaBgR, $alphaBgG, $alphaBgB);
                    imagefilledrectangle($img, 0, 0, $icons[$iconId]["width"] - 1, $icons[$iconId]["height"] - 1, $backcolor);
                endif;
                for ($y = 0; $y < $icons[$iconId]["height"]; $y++) {
                    for ($x = 0; $x < $icons[$iconId]["width"]; $x++) {
                        $r = $icons[$iconId]["data"][$x][$y]["r"];
                        $g = $icons[$iconId]["data"][$x][$y]["g"];
                        $b = $icons[$iconId]["data"][$x][$y]["b"];
                        if ($icons[$iconId]["bit_count"] == 32) {
                            $Alpha = 127 - round($icons[$iconId]["data"][$x][$y]["alpha"] * 127 / 255);
                            if ($icons[$iconId]["mask"][$x][$y] == 1) {
                                $Alpha = 127;
                            }
                            $color = imagecolorexactalpha($img, $r, $g, $b, $Alpha);
                            if ($color == -1) {
                                $color = imagecolorallocatealpha($img, $r, $g, $b, $Alpha);
                            }
                        } else {
                            $color = imagecolorexact($img, $r, $g, $b);
                            if ($color == -1) {
                                $color = imagecolorallocate($img, $r, $g, $b);
                            }
                        }

                        imagesetpixel($img, $x, $y, $color);
                    }
                }
            } else {
                $img = imagecreate($icons[$iconId]["width"], $icons[$iconId]["height"]);
                $palette = [];
                for ($p = 0; $p < count($icons[$iconId]["palette"]); $p++) {
                    $palette[$p] = imagecolorallocate($img, $icons[$iconId]["palette"][$p]["r"], $icons[$iconId]["palette"][$p]["g"], $icons[$iconId]["palette"][$p]["b"]);
                }

                for ($y = 0; $y < $icons[$iconId]["height"]; $y++) {
                    for ($x = 0; $x < $icons[$iconId]["width"]; $x++) {
                        imagesetpixel($img, $x, $y, $palette[$icons[$iconId]["data"][$x][$y]]);
                    }
                }
            }
            $isTransparent = false;
            for ($y = 0; $y < $icons[$iconId]["height"]; $y++) {
                for ($x = 0; $x < $icons[$iconId]["width"]; $x++) {
                    if ($icons[$iconId]["mask"][$x][$y] == 1) {
                        $isTransparent = true;
                        break;
                    }
                }
            }
            if ($icons[$iconId]["bit_count"] == 32) {
                imagealphablending($img, false);
                if (function_exists("imagesavealpha")) {
                    imagesavealpha($img, true);
                }
            }

            if ($isTransparent) {
                if (($icons[$iconId]["bit_count"] >= 24) || (imagecolorstotal($img) >= 256)) {
                    $img2 = imagecreatetruecolor(imagesx($img), imagesy($img));
                    imagecopy($img2, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));
                    imagedestroy($img);
                    $img = $img2;
                    imagetruecolortopalette($img, true, 255);
                }
                $Pruhledna = imagecolorallocate($img, 0, 0, 0);
                for ($y = 0; $y < $icons[$iconId]["height"]; $y++) {
                    for ($x = 0; $x < $icons[$iconId]["width"]; $x++) {
                        if ($icons[$iconId]["mask"][$x][$y] == 1) {
                            imagesetpixel($img, $x, $y, $Pruhledna);
                        }
                    }
                }
                imagecolortransparent($img, $Pruhledna);
            }

            return $img;
        }

        /**
         * Reads image from a icon in exe file
         * @param string $filename Target exefile
         * @param int $icoIndex Index of the icon in exefile
         * @param int $icoColorCount Icon color count (For multiple icons ico file) - 2,16,256, ICO_TRUE_COLOR, ICO_XP_COLOR or ICO_MAX_COLOR
         * @param int $icoSize Icon width (For multiple icons ico file) or ICO_MAX_SIZE
         * @param int $alphaBgR Background color R value for alpha-channel images (Default is White)
         * @param int $alphaBgG Background color G value for alpha-channel images (Default is White)
         * @param int $alphaBgB Background color B value for alpha-channel images (Default is White)
         * @return resource Image resource or false on error
         * FIXME! and make public
         */
        private function imageCreateFromExeIco($filename, $icoIndex, $icoColorCount = 16, $icoSize = 16, $alphaBgR = 255, $alphaBgG = 255, $alphaBgB = 255) {
            $ok = saveExeIcon($filename, "icotemp.dat", $icoIndex);
            if (!$ok):
                $im = false;
            else:
                $im = $this->imageCreateFromIco("icotemp.dat", $icoColorCount, $icoSize, $alphaBgR, $alphaBgG, $alphaBgB);
                unlink("icotemp.dat");
            endif;
            return $im;
        }

        /**
         * Saves icon(s) from the exe file
         * @global int $jpexs_StartOfRsrc Internal reserved variable
         * @global int $jpexs_ImageBase Internal reserved variable
         * @global int $jpexs_ResVirtualAddress Internal reserved variable
         * @param string $filename Target exefile
         * @param string $icoFileNameOrPath Filename to save ico or path (Default "") Path if you want more than 1 icon. If "", the filename is "$icoIndex.ico"
         * @param int|array $iconIndex Index(es) of the icon in exefile  (Default -1) If -1, all icons are saved, Can be an array of indexes.
         * @return boolean True on successful save
         * 
         * FIXME!
         */
        private function saveExeIcon($filename, $icoFileNameOrPath = "", $iconIndex = -1) /* -1 for all,or can be array */ {
            global $jpexs_f, $jpexs_StartOfRsrc, $jpexs_ImageBase, $jpexs_ResVirtualAddress;
            $jpexs_f = fopen($filename, "r");
            $MZ = fread($jpexs_f, 2);
            if ($MZ != "MZ")
                NotValidExe();
            fseek($jpexs_f, 60);
            $OffsetToNewHeader = $this->freaddword($jpexs_f);
            fseek($jpexs_f, $OffsetToNewHeader);
            $PE = fread($jpexs_f, 2);
            if ($PE != "PE")
                NotValidExe();
            fread($jpexs_f, 4);
            $NumberOfSections = $this->freadword($jpexs_f);
            fseek($jpexs_f, ftell($jpexs_f) + 12);
            $SizeOfOptionalHeader = $this->freadword($jpexs_f);
            $PosMagic = ftell($jpexs_f) + 2;
            fseek($jpexs_f, $PosMagic + $SizeOfOptionalHeader);

            for ($p = 0; $p < $NumberOfSections; $p++):
                $SectionName[$p] = trim(fread($jpexs_f, 8));
                $VirtualSize[$p] = $this->freaddword($jpexs_f);
                $VirtualAddress[$p] = $this->freaddword($jpexs_f);
                $PhysicalSize[$p] = $this->freaddword($jpexs_f);
                $PhysicalOffset[$p] = $this->freaddword($jpexs_f);
                fread($jpexs_f, 16);
                if ($SectionName[$p] == ".rsrc"):
                    $jpexs_ResVirtualAddress = $VirtualAddress[$p];
                    fseek($jpexs_f, $PhysicalOffset[$p]);
                    $jpexs_StartOfRsrc = $PhysicalOffset[$p];
                    jpexs_readResDirectoryEntry($R, $PhysicalOffset[$p]);
                    $IconCount = null;
                    $Ikona = null;
                    while (list ($key, $val) = each($R["Subdir"])):
                        if ($key == 14):
                            $r = 0;
                            while (list ($key2, $val2) = each($R["Subdir"][$key]["Subdir"])):
                                while (list ($key3, $val3) = each($R["Subdir"][$key]["Subdir"][$key2]["Subdir"])):
                                    fseek($jpexs_f, $val3["DataOffset"]);
                                    $Reserved = $this->freadword($jpexs_f);
                                    $Type = $this->freadword($jpexs_f);
                                    $ic = $this->freadword($jpexs_f);
                                    $IconCount[] = $ic;
                                    for ($s = 0; $s < $ic; $s++) {
                                        $Ikona[$r][$s]["Width"] = $this->freadbyte($jpexs_f);
                                        $Ikona[$r][$s]["Height"] = $this->freadbyte($jpexs_f);
                                        $Ikona[$r][$s]["ColorCount"] = $this->freadword($jpexs_f);
                                        $Ikona[$r][$s]["Planes"] = $this->freadword($jpexs_f);
                                        $Ikona[$r][$s]["BitCount"] = $this->freadword($jpexs_f);
                                        $Ikona[$r][$s]["BytesInRes"] = $this->freaddword($jpexs_f);
                                        $Ikona[$r][$s]["IconId"] = $this->freadword($jpexs_f);
                                    };
                                    fseek($jpexs_f, $val3["DataOffset"]);
                                    $r++;
                                endwhile;
                            endwhile;
                        endif;
                    endwhile;

                    reset($R["Subdir"]);

                    while (list ($key, $val) = each($R["Subdir"])):
                        if ($key == 3):
                            while (list ($key2, $val2) = each($R["Subdir"][$key]["Subdir"])):
                                for ($r = 0; $r < count($Ikona); $r++):
                                    for ($s = 0; $s < count($Ikona[$r]); $s++):
                                        while (list ($key3, $val3) = each($R["Subdir"][$key]["Subdir"][$Ikona[$r][$s]["IconId"]]["Subdir"])):
                                            if (($iconIndex == $r)or($iconIndex == -1)or((is_array($iconIndex))and(in_array($r, $iconIndex)))):
                                                fseek($jpexs_f, $val3["DataOffset"]);
                                                $Ikona[$r][$s]["Data"] = fread($jpexs_f, $val3["DataSize"]);
                                                $Ikona[$r][$s]["DataSize"] = $val3["DataSize"];
                                            endif;
                                        endwhile;
                                    endfor;
                                endfor;
                            endwhile;
                        endif;
                    endwhile;
                    $ok = false;
                    for ($r = 0; $r < count($Ikona); $r++):
                        if (($iconIndex == $r)or($iconIndex == -1)or((is_array($iconIndex))and(in_array($r, $iconIndex)))):
                            $savefile = $icoFileNameOrPath;
                            if ($icoFileNameOrPath == "") {
                                $savefile = "$r.ico";
                            } else {
                                if (($iconIndex == -1)or(is_array($iconIndex)))
                                    $savefile = $icoFileNameOrPath . "$r.ico";
                            };
                            $f2 = fopen($savefile, "w");
                            fwrite($f2, jpexs_inttoword(0));
                            fwrite($f2, jpexs_inttoword(1));
                            fwrite($f2, jpexs_inttoword(count($Ikona[$r])));
                            $Offset = 6 + 16 * count($Ikona[$r]);
                            for ($s = 0; $s < count($Ikona[$r]); $s++):
                                fwrite($f2, jpexs_inttobyte($Ikona[$r][$s]["Width"]));
                                fwrite($f2, jpexs_inttobyte($Ikona[$r][$s]["Height"]));
                                fwrite($f2, jpexs_inttoword($Ikona[$r][$s]["ColorCount"]));
                                fwrite($f2, jpexs_inttoword($Ikona[$r][$s]["Planes"]));
                                fwrite($f2, jpexs_inttoword($Ikona[$r][$s]["BitCount"]));
                                fwrite($f2, jpexs_inttodword($Ikona[$r][$s]["BytesInRes"]));
                                fwrite($f2, jpexs_inttodword($Offset));
                                $Offset += $Ikona[$r][$s]["DataSize"];
                            endfor;
                            for ($s = 0; $s < count($Ikona[$r]); $s++):
                                fwrite($f2, $Ikona[$r][$s]["Data"]);
                            endfor;
                            fclose($f2);
                            $ok = true;
                        endif;
                    endfor;
                    return $ok;
                endif;
            endfor;

            fclose($jpexs_f);
        }

        /**
         * Internal function for reading exe icons
         * FIXME!
         */
        private function readResDirectoryEntry(&$parentRes, $offset) {
            global $jpexs_f, $jpexs_StartOfRsrc, $jpexs_ImageBase, $jpexs_ResVirtualAddress;
            $lastPos = ftell($jpexs_f);
            $Res = null;
            fseek($jpexs_f, $offset);
            //IMAGE_RESOURCE_DIRECTORY
            $Characteristics = $this->freaddword($jpexs_f);
            $TimeDateStamp = $this->freaddword($jpexs_f);
            $MajorVersion = $this->freadword($jpexs_f);
            $MinorVersion = $this->freadword($jpexs_f);
            $NumberOfNamedEntries = $this->freadword($jpexs_f);
            $NumberOfIdEntries = $this->freadword($jpexs_f);
            for ($q = 0; $q < $NumberOfNamedEntries + $NumberOfIdEntries; $q++):
                //IMAGE_RESOURCE_DIRECTORY_ENTRY
                $ResName = $this->freaddword($jpexs_f);
                $lastPos2 = ftell($jpexs_f);
                if ($ResName >= 0x80000000):
                    //String Name
                    $ResNameOffset = $ResName - 0x80000000;
                    fseek($jpexs_f, $jpexs_StartOfRsrc + $ResNameOffset);
                    $StringLength = $this->freadword($jpexs_f);
                    $Identificator = (fread($jpexs_f, $StringLength * 2));
                    fseek($jpexs_f, $lastPos2);
                else:
                    //Integer Id
                    $Identificator = $ResName;
                endif;

                $ResOffsetToData = $this->freaddword($jpexs_f);
                if ($ResOffsetToData >= 0x80000000):
                    $SubResOffset = $ResOffsetToData - 0x80000000;
                    $this->readResDirectoryEntry($Res["$Identificator"], $jpexs_StartOfRsrc + $SubResOffset);
                else:
                    $RawDataOffset = $ResOffsetToData;
                    $lastPos2 = ftell($jpexs_f);
                    fseek($jpexs_f, $jpexs_StartOfRsrc + $RawDataOffset);
                    //IMAGE_RESOURCE_DATA_ENTRY
                    $OffsetToData = $this->freaddword($jpexs_f);
                    $Res["$Identificator"]["DataOffset"] = $jpexs_StartOfRsrc - $jpexs_ResVirtualAddress + $OffsetToData;
                    $Res["$Identificator"]["DataSize"] = $this->freaddword($jpexs_f);
                    $CodePage = $this->freaddword($jpexs_f);
                    $Reserved = $this->freaddword($jpexs_f);
                    fseek($jpexs_f, $lastPos2);
                endif;
            endfor;
            fseek($jpexs_f, $lastPos);
            $parentRes["Subdir"] = $Res;
        }

        /**
         * Creates ico file from image resource(s)
         * @param resource|resource[] $images Target Image resource (Can be array of image resources)
         * @param string $filename Target ico file to save icon to, If ommited or "", image is written to standard output - use header("Content-type: image/x-icon");
         * @return bool True on success, False on failure
         */
        public function imageIco($images, string $filename = ""): bool {

            if (!is_array($images)) {
                $images = [$images];
            }
            $imageCount = count($images);

            $writeToFile = false;

            if ($filename !== "") {
                $writeToFile = true;
            }

            $ret = "";

            $ret .= $this->inttoword(0); //PASSWORD
            $ret .= $this->inttoword(1); //SOURCE
            $ret .= $this->inttoword($imageCount); //ICONCOUNT

            $fullSize = 0;
            for ($q = 0; $q < $imageCount; $q++) {
                $img = $images[$q];

                $width = imagesx($img);
                $height = imagesy($img);

                $colorCount = imagecolorstotal($img);

                $transparent = imagecolortransparent($img);
                $isTransparent = $transparent != -1;

                if ($isTransparent) {
                    $colorCount--;
                }

                if ($colorCount == 0) {
                    $colorCount = 0;
                    $bitCount = 24;
                }
                if (($colorCount > 0)and($colorCount <= 2)) {
                    $colorCount = 2;
                    $bitCount = 1;
                }
                if (($colorCount > 2)and($colorCount <= 16)) {
                    $colorCount = 16;
                    $bitCount = 4;
                }
                if (($colorCount > 16)and($colorCount <= 256)) {
                    $colorCount = 0;
                    $bitCount = 8;
                }

                //ICONINFO:
                $ret .= $this->inttobyte($width);
                $ret .= $this->inttobyte($height);
                $ret .= $this->inttobyte($colorCount);
                $ret .= $this->inttobyte(0); //RESERVED

                $planes = 0;
                if ($bitCount >= 8) {
                    $planes = 1;
                }

                $ret .= $this->inttoword($planes);
                if ($bitCount >= 8) {
                    $WBitCount = $bitCount;
                }
                if ($bitCount == 4) {
                    $WBitCount = 0;
                }
                if ($bitCount == 1) {
                    $WBitCount = 0;
                }
                $ret .= $this->inttoword($WBitCount); //BITS

                $remainder = (4 - ($width / (8 / $bitCount)) % 4) % 4;
                $remainderMask = (4 - ($width / 8) % 4) % 4;

                $size = 40 + ($width / (8 / $bitCount) + $remainder) * $height + (($width / 8 + $remainderMask) * $height);
                if ($bitCount < 24) {
                    $size += pow(2, $bitCount) * 4;
                }
                $ret .= $this->inttodword($size); //SIZE
                $offset = 6 + 16 * $imageCount + $fullSize;
                $ret .= $this->inttodword($offset); //OFFSET
                $fullSize += $size;
            }

            for ($q = 0; $q < $imageCount; $q++) {
                $img = $images[$q];
                $width = imagesx($img);
                $height = imagesy($img);
                $colorCount = imagecolorstotal($img);

                $transparent = imagecolortransparent($img);
                $isTransparent = $transparent != -1;

                if ($isTransparent) {
                    $colorCount--;
                }
                if ($colorCount == 0) {
                    $colorCount = 0;
                    $bitCount = 24;
                }
                if (($colorCount > 0) && ($colorCount <= 2)) {
                    $colorCount = 2;
                    $bitCount = 1;
                }
                if (($colorCount > 2) && ($colorCount <= 16)) {
                    $colorCount = 16;
                    $bitCount = 4;
                }
                if (($colorCount > 16) && ($colorCount <= 256)) {
                    $colorCount = 0;
                    $bitCount = 8;
                }

                //ICONS
                $ret .= $this->inttodword(40); //HEADSIZE
                $ret .= $this->inttodword($width); //
                $ret .= $this->inttodword(2 * $height); //
                $ret .= $this->inttoword(1); //PLANES
                $ret .= $this->inttoword($bitCount);   //
                $ret .= $this->inttodword(0); //Compress method

                $remainderMask = ($width / 8) % 4;

                $remainder = ($width / (8 / $bitCount)) % 4;
                $size = ($width / (8 / $bitCount) + $remainder) * $height + (($width / 8 + $remainderMask) * $height);

                $ret .= $this->inttodword($size); //SIZE

                $ret .= $this->inttodword(0); //HPIXEL_M
                $ret .= $this->inttodword(0); //V_PIXEL_M
                $ret .= $this->inttodword($colorCount); //UCOLORS
                $ret .= $this->inttodword(0); //DCOLORS

                $cc = $colorCount;
                if ($cc == 0) {
                    $cc = 256;
                }

                if ($bitCount < 24) {
                    $colorTotal = imagecolorstotal($img);
                    if ($isTransparent) {
                        $colorTotal--;
                    }

                    for ($p = 0; $p < $colorTotal; $p++) {
                        $color = imagecolorsforindex($img, $p);
                        $ret .= $this->inttobyte($color["blue"]);
                        $ret .= $this->inttobyte($color["green"]);
                        $ret .= $this->inttobyte($color["red"]);
                        $ret .= $this->inttobyte(0); //RESERVED
                    }

                    for ($p = $colorTotal; $p < $cc; $p++) {
                        $ret .= $this->inttobyte(0);
                        $ret .= $this->inttobyte(0);
                        $ret .= $this->inttobyte(0);
                        $ret .= $this->inttobyte(0); //RESERVED
                    }
                }

                if ($bitCount <= 8) {
                    for ($y = $height - 1; $y >= 0; $y--) {
                        $bWrite = "";
                        for ($x = 0; $x < $width; $x++) {
                            $color = imagecolorat($img, $x, $y);
                            if ($color == $transparent) {
                                $color = imagecolorexact($img, 0, 0, 0);
                            }
                            if ($color == -1) {
                                $color = 0;
                            }
                            if ($color > pow(2, $bitCount) - 1) {
                                $color = 0;
                            }

                            $bWrite .= $this->decbinx($color, $bitCount);
                            if (strlen($bWrite) == 8) {
                                $ret .= $this->inttobyte(bindec($bWrite));
                                $bWrite = "";
                            }
                        }

                        if ((strlen($bWrite) < 8)and(strlen($bWrite) != 0)) {
                            $sl = strlen($bWrite);
                            for ($t = 0; $t < 8 - $sl; $t++) {
                                $sl .= "0";
                            }
                            $ret .= $this->inttobyte(bindec($bWrite));
                        }
                        for ($z = 0; $z < $remainder; $z++) {
                            $ret .= $this->inttobyte(0);
                        }
                    }
                }

                if ($bitCount >= 24) {
                    for ($y = $height - 1; $y >= 0; $y--) {
                        for ($x = 0; $x < $width; $x++) {
                            $color = imagecolorsforindex($img, imagecolorat($img, $x, $y));
                            $ret .= $this->inttobyte($color["blue"]);
                            $ret .= $this->inttobyte($color["green"]);
                            $ret .= $this->inttobyte($color["red"]);
                            if ($bitCount == 32) {
                                $ret .= $this->inttobyte(0); //Alpha for self:XP_COLORS
                            }
                        }
                        for ($z = 0; $z < $remainder; $z++) {
                            $ret .= $this->inttobyte(0);
                        }
                    }
                }

                //MASK
                for ($y = $height - 1; $y >= 0; $y--) {
                    $byteCount = 0;
                    $bOut = "";
                    for ($x = 0; $x < $width; $x++) {
                        if (($transparent != -1) && (imagecolorat($img, $x, $y) == $transparent)) {
                            $bOut .= "1";
                        } else {
                            $bOut .= "0";
                        }
                    }
                    for ($p = 0; $p < strlen($bOut); $p += 8) {
                        $byte = bindec(substr($bOut, $p, 8));
                        $byteCount++;
                        $ret .= $this->inttobyte($byte);
                    }
                    $remainder = $byteCount % 4;
                    for ($z = 0; $z < $remainder; $z++) {
                        $ret .= $this->inttobyte(0xff);
                    }
                }
            }





            if ($writeToFile) {
                $f = @fopen($filename, "w");
                if ($f === false) {
                    trigger_error("Cannot write icon to file \"$filename\"");
                    return false;
                }
                fwrite($f, $ret);
                fclose($f);
            } else {
                echo $ret;
            }
            return true;
        }

        private function readIcon(string $filename, int $id, array &$icons) {

            $f = fopen($filename, "rb");

            fseek($f, 6 + $id * 16);
            $width = $this->freadbyte($f);
            $height = $this->freadbyte($f);

            if ($width == 0) {
                $width = 256;
            }
            if ($height == 0) {
                $height = 256;
            }

            fseek($f, 6 + $id * 16 + 8);
            $dataLength = $this->freaddword($f);
            $offset = $this->freaddword($f);
            fseek($f, $offset);

            $p = $id;

            $icons[$p]["info"]["header_size"] = $this->freadlngint($f);
            if ($icons[$p]["info"]["header_size"] === 0x474E5089) {
                //it's a PNG file
                $icons[$p]["png_data"] = chr(0x89) . "PNG" . fread($f, $dataLength - 4);
                fclose($f);
                return;
            } else {
                $icons[$p]["info"]["image_width"] = $this->freadlngint($f);
                $icons[$p]["info"]["image_height"] = $this->freadlngint($f);
                $icons[$p]["info"]["number_of_image_planes"] = $this->freadword($f);
                $icons[$p]["info"]["bits_per_pixel"] = $this->freadword($f);
                $icons[$p]["info"]["compression_method"] = $this->freadlngint($f);
                $icons[$p]["info"]["size_of_bitmap"] = $this->freadlngint($f);
                $icons[$p]["info"]["horz_resolution"] = $this->freadlngint($f);
                $icons[$p]["info"]["vert_resolution"] = $this->freadlngint($f);
                $icons[$p]["info"]["num_color_used"] = $this->freadlngint($f);
                $icons[$p]["info"]["num_significant_colors"] = $this->freadlngint($f);
            }

            $biBitCount = $icons[$p]["info"]["bits_per_pixel"];

            if ($icons[$p]["info"]["bits_per_pixel"] <= 8) {

                $numColors = pow(2, $biBitCount);

                for ($b = 0; $b < $numColors; $b++) {
                    $icons[$p]["palette"][$b]["b"] = $this->freadbyte($f);
                    $icons[$p]["palette"][$b]["g"] = $this->freadbyte($f);
                    $icons[$p]["palette"][$b]["r"] = $this->freadbyte($f);
                    $this->freadbyte($f);
                }

                $remainder = (4 - ceil(($width / (8 / $biBitCount))) % 4) % 4;

                for ($y = $height - 1; $y >= 0; $y--) {
                    $this->currentBit = 0;
                    for ($x = 0; $x < $width; $x++) {
                        $c = $this->freadbits($f, $biBitCount);
                        $icons[$p]["data"][$x][$y] = $c;
                    }

                    if ($this->currentBit != 0) {
                        $this->freadbyte($f);
                    }
                    for ($g = 0; $g < $remainder; $g++) {
                        $this->freadbyte($f);
                    }
                }
            } elseif ($biBitCount == 24) {
                $remainder = $width % 4;

                for ($y = $height - 1; $y >= 0; $y--) {
                    for ($x = 0; $x < $width; $x++) {
                        $b = $this->freadbyte($f);
                        $g = $this->freadbyte($f);
                        $r = $this->freadbyte($f);
                        $icons[$p]["data"][$x][$y]["r"] = $r;
                        $icons[$p]["data"][$x][$y]["g"] = $g;
                        $icons[$p]["data"][$x][$y]["b"] = $b;
                    }
                    for ($z = 0; $z < $remainder; $z++) {
                        $this->freadbyte($f);
                    }
                }
            } elseif ($biBitCount == 32) {
                $remainder = $width % 4;

                for ($y = $height - 1; $y >= 0; $y--) {
                    for ($x = 0; $x < $width; $x++) {
                        $b = $this->freadbyte($f);
                        $g = $this->freadbyte($f);
                        $r = $this->freadbyte($f);
                        $alpha = $this->freadbyte($f);
                        $icons[$p]["data"][$x][$y]["r"] = $r;
                        $icons[$p]["data"][$x][$y]["g"] = $g;
                        $icons[$p]["data"][$x][$y]["b"] = $b;
                        $icons[$p]["data"][$x][$y]["alpha"] = $alpha;
                    }
                    for ($z = 0; $z < $remainder; $z++) {
                        $this->freadbyte($f);
                    }
                }
            }

            //Mask
            $remainder = (4 - ceil(($width / (8))) % 4) % 4;
            for ($y = $height - 1; $y >= 0; $y--) {
                $this->currentBit = 0;
                for ($x = 0; $x < $width; $x++) {
                    $c = $this->freadbits($f, 1);
                    $icons[$p]["mask"][$x][$y] = $c;
                }
                if ($this->currentBit != 0) {
                    $this->freadbyte($f);
                }
                for ($g = 0; $g < $remainder; $g++) {
                    $this->freadbyte($f);
                }
            }

            fclose($f);
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

        private function freadbits($f, int $count) {
            $Byte = $this->freadbyte($f);
            $LastCBit = $this->currentBit;
            $this->currentBit += $count;
            if ($this->currentBit == 8) {
                $this->currentBit = 0;
            } else {
                fseek($f, ftell($f) - 1);
            }
            return $this->retBits($Byte, $LastCBit, $count);
        }

        private function freadbyte($f): int {
            return ord(fread($f, 1));
        }

        private function freadword($f): int {
            $b1 = $this->freadbyte($f);
            $b2 = $this->freadbyte($f);
            return $b2 * 256 + $b1;
        }

        private function freadlngint($f): int {
            return $this->freaddword($f);
        }

        private function freaddword($f): int {
            $b1 = $this->freadword($f);
            $b2 = $this->freadword($f);
            return $b2 * 65536 + $b1;
        }

        private function inttobyte(int $n): string {
            return chr($n);
        }

        private function inttodword(int $n): string {
            return chr($n & 255) . chr(($n >> 8) & 255) . chr(($n >> 16) & 255) . chr(($n >> 24) & 255);
        }

        private function inttoword(int $n): string {
            return chr($n & 255) . chr(($n >> 8) & 255);
        }

        public function getIconsInfo(string $filename): array {
            $f = fopen($filename, "rb");

            $this->freadword($f); //reserved
            $this->freadword($f); //type
            $count = $this->freadword($f);
            $icons = [];
            for ($p = 0; $p < $count; $p++) {
                $icon = [];
                $icon["width"] = $this->freadbyte($f);
                $icon["height"] = $this->freadbyte($f);
                if ($icon["width"] == 0) {
                    $icon["width"] = 256;
                }
                if ($icon["height"] == 0) {
                    $icon["height"] = 256;
                }
                $this->freadword($f); //color_count
                $this->freadword($f); //planes
                $icon["bit_count"] = $this->freadword($f);
                $this->freaddword($f); //bytes_in_res
                $icon["image_offset"] = $this->freaddword($f);
                $icons[] = $icon;
            }

            if (!feof($f)) {
                for ($p = 0; $p < $count; $p++) {
                    fseek($f, $icons[$p]["image_offset"] + 14);
                    $icons[$p]["info"]["bits_per_pixel"] = $this->freadword($f);
                    unset($icons[$p]["image_offset"]);
                }
            }
            fclose($f);
            return $icons;
        }

    }

}

namespace {

    use Com\Jpexs\Image\Icon;

if (!function_exists("imagecreatefromico")) { //hope nobody uses same name

        /**
         * Reads image from a ICO file
         *
         * @param string $filename Target ico file to load
         * @param int $numColorBits Icon color bits - color count (see Icon::COLORS_* constants) (For multiple icons ico file) or Icon::MAX_COLOR
         * @param int $icoSize Icon width (For multiple icons ico file) or Icon::MAX_SIZE
         * @param int $alphaBgR Background color R value for alpha-channel images (Default is White)
         * @param int $alphaBgG Background color G value for alpha-channel images (Default is White)
         * @param int $alphaBgB Background color B value for alpha-channel images (Default is White)
         * @return resource Image resource
         */

        function imageCreateFromIco(string $filename, int $numColorBits = 16, int $icoSize = 16, int $alphaBgR = 255, int $alphaBgG = 255, int $alphaBgB = 255) {
            $iconLib = new Icon();
            return $iconLib->imageCreateFromIco($filename, $numColorBits, $icoSize, $alphaBgR, $alphaBgG, $alphaBgB);
        }

    }

    if (!function_exists("imageico")) {

        /**
         * Creates ico file from image resource(s)
         * @param resource|resource[] $images Target Image resource (Can be array of image resources)
         * @param string $filename Target ico file to save icon to, If ommited or "", image is written to standard output - use header("Content-type: image/x-icon");
         * @return bool True on success, False on failure
         */
        function imageIco($images, string $filename = ""): bool {
            $iconLib = new Icon();
            return $iconLib->imageIco($images, $filename);
        }

    }
}