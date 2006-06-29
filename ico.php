<?php
/*
*------------------------------------------------------------
*                   ICO Image functions v2.0
*------------------------------------------------------------
*                      By JPEXS
*
*           Function list:
*              ImageCreateFromIco - Reads image from a ICO file
*              ImageCreateFromExeIco - Reads image from icon in EXE file
*              SaveExeIcon - Saves icon from the exe file
*              ImageIco - Saves an image to icofile or writes it to output
*/

define("TRUE_COLOR", 0x1000000);
define("XP_COLOR", 4294967296);
define("MAX_COLOR", -2);
define("MAX_SIZE", -2);

/*
        Version changes:
                v2.0 - For icons with Alpha channel now you can set background color
                     - ImageCreateFromExeIco added
                     - Fixed MAX_SIZE and MAX_COLOR values
*/

/*
*------------------------------------------------------------
*                    ImageCreateFromIco
*------------------------------------------------------------
*            - Reads image from a ICO file
*
*         Parameters:  $filename - Target ico file to load
*                 $icoColorCount - Icon color count (For multiple icons ico file)
*                                - 2,16,256, TRUE_COLOR or XP_COLOR
*                       $icoSize - Icon width       (For multiple icons ico file)
*  $AlphaBgR,$AlphaBgG,$AlphaBgB - Background color for alpha-channel images (Default is White)
*            Returns: Image ID
*/


