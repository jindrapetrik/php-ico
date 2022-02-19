# Change Log
All notable changes to this project will be documented in this file.

## [Unreleased]
 - Storing 256x256 32bit icons as PNG compressed
 - Error on writing larger images than 256
 - Enhanced sample for generating icon

## [4.0] - 2022-02-18
 - Added cursor (.CUR) support
 - Stream optimize
 - Fixed transparent color when writing
 - Fixed writing icons with bpp < 8

## [3.0] - 2022-02-18
 - License change to GNU/LGPL v2.1
 - Support for reading PNG encoded icons (256x256 sizes usually)
 - Class encapsulation, total redesign
 - Code formatting
 - Fixed problems with alpha channel on 32bit images
 - Added sample data and script

## [2.3] - 2012-02-25
 - License changed to GNU/GPL v2 or v3

## [2.2] - 2012-02-18
 - License changed to GNU/GPL v3

## [2.1] - 2009-02-23
  - Redesigned sourcecode
  - Phpdoc included
  - All internal functions and global variables have prefix "jpexs_"

## [2.0] - 2006-06-30
 - For icons with Alpha channel now you can set background color
 - ImageCreateFromExeIco added
 - Fixed ICO_MAX_SIZE and ICO_MAX_COLOR values

## [1.0] - 2004
 - initial version