function ImageCreateFromIco($filename,$icoColorCount=16,$icoSize=16,$AlphaBgR=255,$AlphaBgG=255,$AlphaBgB=255)
{
$Ikona=GetIconsInfo($filename);

$IconID=-1;

$ColMax=-1;
$SizeMax=-1;

for($p=0;$p<count($Ikona);$p++)
{
$Ikona[$p]["NumberOfColors"]=pow(2,$Ikona[$p]["Info"]["BitsPerPixel"]);
};


for($p=0;$p<count($Ikona);$p++)
{

if(($ColMax==-1)or($Ikona[$p]["NumberOfColors"]>=$Ikona[$ColMax]["NumberOfColors"]))
if(($icoSize==$Ikona[$p]["Width"])or($icoSize==MAX_SIZE))
 {
  $ColMax=$p;
 };

if(($SizeMax==-1)or($Ikona[$p]["Width"]>=$Ikona[$SizeMax]["Width"]))
if(($icoColorCount==$Ikona[$p]["NumberOfColors"])or($icoColorCount==MAX_COLOR))
 {
   $SizeMax=$p;
 };


if($Ikona[$p]["NumberOfColors"]==$icoColorCount)
if($Ikona[$p]["Width"]==$icoSize)
 {

 $IconID=$p;
 };
};

  if($icoColorCount==MAX_COLOR) $IconID=$ColMax;
  if($icoSize==MAX_SIZE) $IconID=$SizeMax;

$ColName=$icoColorCount;

if($icoSize==MAX_SIZE) $icoSize="Max";
if($ColName==TRUE_COLOR) $ColName="True";
if($ColName==XP_COLOR) $ColName="XP";
if($ColName==MAX_COLOR) $ColName="Max";
if($IconID==-1) die("Icon with $ColName colors and $icoSize x $icoSize size doesn't exist in this file!");


ReadIcon($filename,$IconID,$Ikona);

 $biBitCount=$Ikona[$IconID]["Info"]["BitsPerPixel"];


  if($Ikona[$IconID]["Info"]["BitsPerPixel"]==0)
  {
  $Ikona[$IconID]["Info"]["BitsPerPixel"]=24;
  };

 $biBitCount=$Ikona[$IconID]["Info"]["BitsPerPixel"];
 if($biBitCount==0) $biBitCount=1;


$Ikona[$IconID]["BitCount"]=$Ikona[$IconID]["Info"]["BitsPerPixel"];



if($Ikona[$IconID]["BitCount"]>=24)
{
$img=imagecreatetruecolor($Ikona[$IconID]["Width"],$Ikona[$IconID]["Height"]);
if($Ikona[$IconID]["BitCount"]==32):
  $backcolor=imagecolorallocate($img,$AlphaBgR,$AlphaBgG,$AlphaBgB);
  imagefilledrectangle($img,0,0,$Ikona[$IconID]["Width"]-1,$Ikona[$IconID]["Height"]-1,$backcolor);
endif;
for($y=0;$y<$Ikona[$IconID]["Height"];$y++)
for($x=0;$x<$Ikona[$IconID]["Width"];$x++)
 {
 $R=$Ikona[$IconID]["Data"][$x][$y]["r"];
 $G=$Ikona[$IconID]["Data"][$x][$y]["g"];
 $B=$Ikona[$IconID]["Data"][$x][$y]["b"];
 if($Ikona[$IconID]["BitCount"]==32)
 {
 $Alpha=127-round($Ikona[$IconID]["Data"][$x][$y]["alpha"]*127/255);
 if($Ikona[$IconID]["Maska"][$x][$y]==1) $Alpha=127;
 $color=imagecolorexactalpha($img,$R,$G,$B,$Alpha);
 if($color==-1) $color=imagecolorallocatealpha($img,$R,$G,$B,$Alpha);
 }
 else
 {
 $color=imagecolorexact($img,$R,$G,$B);
 if($color==-1) $color=imagecolorallocate($img,$R,$G,$B);
 };

 imagesetpixel($img,$x,$y,$color);

 };

}
else
{
$img=imagecreate($Ikona[$IconID]["Width"],$Ikona[$IconID]["Height"]);
for($p=0;$p<count($Ikona[$IconID]["Paleta"]);$p++)
 $Paleta[$p]=imagecolorallocate($img,$Ikona[$IconID]["Paleta"][$p]["r"],$Ikona[$IconID]["Paleta"][$p]["g"],$Ikona[$IconID]["Paleta"][$p]["b"]);

for($y=0;$y<$Ikona[$IconID]["Height"];$y++)
for($x=0;$x<$Ikona[$IconID]["Width"];$x++)
 {
 imagesetpixel($img,$x,$y,$Paleta[$Ikona[$IconID]["Data"][$x][$y]]);
 };
};
$IsTransparent=false;  
for($y=0;$y<$Ikona[$IconID]["Height"];$y++)
for($x=0;$x<$Ikona[$IconID]["Width"];$x++)
 if($Ikona[$IconID]["Maska"][$x][$y]==1)
  {
   $IsTransparent=true;
   break;
  };
if($Ikona[$IconID]["BitCount"]==32)
{
 imagealphablending($img, false);
 if(function_exists("imagesavealpha"))
  imagesavealpha($img,true);
};

if($IsTransparent)
 {
  if(($Ikona[$IconID]["BitCount"]>=24)or(imagecolorstotal($img)>=256))
   {
   $img2=imagecreatetruecolor(imagesx($img),imagesy($img));
   imagecopy($img2,$img,0,0,0,0,imagesx($img),imagesy($img));
   imagedestroy($img);
   $img=$img2;
   imagetruecolortopalette($img,true,255);

   };
    $Pruhledna=imagecolorallocate($img,0,0,0);
    for($y=0;$y<$Ikona[$IconID]["Height"];$y++)
     for($x=0;$x<$Ikona[$IconID]["Width"];$x++)
      if($Ikona[$IconID]["Maska"][$x][$y]==1)
       {
        imagesetpixel($img,$x,$y,$Pruhledna);
       };
  imagecolortransparent($img,$Pruhledna);
 };

return $img;


};




function ReadIcon($filename,$id,&$Ikona)
{
global $CurrentBit;

$f=fopen($filename,"rb");

fseek($f,6+$id*16);
  $Width=freadbyte($f);
  $Height=freadbyte($f);
fseek($f,6+$id*16+12);
$OffSet=freaddword($f);
fseek($f,$OffSet);

$p=$id;

  $Ikona[$p]["Info"]["HeaderSize"]=freadlngint($f);
  $Ikona[$p]["Info"]["ImageWidth"]=freadlngint($f);
  $Ikona[$p]["Info"]["ImageHeight"]=freadlngint($f);
  $Ikona[$p]["Info"]["NumberOfImagePlanes"]=freadword($f);
  $Ikona[$p]["Info"]["BitsPerPixel"]=freadword($f);
  $Ikona[$p]["Info"]["CompressionMethod"]=freadlngint($f);
  $Ikona[$p]["Info"]["SizeOfBitmap"]=freadlngint($f);
  $Ikona[$p]["Info"]["HorzResolution"]=freadlngint($f);
  $Ikona[$p]["Info"]["VertResolution"]=freadlngint($f);
  $Ikona[$p]["Info"]["NumColorUsed"]=freadlngint($f);
  $Ikona[$p]["Info"]["NumSignificantColors"]=freadlngint($f);


 $biBitCount=$Ikona[$p]["Info"]["BitsPerPixel"];

 if($Ikona[$p]["Info"]["BitsPerPixel"]<=8)
  {

 $barev=pow(2,$biBitCount);

  for($b=0;$b<$barev;$b++)
    {
    $Ikona[$p]["Paleta"][$b]["b"]=freadbyte($f);
    $Ikona[$p]["Paleta"][$b]["g"]=freadbyte($f);
    $Ikona[$p]["Paleta"][$b]["r"]=freadbyte($f);
    freadbyte($f);
    };

$Zbytek=(4-ceil(($Width/(8/$biBitCount)))%4)%4;


for($y=$Height-1;$y>=0;$y--)
    {
     $CurrentBit=0;
     for($x=0;$x<$Width;$x++)
      {
         $C=freadbits($f,$biBitCount);
         $Ikona[$p]["Data"][$x][$y]=$C;
      };

    if($CurrentBit!=0) {freadbyte($f);};
    for($g=0;$g<$Zbytek;$g++)
     freadbyte($f);
     };

}
elseif($biBitCount==24)
{
 $Zbytek=$Width%4;

   for($y=$Height-1;$y>=0;$y--)
    {
     for($x=0;$x<$Width;$x++)
      {
       $B=freadbyte($f);
       $G=freadbyte($f);
       $R=freadbyte($f);
       $Ikona[$p]["Data"][$x][$y]["r"]=$R;
       $Ikona[$p]["Data"][$x][$y]["g"]=$G;
       $Ikona[$p]["Data"][$x][$y]["b"]=$B;
      }
    for($z=0;$z<$Zbytek;$z++)
     freadbyte($f);
   };
}
elseif($biBitCount==32)
{
 $Zbytek=$Width%4;

   for($y=$Height-1;$y>=0;$y--)
    {
     for($x=0;$x<$Width;$x++)
      {
       $B=freadbyte($f);
       $G=freadbyte($f);
       $R=freadbyte($f);
       $Alpha=freadbyte($f);
       $Ikona[$p]["Data"][$x][$y]["r"]=$R;
       $Ikona[$p]["Data"][$x][$y]["g"]=$G;
       $Ikona[$p]["Data"][$x][$y]["b"]=$B;
       $Ikona[$p]["Data"][$x][$y]["alpha"]=$Alpha;
      }
    for($z=0;$z<$Zbytek;$z++)
     freadbyte($f);
   };
};


//Maska
$Zbytek=(4-ceil(($Width/(8)))%4)%4;
for($y=$Height-1;$y>=0;$y--)
    {
     $CurrentBit=0;
     for($x=0;$x<$Width;$x++)
      {
         $C=freadbits($f,1);
         $Ikona[$p]["Maska"][$x][$y]=$C;
      };
    if($CurrentBit!=0) {freadbyte($f);};
    for($g=0;$g<$Zbytek;$g++)
     freadbyte($f);
     };
//--------------

fclose($f);

};

function GetIconsInfo($filename)
{
global $CurrentBit;

$f=fopen($filename,"rb");

$Reserved=freadword($f);
$Type=freadword($f);
$Count=freadword($f);
for($p=0;$p<$Count;$p++)
 {
  $Ikona[$p]["Width"]=freadbyte($f);
  $Ikona[$p]["Height"]=freadbyte($f);
  $Ikona[$p]["ColorCount"]=freadword($f);
 if($Ikona[$p]["ColorCount"]==0) $Ikona[$p]["ColorCount"]=256;
  $Ikona[$p]["Planes"]=freadword($f);
  $Ikona[$p]["BitCount"]=freadword($f);
  $Ikona[$p]["BytesInRes"]=freaddword($f);
  $Ikona[$p]["ImageOffset"]=freaddword($f);
 };

if(!feof($f)):
  for($p=0;$p<$Count;$p++)
   {
    fseek($f,$Ikona[$p]["ImageOffset"]+14);
    $Ikona[$p]["Info"]["BitsPerPixel"]=freadword($f);
   };
endif;
fclose($f);
return $Ikona;
};




/*
*------------------------------------------------------------
*                    ImageCreateFromExeIco
*------------------------------------------------------------
*            - Reads image from a icon in exe file
*
*         Parameters:  $filename - Target exefile
*                      $icoIndex - Index of the icon in exefile
*                 $icoColorCount - Icon color count (For multiple icons ico file)
*                                - 2,16,256, TRUE_COLOR or XP_COLOR
*                       $icoSize - Icon width       (For multiple icons ico file)
*  $AlphaBgR,$AlphaBgG,$AlphaBgB - Background color for alpha-channel images (Default is White)
*            Returns: Image ID or empty string if failed
*/
function ImageCreateFromExeIco($filename,$icoIndex,$icoColorCount=16,$icoSize=16,$AlphaBgR=255,$AlphaBgG=255,$AlphaBgB=255)
{
 $ok=SaveExeIcon($filename,"icotemp.dat",$icoIndex);
 if(!$ok):
  $im="";
 else:
   $im=ImageCreateFromIco("icotemp.dat",$icoColorCount,$icoSize,$AlphaBgR,$AlphaBgG,$AlphaBgB);
   unlink("icotemp.dat");
 endif;
 return $im;
};


/*
*------------------------------------------------------------
*                    SaveExeIcon
*------------------------------------------------------------
*            - Saves icon from the exe file
*
*         Parameters:  $filename - Target exefile
*             $icoFileNameOrPath - Filename to save ico or path (Default "")
*                                  (path if you want more than 1 icon)
*                                  (If "", the filename is "$icoIndex.ico")
*
*                      $icoIndex - Index(es) of the icon in exefile  (Default -1)
*                                  (If -1, all icons are saved)
*                                  (Can be an array of indexes!)
*/
function SaveExeIcon($filename,$icoFileNameOrPath="",$iconIndex=-1/*-1 for all,or can be array*/)
{
  global $f,$StartOfRsrc,$ImageBase,$ResVirtualAddress;
  $f=fopen($filename,"r");
  $MZ=fread($f,2);
  if($MZ!="MZ") NotValidExe();
  fseek($f,60);
  $OffsetToNewHeader=freaddword($f);
  fseek($f,$OffsetToNewHeader);
  $PE=fread($f,2);
  if($PE!="PE") NotValidExe();
  fread($f,4);
  $NumberOfSections=freadword($f);
  fseek($f,ftell($f)+12);
  $SizeOfOptionalHeader=freadword($f);
  $PosMagic=ftell($f)+2;
  fseek($f,$PosMagic+$SizeOfOptionalHeader);

  for($p=0;$p<$NumberOfSections;$p++):
    $SectionName[$p]=trim(fread($f,8));
    $VirtualSize[$p]=freaddword($f);
    $VirtualAddress[$p]=freaddword($f);
    $PhysicalSize[$p]=freaddword($f);
    $PhysicalOffset[$p]=freaddword($f);
    fread($f,16);
    if($SectionName[$p]==".rsrc"):
      $ResVirtualAddress=$VirtualAddress[$p];
      fseek($f,$PhysicalOffset[$p]);
      $StartOfRsrc=$PhysicalOffset[$p];
      ReadResDirectoryEntry($R,$PhysicalOffset[$p]);
      $IconCount=null;
      $Ikona=null;
      while (list ($key, $val) = each ($R["Subdir"])):
        if($key==14):
          $r=0;
          while (list ($key2, $val2) = each ($R["Subdir"][$key]["Subdir"])):
             while (list ($key3, $val3) = each ($R["Subdir"][$key]["Subdir"][$key2]["Subdir"])):
               fseek($f,$val3["DataOffset"]);
               $Reserved=freadword($f);
               $Type=freadword($f);
               $ic=freadword($f);
               $IconCount[]=$ic;
               for($s=0;$s<$ic;$s++)
                {
                 $Ikona[$r][$s]["Width"]=freadbyte($f);
                 $Ikona[$r][$s]["Height"]=freadbyte($f);
                 $Ikona[$r][$s]["ColorCount"]=freadword($f);
                 $Ikona[$r][$s]["Planes"]=freadword($f);
                 $Ikona[$r][$s]["BitCount"]=freadword($f);
                 $Ikona[$r][$s]["BytesInRes"]=freaddword($f);
                 $Ikona[$r][$s]["IconId"]=freadword($f);
                };
               fseek($f,$val3["DataOffset"]);
               $r++;
             endwhile;
          endwhile;
        endif;
      endwhile;

      reset ($R["Subdir"]);

      while (list ($key, $val) = each ($R["Subdir"])):
        if($key==3):
          while (list ($key2, $val2) = each ($R["Subdir"][$key]["Subdir"])):
          for($r=0;$r<count($Ikona);$r++):
           for($s=0;$s<count($Ikona[$r]);$s++):
             while (list ($key3, $val3) = each ($R["Subdir"][$key]["Subdir"][$Ikona[$r][$s]["IconId"]]["Subdir"])):
               if(($iconIndex==$r)or($iconIndex==-1)or((is_array($iconIndex))and(in_array($r,$iconIndex)))):
                 fseek($f,$val3["DataOffset"]);
                 $Ikona[$r][$s]["Data"]=fread($f,$val3["DataSize"]);
                 $Ikona[$r][$s]["DataSize"]=$val3["DataSize"];
               endif;
             endwhile;
           endfor;
           endfor;
          endwhile;
        endif;
      endwhile;
      $ok=false;
      for($r=0;$r<count($Ikona);$r++):
        if(($iconIndex==$r)or($iconIndex==-1)or((is_array($iconIndex))and(in_array($r,$iconIndex)))):
          $savefile=$icoFileNameOrPath;
          if($icoFileNameOrPath=="")
           {
             $savefile="$r.ico";
           }
           else
           {
            if(($iconIndex==-1)or(is_array($iconIndex)))
              $savefile=$icoFileNameOrPath."$r.ico";
           };
          $f2=fopen($savefile,"w");
          fwrite($f2,inttoword(0));
          fwrite($f2,inttoword(1));
          fwrite($f2,inttoword(count($Ikona[$r])));
          $Offset=6+16*count($Ikona[$r]);
          for($s=0;$s<count($Ikona[$r]);$s++):
            fwrite($f2,inttobyte($Ikona[$r][$s]["Width"]));
            fwrite($f2,inttobyte($Ikona[$r][$s]["Height"]));
            fwrite($f2,inttoword($Ikona[$r][$s]["ColorCount"]));
            fwrite($f2,inttoword($Ikona[$r][$s]["Planes"]));
            fwrite($f2,inttoword($Ikona[$r][$s]["BitCount"]));
            fwrite($f2,inttodword($Ikona[$r][$s]["BytesInRes"]));
            fwrite($f2,inttodword($Offset));
            $Offset+=$Ikona[$r][$s]["DataSize"];
          endfor;
          for($s=0;$s<count($Ikona[$r]);$s++):
            fwrite($f2,$Ikona[$r][$s]["Data"]);
          endfor;
          fclose($f2);
          $ok=true;
        endif;
      endfor;
      return $ok;
    endif;
  endfor;

  fclose($f);
};

function ReadResDirectoryEntry(&$parentRes,$offset)
{
global $f,$StartOfRsrc,$ImageBase,$ResVirtualAddress;
$lastPos=ftell($f);
$Res=null;
fseek($f,$offset);
//IMAGE_RESOURCE_DIRECTORY
      $Characteristics=freaddword($f);
      $TimeDateStamp=freaddword($f);
      $MajorVersion=freadword($f);
      $MinorVersion=freadword($f);
      $NumberOfNamedEntries=freadword($f);
      $NumberOfIdEntries=freadword($f);
      for($q=0;$q<$NumberOfNamedEntries+$NumberOfIdEntries;$q++):
        //IMAGE_RESOURCE_DIRECTORY_ENTRY
        $ResName=freaddword($f);
        $lastPos2=ftell($f);
        if($ResName>=0x80000000):
          //String Name
          $ResNameOffset=$ResName-0x80000000;
          fseek($f,$StartOfRsrc+$ResNameOffset);
          $StringLength=freadword($f);
          $Identificator=(fread($f,$StringLength*2));
          fseek($f,$lastPos2);
        else:
          //Integer Id
          $Identificator=$ResName;
        endif;

        $ResOffsetToData=freaddword($f);
        if($ResOffsetToData>=0x80000000):
          $SubResOffset=$ResOffsetToData-0x80000000;
          ReadResDirectoryEntry($Res["$Identificator"],$StartOfRsrc+$SubResOffset);
        else:
          $RawDataOffset=$ResOffsetToData;
          $lastPos2=ftell($f);
          fseek($f,$StartOfRsrc+$RawDataOffset);
          //IMAGE_RESOURCE_DATA_ENTRY
          $OffsetToData=freaddword($f);
          $Res["$Identificator"]["DataOffset"]=$StartOfRsrc-$ResVirtualAddress+$OffsetToData;
          $Res["$Identificator"]["DataSize"]=freaddword($f);
          $CodePage=freaddword($f);
          $Reserved=freaddword($f);
          fseek($f,$lastPos2);
        endif;
      endfor;
fseek($f,$lastPos);
$parentRes["Subdir"]=$Res;
};
//------------------------

/*
*------------------------------------------------------------
*                       ImageIco
*------------------------------------------------------------
*                 - Returns ICO file
*
*         Parameters:       $img - Target Image (Can be array of images)
*                      $filename - Target ico file to save
*
*
* Note: For returning icons to Browser, you have to set header:
*
*             header("Content-type: image/x-icon");
*
*/


function ImageIco($Images/*image or image array*/,$filename="")
{

if(is_array($Images))
{
$ImageCount=count($Images);
$Image=$Images;
}
else
{
$Image[0]=$Images;
$ImageCount=1;
};


$WriteToFile=false;

if($filename!="")
{
$WriteToFile=true;
};


$ret="";

$ret.=inttoword(0); //PASSWORD
$ret.=inttoword(1); //SOURCE
$ret.=inttoword($ImageCount); //ICONCOUNT


for($q=0;$q<$ImageCount;$q++)
{
$img=$Image[$q];

$Width=imagesx($img);
$Height=imagesy($img);

$ColorCount=imagecolorstotal($img);

$Transparent=imagecolortransparent($img);
$IsTransparent=$Transparent!=-1;


if($IsTransparent) $ColorCount--;

if($ColorCount==0) {$ColorCount=0; $BitCount=24;};
if(($ColorCount>0)and($ColorCount<=2)) {$ColorCount=2; $BitCount=1;};
if(($ColorCount>2)and($ColorCount<=16)) { $ColorCount=16; $BitCount=4;};
if(($ColorCount>16)and($ColorCount<=256)) { $ColorCount=0; $BitCount=8;};





//ICONINFO:
$ret.=inttobyte($Width);//
$ret.=inttobyte($Height);//
$ret.=inttobyte($ColorCount);//
$ret.=inttobyte(0);//RESERVED

$Planes=0;
if($BitCount>=8) $Planes=1;

$ret.=inttoword($f,$Planes);//PLANES
if($BitCount>=8) $WBitCount=$BitCount;
if($BitCount==4) $WBitCount=0;
if($BitCount==1) $WBitCount=0;
$ret.=inttoword($WBitCount);//BITS

$Zbytek=(4-($Width/(8/$BitCount))%4)%4;
$ZbytekMask=(4-($Width/8)%4)%4;

$PalSize=0;

$Size=40+($Width/(8/$BitCount)+$Zbytek)*$Height+(($Width/8+$ZbytekMask) * $Height);
if($BitCount<24)
 $Size+=pow(2,$BitCount)*4;
$IconId=1;
$ret.=inttodword($Size); //SIZE
$OffSet=6+16*$ImageCount+$FullSize;
$ret.=inttodword(6+16*$ImageCount+$FullSize);//OFFSET
$FullSize+=$Size;
//-------------

};


for($q=0;$q<$ImageCount;$q++)
{
$img=$Image[$q];
$Width=imagesx($img);
$Height=imagesy($img);
$ColorCount=imagecolorstotal($img);

$Transparent=imagecolortransparent($img);
$IsTransparent=$Transparent!=-1;

if($IsTransparent) $ColorCount--;
if($ColorCount==0) {$ColorCount=0; $BitCount=24;};
if(($ColorCount>0)and($ColorCount<=2)) {$ColorCount=2; $BitCount=1;};
if(($ColorCount>2)and($ColorCount<=16)) { $ColorCount=16; $BitCount=4;};
if(($ColorCount>16)and($ColorCount<=256)) { $ColorCount=0; $BitCount=8;};



//ICONS
$ret.=inttodword(40);//HEADSIZE
$ret.=inttodword($Width);//
$ret.=inttodword(2*$Height);//
$ret.=inttoword(1); //PLANES
$ret.=inttoword($BitCount);   //
$ret.=inttodword(0);//Compress method


$ZbytekMask=($Width/8)%4;

$Zbytek=($Width/(8/$BitCount))%4;
$Size=($Width/(8/$BitCount)+$Zbytek)*$Height+(($Width/8+$ZbytekMask) * $Height);

$ret.=inttodword($Size);//SIZE

$ret.=inttodword(0);//HPIXEL_M
$ret.=inttodword(0);//V_PIXEL_M
$ret.=inttodword($ColorCount); //UCOLORS
$ret.=inttodword(0); //DCOLORS
//---------------


$CC=$ColorCount;
if($CC==0) $CC=256;

if($BitCount<24)
{
 $ColorTotal=imagecolorstotal($img);
 if($IsTransparent) $ColorTotal--;

 for($p=0;$p<$ColorTotal;$p++)
  {
   $color=imagecolorsforindex($img,$p);
   $ret.=inttobyte($color["blue"]);
   $ret.=inttobyte($color["green"]);
   $ret.=inttobyte($color["red"]);
   $ret.=inttobyte(0); //RESERVED
  };

 $CT=$ColorTotal;
 for($p=$ColorTotal;$p<$CC;$p++)
  {
   $ret.=inttobyte(0);
   $ret.=inttobyte(0);
   $ret.=inttobyte(0);
   $ret.=inttobyte(0); //RESERVED
  };
};






if($BitCount<=8)
{

 for($y=$Height-1;$y>=0;$y--)
 {
  $bWrite="";
  for($x=0;$x<$Width;$x++)
   {
   $color=imagecolorat($img,$x,$y);
   if($color==$Transparent)
    $color=imagecolorexact($img,0,0,0);
   if($color==-1) $color=0;
   if($color>pow(2,$BitCount)-1) $color=0;

   $bWrite.=decbinx($color,$BitCount);
   if(strlen($bWrite)==8)
    {
     $ret.=inttobyte(bindec($bWrite));
     $bWrite="";
    };
   };

  if((strlen($bWrite)<8)and(strlen($bWrite)!=0))
    {
     $sl=strlen($bWrite);
     for($t=0;$t<8-$sl;$t++)
      $sl.="0";
     $ret.=inttobyte(bindec($bWrite));
    };
  for($z=0;$z<$Zbytek;$z++)
   $ret.=inttobyte(0);
 };
};



if($BitCount>=24)
{
 for($y=$Height-1;$y>=0;$y--)
 {
  for($x=0;$x<$Width;$x++)
   {
   $color=imagecolorsforindex($img,imagecolorat($img,$x,$y));
   $ret.=inttobyte($color["blue"]);
   $ret.=inttobyte($color["green"]);
   $ret.=inttobyte($color["red"]);
   if($BitCount==32)
    $ret.=inttobyte(0);//Alpha for XP_COLORS
   };
  for($z=0;$z<$Zbytek;$z++)
   $ret.=inttobyte(0);
 };
};


//MASK

 for($y=$Height-1;$y>=0;$y--)
 {
  $byteCount=0;
  $bOut="";
  for($x=0;$x<$Width;$x++)
   {
    if(($Transparent!=-1)and(imagecolorat($img,$x,$y)==$Transparent))
     {
      $bOut.="1";
     }
     else
     {
      $bOut.="0";
     };
   };
  for($p=0;$p<strlen($bOut);$p+=8)
  {
   $byte=bindec(substr($bOut,$p,8));
   $byteCount++;
   $ret.=inttobyte($byte);
  };
 $Zbytek=$byteCount%4;
  for($z=0;$z<$Zbytek;$z++)
   {
   $ret.=inttobyte(0xff);
   };
 };

//------------------

};//q





if($WriteToFile)
{
 $f=fopen($filename,"w");
 fwrite($f,$ret);
 fclose($f);
}
else
{
 echo $ret;
};

};




/*
* Helping functions:
*-------------------------
*
* inttobyte($n) - returns chr(n)
* inttodword($n) - returns dword (n)
* inttoword($n) - returns word(n)
* freadbyte($file) - reads 1 byte from $file
* freadword($file) - reads 2 bytes (1 word) from $file
* freaddword($file) - reads 4 bytes (1 dword) from $file
* freadlngint($file) - same as freaddword($file)
* decbin8($d) - returns binary string of d zero filled to 8
* RetBits($byte,$start,$len) - returns bits $start->$start+$len from $byte
* freadbits($file,$count) - reads next $count bits from $file
*/

function decbin8($d)
{
return decbinx($d,8);
};

function decbinx($d,$n)
{
$bin=decbin($d);
$sbin=strlen($bin);
for($j=0;$j<$n-$sbin;$j++)
 $bin="0$bin";
return $bin;
};

function RetBits($byte,$start,$len)
{
$bin=decbin8($byte);
$r=bindec(substr($bin,$start,$len));
return $r;

};



$CurrentBit=0;
function freadbits($f,$count)
{
 global $CurrentBit,$SMode;
 $Byte=freadbyte($f);
 $LastCBit=$CurrentBit;
 $CurrentBit+=$count;
 if($CurrentBit==8)
  {
   $CurrentBit=0;
  }
 else
  {
   fseek($f,ftell($f)-1);
  };
 return RetBits($Byte,$LastCBit,$count);
};


function freadbyte($f)
{
 return ord(fread($f,1));
};

function freadword($f)
{
 $b1=freadbyte($f);
 $b2=freadbyte($f);
 return $b2*256+$b1;
};


function freadlngint($f)
{
return freaddword($f);
};

function freaddword($f)
{
 $b1=freadword($f);
 $b2=freadword($f);
 return $b2*65536+$b1;
};

function inttobyte($n)
{
return chr($n);
};

function inttodword($n)
{
return chr($n & 255).chr(($n >> 8) & 255).chr(($n >> 16) & 255).chr(($n >> 24) & 255);
};

function inttoword($n)
 {
 return chr($n & 255).chr(($n >> 8) & 255);
 };

?>