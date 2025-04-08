<?php
// BATOCO (C) Taskmaster Software 2025 - This code is released under the GPL v3 license
// Uses code from UTO's drb.php from DAAD Ready
// Uses inspiration and some converted code from zxmakebas.c by Russell Marks, 1998
// zxtext2p.c by Chris Cowley
// A ZX81 BASIC to P-File Converter by maziac
// Lambatext2p by Xavsnap

//This code isn't designed to be efficient, but aiming for clarity so I can remember what it is supposed to do
//twenty years from now

//Set route to php for unix environments
#!/usr/bin/php

//Machine types SPECTRUM, NF300, LAMBDA1, LAMBDA2, ZX80, ZX81, TIMEX, PLUS3, NEXT

global $parseOptions;
global $basicLines;


ini_set("auto_detect_line_endings", true);

define("VERSION_NUMBER","0.2.0");
define("DEFAULT_OUTPUT","out.tap");
define("DEFAULT_OUTPUT_81","out.p");
define("DEFAULT_OUTPUT_80","out.o");
define("DEFAULT_EXTENSION","tap");
define("DEFAULT_EXTENSION_81","p");
define("DEFAULT_EXTENSION_80","o");



//================================================================= helper functions =====================================================
// Replace all instances of array keys in  string with array values
function strReplaceAssoc(array $replace, $subject) {

    return str_replace(array_keys($replace), array_values($replace), $subject);    
 
 }
 // Replace all instances of array keys in  string with array values case insensitive
 function strReplaceAssoci(array $replace, $subject) {

    return str_ireplace(array_keys($replace), array_values($replace), $subject);    
 
 }


function frexp ( $number, $subMachine ) 
{
    
    $returnArray = array();
    //Check if not a float and between -65535 and 65535
    if(($number==(int)$number && $number>=-65535 && $number<=65535 && $subMachine!="ZX81") or $subMachine == "ZX80")//NOTE ZX81 only stores variables as floats
    {
        /*There is an alternative way of storing whole numbers between -65535 and +65535:

        the first byte is 0.
        the second byte is 0 for a positive number, FFh for a negative one.
        the third and fourth bytes are the less (b7:0) and more (b15:8) significant bytes of the number (or the number +131072 if it is negative).
        the fifth byte is 0.  */
        $returnArray[] = 0x0E; 
        $returnArray[] = 0x00;
        if($number < 0)
            $returnArray[] = 0xFF;
        else
            $returnArray[] = 0x00;
        $returnArray[] = ($number & 0xff);
        $returnArray[] = ($number & 0xff00) >> 8;
        $returnArray[] = 0x00;
    }
    else
    {
        $number = abs($number);
        $exponent = 0;
        
        // Normalize the number
        while ($number >= 1.0) {
            $number /= 2.0;
            $exponent++;
        }
        
        while ($number != 0 && $number < 0.5) {
            $number *= 2.0;
            $exponent--;
        }
        
        if ($exponent < -128 || $exponent > 127) {
            Error("Exponent out of range (number too big). Exponent = ".$exponent);
        }
        
        if ($number != 0) {
            $exponent = 128 + $exponent;
        }
        $number *= 2.0;
        $mantissa = 0;
        
        for ($f = 0; $f < 32; $f++) {
            $mantissa = ($mantissa << 1) | intval($number);
            $number -= (int) intval($number);
            $number *= 2.0;
        }
        /* Now, if (int)num is non-zero (well, 1) then we should generally
         * round up 1. We don't do this if it would cause an overflow in the
         * mantissa, though.
         */

         if (intval($number) && $mantissa != 0xFFFFFFFF) $mantissa++;

         /* finally, zero out the top bit */
         $mantissa &= 0x7FFFFFFF;
        
        //A numerical constant in the program is followed by its binary form, using the character CHR$ 14 followed by five bytes for the number itself.
        if($subMachine="ZX81")
            $returnArray[] = 0X7E;
        else
            $returnArray[] = 0x0E; 
        $returnArray[] = $exponent; //Zero has a special representation in which all 5 bytes are 0.
        $returnArray[] = ($mantissa & 0xFF000000) >> 24;
        $returnArray[] = ($mantissa & 0xFF0000) >> 16;
        $returnArray[] = ($mantissa & 0xFF00) >> 8; 
        $returnArray[] = $mantissa & 0XFF;

    }
    return $returnArray;
  
}


//================================================================= end helper functions =================================================
function usageHelp() {
    echo "batoco.php - public domain by TaskmasterSoftware.\n\n";

    echo "usage: php batoco [input_file] [-hlpr3v(vn)] [-a line] [-i incr] [-n speccy_filename]\n";
    echo "                [-o output_file] [-s line]\n\n";
    echo "Informational Flags";
    echo "        -vn     output version number.\n";
    echo "        -h      give this usage help.\n";
    echo "        -v      verbose mode.\n";
    echo "Label-related Flags";
    echo "        -i      in labels mode, set line number incr. (default 2).\n";
    echo "        -l      use labels rather than line numbers.\n";
    echo "        -s      in labels mode, set starting line number.\n";
    echo "Settings Flags";
    echo "        -m      set machine type, must be followed by one of these options SPECTRUM, LAMBDA1, LAMBDA2, ZX80, ZX81, TIMEX, PLUS3, NEXT, NF300";
    echo "        -a      set auto-start line of basic file (default none).\n";
    echo "        -n      set Spectrum filename (to be given in tape header).\n"; 
    echo "        -o      specify output file (default",DEFAULT_OUTPUT,").\n";
    echo "        -r      output raw headerless file (default is .tap file).\n";
    echo "        -z      output TZX file(only supported for SPECTRUM and TIMEX.\n";
    echo "Deprecated Flags";
    echo "        -3      output a +3DOS compatible file (default is .tap file).\n";
    echo "        -p      output .p instead (set ZX81 mode).\n";
    exit(1);
}

function Error($msg)
{
 echo "Error: ".$msg.PHP_EOL;
 exit(2);
}

function Warning($msg)
{
 echo "Warning: ".$msg.PHP_EOL;
}



function parseCliOptions($argv, $nextParam, &$parseOptions)
{
    while ($nextParam<sizeof($argv))
    {
        $currentParam = $argv[$nextParam]; $nextParam++;
        if (substr($currentParam,0,1)=='-')
        {
            $currentParam = strtoupper($currentParam);
            switch ($currentParam)
            {
                case "-VN" : echo "Version number: ",VERSION_NUMBER,"\n";exit(0);
                case "-V" : $parseOptions->verboseMode = true;break;
                case "-A" : $parseOptions->autostartLine = $argv[$nextParam];
        
                            if($parseOptions->autostartLine > 9999)
                            {
                                Error("Auto-start line must be in the range 0 to 9999.");
                            }
                            break;
                case "-H" : usageHelp();
                case "-I" : $parseOptions->setLabelModeIncrement = $argv[$nextParam];break;
                case "-L" : $parseOptions->useLabels = true;break;
                case "-N" : $parseOptions->spectrumFilename = $argv[$nextParam];break;
                case "-O" : $parseOptions->outputFilename = $argv[$nextParam];break;
                case "-P" : $parseOptions->zx81Mode = true;$parseOptions->outputFormat = "P81";break;
                case "-R" : $parseOptions->outputTapeMode = false;$parseOptions->outputRawFileMode = true;$parseOptions->outputFormat = "RAW";break;
                case "-3" : $parseOptions->outputTapeMode = false;$parseOptions->outputPlus3DOSFileMode = true;$parseOptions->outputFormat = "DOS";break;
                case "-S" : $parseOptions->setLabelsModeStartLineNumber = $argv[$nextParam];break;
                case "-M" : $parseOptions->machineType = $argv[$nextParam];break;
                case "-Z" : $parseOptions->outputTZX = true;break;
                default: Error("$currentParam is not a valid option");
            }
        } 
    }
    $parseOptions->inputFilename = $argv[1];
}

function parsePostOptions(&$parseOptions)
{
    //This file can be called like http://localhost/batoco.php?input=input.txt
    //Other supported parameters are
    // v=on                     : Turns verbose mode on
    // a=<autostartline>        : Sets the line number to auto run the program from
    // i=<IncrementInterval>"   : In labels mode, set line number incr. (default 2).\n";
    // l=on                     : use labels rather than line numbers.\n";
    // n=<Filename>             : set Spectrum filename (to be given in tape header).";
    // o=<OutputFilename>       : specify output file (default",DEFAULT_OUTPUT,").\n";
    // p=on                     : output .p instead (set ZX81 mode).\n";
    // r=on                     : output raw headerless file (default is .tap file).\n";
    // 3=on                     : output a +3DOS compatible file (default is .tap file).\n";
    // s=<StartNumber>          :in labels mode, set starting line number ";
    // z=on                     : output a TZX file (only support for SPECTRUM or TIMEX)

    //e.g. http://localhost/batoco.php?input=input.txt&n=Game&o=Game.tap

    if (is_array($_POST)) 
    {
        $ok_key_names = ['input', 'v', 'a', 'i', 'l', 'n', 'o', 'p', 'r', '3', 's','m','z'];
        $ok_keys = array_flip(array_filter($ok_key_names, function($arr_key) {
            return array_key_exists($arr_key, $_POST);
        }));
    
        $extras = array_diff_key($_POST, $ok_keys);
        if(count($extras) > 0) {
            Error ("Bad Parameter Structure: Only use acceptable parameters.");
        }
    }
    //Set all key names to uppercase
    $a=(array_change_key_case($_POST, CASE_UPPER));
    if (array_key_exists("V",$a) )
    {
        $parseOptions->verboseMode = true;
    }
    if (array_key_exists("A",$a) )
    {
        $parseOptions->autostartLine = (int) $a['A'];
        
        if($parseOptions->autostartLine > 9999)
        {
            Error("Auto-start line must be in the range 0 to 9999.");
        }
    }
    if (array_key_exists("I",$a) )
    {
        $parseOptions->setLabelModeIncrement = (int) $a['I'];
    }
    if (array_key_exists("L",$a) )
    {
        $parseOptions->useLabels = true;
    }
    if (array_key_exists("N",$a) )
    {
        $parseOptions->spectrumFilename = $a['N'];
    }
    if (array_key_exists("O",$a) )
    {
        $parseOptions->outputFilename = $a['O'];
    }
    if (array_key_exists("P",$a) )
    {
        //$parseOptions->zx81Mode = true;
        $parseOptions->outputFormat = "P81";
    }
    if (array_key_exists("R",$a) )
    {
        //$parseOptions->outputTapeMode = false;$parseOptions->outputRawFileMode = true;
        $parseOptions->outputFormat = "RAW";
    }
    if (array_key_exists("3",$a) )
    {
        //$parseOptions->outputTapeMode = false;$parseOptions->outputPlus3DOSFileMode = true;
        $parseOptions->outputFormat = "DOS";
    }
    if (array_key_exists("S",$a) )
    {
        $parseOptions->setLabelsModeStartLineNumber = (int) $a['S'];
    }
    if (array_key_exists("INPUT",$a) )
    {
        $parseOptions->inputFilename = $a['INPUT'];
    }
    if (array_key_exists("M",$a) )
    {
        $parseOptions->machineType = $a['M'];
    }
    if (array_key_exists("Z",$a) )
    {
        $parseOptions->outputTZX = true;
    }

}
function parseURLOptions(&$parseOptions)
{
    //This file can be called like http://localhost/zx_htm2tap/batoco.php?input=inputfile.bas
    // v=on                     : Turns verbose mode on
    // a=<autostartline>        : Sets the line number to auto run the program from
    // i=<IncrementInterval>"   : In labels mode, set line number incr. (default 2).\n";
    // l=on                     : use labels rather than line numbers.\n";
    // n=<Filename>             : set Spectrum filename (to be given in tape header).";
    // o=<OutputFilename>       : specify output file (default",DEFAULT_OUTPUT,").\n";
    // p=on                     : output .p instead (set ZX81 mode).\n";
    // r=on                     : output raw headerless file (default is .tap file).\n";
    // 3=on                     : output a +3DOS compatible file (default is .tap file).\n";
    // s=<StartNumber>          : in labels mode, set starting line number ";
    // input=<Input filename>   : Name of the file to read and convert
    // z=on                     : output a TZX file (only support for SPECTRUM or TIMEX)

    //e.g. http://localhost/zx_htm2tap/batoco.php?input=inputfile.bas&v=on&l=on&n=ZMB-TEST&o=outputfile.tap
    
    if (is_array($_GET)) {
        $ok_key_names = ['input', 'v', 'a', 'i', 'l', 'n', 'o', 'p', 'r', '3', 's','m','z'];
        $ok_keys = array_flip(array_filter($ok_key_names, function($arr_key) {
            return array_key_exists($arr_key, $_GET);
        }));
    
        $extras = array_diff_key($_GET, $ok_keys);
        if(count($extras) > 0) {
            Error ("Bad Parameter Structure: Only use acceptable parameters.");
        }
    }
    //Set all key names to uppercase
    $a=(array_change_key_case($_GET, CASE_UPPER));
    if (array_key_exists("V",$a) )
    {
        $parseOptions->verboseMode = true;
    }
    if (array_key_exists("A",$a) )
    {
        $parseOptions->autostartLine = (int) $a['A'];
        
        if($parseOptions->autostartLine > 9999)
        {
            Error("Auto-start line must be in the range 0 to 9999.");
        }
    }
    if (array_key_exists("I",$a) )
    {
        $parseOptions->setLabelModeIncrement = (int) $a['I'];
    }
    if (array_key_exists("L",$a) )
    {
        $parseOptions->useLabels = true;
    }
    if (array_key_exists("N",$a) )
    {
        $parseOptions->spectrumFilename = $a['N'];
    }
    if (array_key_exists("O",$a) )
    {
        $parseOptions->outputFilename = $a['O'];
    }
    if (array_key_exists("P",$a) )
    {
        //$parseOptions->zx81Mode = true;
        $parseOptions->outputFormat = "P81";
    }
    if (array_key_exists("R",$a) )
    {
        //$parseOptions->outputTapeMode = false;$parseOptions->outputRawFileMode = true;
        $parseOptions->outputFormat = "RAW";
    }
    if (array_key_exists("3",$a) )
    {
        //$parseOptions->outputTapeMode = false;$parseOptions->outputPlus3DOSFileMode = true;
        $parseOptions->outputFormat = "DOS";
    }
    if (array_key_exists("S",$a) )
    {
        $parseOptions->setLabelsModeStartLineNumber = (int) $a['S'];
    }
    if (array_key_exists("INPUT",$a) )
    {
        $parseOptions->inputFilename = $a['INPUT'];
    }
    if (array_key_exists("M",$a) )
    {
        $parseOptions->machineType = $a['M'];
    }
    if (array_key_exists("Z",$a) )
    {
        $parseOptions->outputTZX = true;
    }
             
}
//--------------------------------------------Add File Headers--------------------------------------------------------
function prependPlus3Header($outputFileName,&$parseOptions)
{
    $fileSize = filesize($outputFileName) + 128; // Final file size wit header
    $inputHandle = fopen($outputFileName, 'r') or die("Unable to open file!");
    $outputHandle = fopen("prepend.tmp", "w") or die("Unable to open file!");
    $header = array();
    $header[]= ord('P'); //+3DOS signature
    $header[]= ord('L');
    $header[]= ord('U');
    $header[]= ord('S');
    $header[]= ord('3');
    $header[]= ord('D');
    $header[]= ord('O');
    $header[]= ord('S');
    $header[]= 0x1A; // Soft EOF
    $header[]= 0x01; // Issue
    $header[]= 0x00; // Version
    $header[]= $fileSize & 0XFF;  // Four bytes for file size
    $header[]= ($fileSize & 0xFF00) >> 8;
    $header[]= ($fileSize & 0xFF0000) >> 16;
    $header[]= ($fileSize & 0xFF000000) >> 24;  
    $header[]= 0x00; // Bytes:
    $fileSize -= 128; // Get original size
    $header[]= $fileSize & 0x00FF;  // Two bytes for data size
    $header[]= ($fileSize & 0xFF00) >> 8;
    //Bytes 18 and 19 Autostart LINE Number (or 8000h..FFFFh if no autostart)
    $header[]= ($parseOptions->autostartLine & 0x00FF);  // Two bytes for data size
    $header[]= ($parseOptions->autostartLine & 0xFF00) >> 8;
    $header[]= $fileSize & 0x00FF;  // Two bytes for data size
    $header[]= ($fileSize & 0xFF00) >> 8;
    while (sizeof($header)<127) $header[]= 0x00; // Fillers
    $checksum = 0;
    for ($i=0;$i<127;$i++)  $checksum+=$header[$i];
    $header[]= $checksum & 0xFF; // Checksum
    
    // Dump header
    for ($i=0;$i<128;$i++) fputs($outputHandle, chr($header[$i]), 1);

    //Reset checksum
    $checksum=0;
    // Dump original file
    while (!feof($inputHandle))
    {
        $c = fgetc($inputHandle);
        //Calculate checksum
        fputs($outputHandle,$c,1);
    }
    fclose($inputHandle);
    fclose($outputHandle);
    unlink($outputFileName);
    rename("prepend.tmp" ,$outputFileName);
}

function prependSpectrumTapeHeader(&$parseOptions)
{
    
    //$fileSize = filesize($parseOptions->outputFilename) + 17; // Final file size with header
    $fileSize = filesize($parseOptions->outputFilename);
    $inputHandle = fopen($parseOptions->outputFilename, 'r') or die("Unable to open file!");
    $outputHandle = fopen("prepend.tmp", "w") or die("Unable to open file!");

    /*Each block is preceeded by a 16bit length value (eg. 0013h for Header blocks), followed by "length" bytes which contain the same 
    information as stored on real cassettes (including the leading Blocktype byte, and ending Checksum byte).*/

    // This is the file type 0 for BASIC, 1 for BASIC Number array, 2 for BASIC Character array or 3 for binary CODE file or SCREEN$ file
    $header[]= 0x00;
    //Set 10 byte filename to blank default which is chr(20h)
    while(count($header)<11)$header[]=0x20; 
    //Now copy in the filename, but only the first ten characters
    $StrPtr=0;
    while(($StrPtr < strlen($parseOptions->spectrumFilename)) and ($StrPtr < 9))
    {
        $header[$StrPtr+1] = ord($parseOptions->spectrumFilename[$StrPtr]);
        $StrPtr++;
    }
    //Bytes 11 and 12 contain the length of the following file
    $header[]= ($fileSize & 0x00FF);  // Two bytes for data size
    $header[]= ($fileSize & 0xFF00) >> 8;
    //Bytes 13 and 14 Autostart LINE Number (or 8000h..FFFFh if no autostart)
    $header[]= ($parseOptions->autostartLine & 0x00FF);  // Two bytes for data size
    $header[]= ($parseOptions->autostartLine & 0xFF00) >> 8;
    //Bytes 15 and 16 the size of the PROG area, data after this would be the varaibales table which we don't need to worry about
    $header[]= ($fileSize & 0x00FF);  // Two bytes for data size
    $header[]= ($fileSize & 0xFF00) >> 8;

    //Checksum (above 18 bytes XORed with each other)
    $checksum = 0;
    for ($i=1;$i<17;$i++)  
        $checksum^=$header[$i];

    $header[]= ($checksum &= 0xFF); // Checksum
    //Start TAP file
    fputs($outputHandle, chr(0x13));
    fputs($outputHandle, chr(0x00));
    fputs($outputHandle, chr(0x00));
    // Dump header
    for ($i=0;$i<18;$i++) fputs($outputHandle, chr($header[$i]), 1);
    //If using TZX
    if($parseOptions->outputTZX)
    {
        // Standard Speed Block
        fputs($outputHandle, chr(0x10)); 
        fputs($outputHandle, chr(0xE8));  
        fputs($outputHandle, chr(0x03)); 
    }  

    // write (most of) tap bit for data block
    fputs($outputHandle, chr(($fileSize+2) & 0x00FF));
    fputs($outputHandle, chr((($fileSize+2) & 0xFF00) >> 8));
    fputs($outputHandle, chr(0xFF));
    
    
    //Reset checksum
    $checksum=0xFF;
    // Dump original file
    while (!feof($inputHandle))
    {
        $c = fgetc($inputHandle);
        
        //Calculate checksum
        $checksum^=ord($c);
        fputs($outputHandle,$c,1);
    }
    //Add checksum on end
    fputs($outputHandle,(chr($checksum &= 0xFF)),1);
    fclose($inputHandle);
    fclose($outputHandle);
    unlink($parseOptions->outputFilename);
    //pause to allow temp file to be unlocked
    echo "Pausing to allow time for the temp file to unlock." . PHP_EOL;
    sleep(1);
    rename("prepend.tmp" ,$parseOptions->outputFilename);
    echo "Done." . PHP_EOL;
}

function prependZX81TapeHeader(&$parseOptions,$sinclairBasic)
{
    if($parseOptions->verboseMode)echo "Writing ZX81 Header".PHP_EOL;
        
    $fileSize = filesize($parseOptions->outputFilename);
    $inputHandle = fopen($parseOptions->outputFilename, 'r') or die("Unable to open file!");
    $outputHandle = fopen("prepend.tmp", "w") or die("Unable to open file!");

    //Write out the filename
   /*$StrPtr=0;
    while(($StrPtr < strlen($parseOptions->spectrumFilename)) and ($StrPtr < 9))
    {
        fputs($outputHandle, chr($sinclairBasic[$parseOptions->spectrumFilename[$StrPtr]]),1);
        $StrPtr++;
    }*/

    // Make sysvars: 116 bytes from 0x4009 (16393) to 16509
    //Create 116 byte array for the header and zero it
    $header=array_fill(0, 116, 0);
    //Set D_File size, this is currently either 24 lines of 33 characters (including line end), or 24 lines of 1 character (line end)
    //In the future this might be flexible to support prefilled display files
    if($parseOptions->full_D_FILE)
        $D_FILE_SIZE = 0x0318;
    else
        $D_FILE_SIZE = 0x21;
    //Program start address
    $programStartAddress = 0x407D;
    //Skip 	Version
    $header[1] = ($parseOptions->firstLineNum & 0x00FF); //E_PPC - Line number of line which has the edit cursor   
    $header[2] = ($parseOptions->firstLineNum & 0xFF00)>>8;// E_PPC  high
    $header[3] = (($fileSize+ $programStartAddress) & 0x00FF);    // Two bytes for data size  // D_FILE   - Address of display file
    $header[4] = (($fileSize+ $programStartAddress) & 0xFF00)>>8; // Two bytes for data size>>8;
    $header[5] = (($fileSize+ $programStartAddress+1) & 0x00FF);    // DF_CC - Address of print position in display file 
    $header[6] = (($fileSize+ $programStartAddress+1) & 0xFF00)>>8; // DF_CC high
    $header[7] = (($fileSize+ $programStartAddress+$D_FILE_SIZE+1) & 0x00FF);    // VARS - Address of program variables
    $header[8] = (($fileSize+ $programStartAddress+$D_FILE_SIZE+1) & 0xFF00)>>8; // VARS high
    //Skip 	DEST - Address of variable in assignment.
    $header[11] = (($fileSize+ $programStartAddress+$D_FILE_SIZE+2) & 0x00FF);   // E_LINE   - Pointer to line edit buffer
    $header[12] = (($fileSize+ $programStartAddress+$D_FILE_SIZE+2) & 0xFF00)>>8;// E_LINE high
    $header[13] = (($fileSize+ $programStartAddress+$D_FILE_SIZE+2) & 0x00FF);   // CH_ADD   - Address of the next character to be interpreted
    $header[14] = (($fileSize+ $programStartAddress+$D_FILE_SIZE+2) & 0xFF00)>>8;
    //Skip X_PTR	Address of the character preceding the  marker. 
    $header[17] = (($fileSize+ $programStartAddress+$D_FILE_SIZE+2) & 0x00FF);   //STKBOT   - Points to the bottom of the calculator stack   
    $header[18] = (($fileSize+ $programStartAddress+$D_FILE_SIZE+2) & 0xFF00)>>8;//STKBOT high
    $header[19] = (($fileSize+ $programStartAddress+$D_FILE_SIZE+2) & 0x00FF);   // STKEND   - Points to the end of the calculator stack
    $header[20] = (($fileSize+ $programStartAddress+$D_FILE_SIZE+2) & 0xFF00)>>8;//STKEND high
    //Skip BERG	Calculator's b register.
    $header[22] = 0x5d;                             // MEM      - Address of area used for calculator's memory (we point this at MEMBOT 0x405D
    $header[23] = 0x40;                             //MEM high
    //24 is not used
    $header[25] = 2;                                // DF_SZ       - Number of lines (including 1 blank line) in lower part of the screen
    //Skip 	S_TOP	The number of the top program line in automatic listings.
    $header[28] = 0xFF;                             // LAST_K      - Shows which keys are pressed
    $header[29] = 0xFF;
    $header[30] = 0xFF;                             // DEBOUNCE    - Debounce status of keyboard
    $header[31] = 55;                               // MARGIN      - Number of scanlines before start of display (55=PAL, 31=NTSC)
    $header[32] = $header[3];                       // NXTLIN low  - Address of next program line to be executed
    $header[33] = $header[4];                       // NXTLIN high
    //If using startline 32 and 33 are startline+0x407e;
    //Skip OLDPPC	Line number of which CONT jumps.
    //Skip FLAGX	Various flags.
    //Skip STRLEN	Length of string type destination in assignment.
    $header[39] = 0x8D;                             // T_ADDR      - Address of next item in syntax table
    $header[40] = 0x0C;                             // T_ADDR high
    //Skip SEED	The seed for RND. This is the variable that is set by RAND.
    $header[43] = 0x7D;                             // FRAMES      - Counts the frames displayed on the television. Bit 15 is 1. Bits 0 to 14 are decremented for each frame set to the television. This can be used for timing, but PAUSE also uses it. PAUSE resets to 0 bit 15, & puts in bits 0 to 14 the length of the pause. When these have been counted down to zero, the pause stops. If the pause stops because of a key depression, bit 15 is set to 1 again.
    $header[44] = 0xFD;
    //Skip COORDS	x-coordinate of last point PLOTted.
    //Skip COORDS	y-coordinate of last point PLOTted.
    $header[47] = 0xBC;                             // PR_CC       - Least significant byte of address of next position for LPRINT to print
    $header[48] = 0x21;                             // S_POSN x    - Column number for print position
    $header[49] = 0x18;                             // S_POSN y    - Row number for print position
    $header[50] = 0x40;                             // CDFLAG      - Various flags. Bit 7 is set during SLOW mode, Bit 6 is the true fast/slow flag
    $header[83] = 118;                              // PRBUF      - Printer buffer (33 bytes, 33rd is NEWLINE)
    //TESTing purposes only echo var_dump($header);
    // Dump header
    for ($i=0;$i<116;$i++) fputs($outputHandle, chr($header[$i]), 1);

    // Dump original file
    while (!feof($inputHandle))
    {
        fputs($outputHandle,fgetc($inputHandle),1);
    }
    //Add a HALT
    fputs($outputHandle, chr(0x76));
    //Now write an empty display buffer
    //Each line of the D_FILE (display file) is 0-32 characters long and ends with a HALT 0x76
    //There are 24 of these lines. A collapsed D_FILE might only ha

    //var_dump($parseOptions->D_FILE);
    for ( $i= 0; $i<24; $i++ )
    {
        //If D_FILE isn't collapsed fill with zeros
        if($parseOptions->full_D_FILE)
        {
            //Check if D_FILE has content
           if(isset($parseOptions->D_FILE[$i]))
            {
                //We have content so output it to currentline, each line of D_FILE is held as an array of 32 BYTES
                foreach ( $parseOptions->D_FILE[$i] AS $D_BYTE )
                    fputs($outputHandle,chr($D_BYTE), 1);
            }
            else
            {
                for ($j= 0; $j< 32; $j++)
                    fputs($outputHandle,chr(0x00), 1);
            }
        }
        
        fputs($outputHandle,chr(0x76), 1);
    }
    //Add termination byte
    
    fputs($outputHandle,chr(0x80), 1);


    fclose($inputHandle);
    fclose($outputHandle);
    unlink($parseOptions->outputFilename);
    //pause to allow temp file to be unlocked
    echo "Pausing to allow time for the temp file to unlock." . PHP_EOL;
    sleep(1);
    rename("prepend.tmp" ,$parseOptions->outputFilename);
    echo "Done." . PHP_EOL;
}

function prependZX80TapeHeader(&$parseOptions,$sinclairBasic)
{
    if($parseOptions->verboseMode)echo "Writing ZX80 Header".PHP_EOL;

    $fileSize = filesize($parseOptions->outputFilename);
    $inputHandle = fopen($parseOptions->outputFilename, 'r') or die("Unable to open file!");
    $outputHandle = fopen("prepend.tmp", "w") or die("Unable to open file!");

    //Write out the filename
    /*$StrPtr=0;
    while(($StrPtr < strlen($parseOptions->spectrumFilename)) and ($StrPtr < 9))
    {
        fputs($outputHandle, chr($sinclairBasic[$parseOptions->spectrumFilename[$StrPtr]]),1);
        $StrPtr++;
    }*/

    // Make sysvars: 40 bytes from 0x4009 (16393) to 16509
    //Create 116 byte array for the header and zero it
    $header=array_fill(0, 40, 0);
    //Set D_File size, this is currently either 24 lines of 33 characters (including line end), or 24 lines of 1 character (line end)
    //In the future this might be flexible to support prefilled display files
    if($parseOptions->full_D_FILE)
        $D_FILE_SIZE = 0x0318;
    else
        $D_FILE_SIZE = 0x21;
    //Program start address
    $programStartAddress = 0x4028;

    //SYS VARS
    $header[0] =255; //Address 16384 - 1 Byte - 1 Less than runtime error number
    $header[1] = 0x88;  //Address 16385 - 1 Byte - Sundry flags which control BASIC system
    $header[2] = 0xFE;  //Address 16386 - 1 Word - Statement number of current statement
    $header[3] = 0xFF;
    $header[6] = ($parseOptions->firstLineNum & 0x00FF); //Address 16390 - 1 word - statement number of > Cursor
    $header[7] = ($parseOptions->firstLineNum & 0xFF00)>>8;
    $header[8] = (($fileSize+ $programStartAddress) & 0x00FF);   //Address 16392 - 1 word - VARS
    $header[9] = (($fileSize+ $programStartAddress) & 0xFF00)>>8;
    $header[10] = $header[8]+1; //Address 16394 - 1 word - E-LINE - Space assigned for input or edit lines, cleared after use
    $header[11] = $header[9];
    $header[4] =  $header[10] +1; //Address 16388 - 1 word - position in RAM of K or L cursor last line
    $header[5] = $header[11] ;
    $header[12] = $header[10]+2; //Address 16396 - 1 word - D-LINE - Always contains 25 newline characters, first and last bytes are 0x76, in between are 24 lines 0 - 32 characters each
    $header[13] = $header[11];
    $header[14] = $header[12]+1; //Address 16398 - 1 Word - DF-EA - points to start of the lower part of the screen this is between D-LINE and DF-END
    $header[15] = $header[13];
    $header[16] = $header[14]; //Address 16400 - 1 Word - DF-END - points to end of display file
    $header[17] = $header[15];
    $header[18] = 0x02; //Address 16402 - 1 Byte - Number of lines in lower half of screen
    $header[19] = 0x00; //Address 16403 - 1 Word -  Statement number of first line on screen
    $header[20] = 0x00;
    $header[21] = 0x00; //Address 16405 - 1 Word - Address of charater or token preceding the S marker
    $header[22] = 0x00;
    $header[23] = 0x00; //Address 16407 - 1 Word - Statement number that CONTINUE jumps to
    $header[24] = 0x00;
    $header[25] = 0x00;    //Address 16409 - 1 Byte - sundry flags which control the syntax analysis
    $header[26] = 0xB0;    //Address 16410 - 1 Word - Address of next item in syntax table
    $header[27] = 0x07;
    $header[28] = 0x00; //Address 16412 - 1 Word - Seed for random number generator
    $header[29] = 0x00;
    $header[30] = 0x1B; //Address 16414 - 1 word - Number of frames since the ZX-80 was switched on
    $header[31] = 0x18;
    $header[32] = $header[12]; //Address 16416 - 1 Word - Address of 1st character of 1st variable name
    $header[33] = $header[13];
    $header[34] = 0x00; //Address 16418 - 1 Word - Value of last expession or variable
    $header[35] = 0x00;
    $header[36] = 0x21;  //Address 16420 - 1 Byte - position of next line character to be written to screen
    $header[37] = 0x17; //Address 16421 - 1 Byte - Position of current line on screen
    $header[38] = $header[10]+1; //Address 16422 - 1 Word - Address of character after closing bracket of PEEK or newline after POKE statement
    $header[39] = $header[11];

/*SYSVARS begin at 16384
Program begins at 16424
VARS begin at 16424 + Program length
After vars is 1 Byte 0x80
E_LINE is address after this byte
D_FILE is after E_LINE
After end of D_File is spare space
then the stack pointed by SP

Each program line is same as ZX Spectrum, except no line length stored and line end is 0x76

Variables are stored as Integers
*/
    // Dump header
    for ($i=0;$i<40;$i++) fputs($outputHandle, chr($header[$i]), 1);

    // Dump original file
    while (!feof($inputHandle))
    {
        fputs($outputHandle,fgetc($inputHandle),1);
    }
    //Add a HALT
    //fputs($outputHandle, chr(0x76));
    //Now write an empty display buffer
    //Each line of the D_FILE (display file) is 0-32 characters long and ends with a HALT 0x76
    //There are 24 of these lines. A collapsed D_FILE might only ha
    /*for ( $i= 0; $i<24; $i++ )
    {
        //If D_FILE isn't collapsed fill with zeros
        if($parseOptions->full_D_FILE)
        {
            for ($j= 0; $j< 32; $j++)
                fputs($outputHandle,chr(0x00), 1);
        }
        
        fputs($outputHandle,chr(0x76), 1);
    }*/
    //Add termination byte
    
    fputs($outputHandle,chr(0x80), 1);


    fclose($inputHandle);
    fclose($outputHandle);
    unlink($parseOptions->outputFilename);
    //pause to allow temp file to be unlocked
    echo "Pausing to allow time for the temp file to unlock." . PHP_EOL;
    sleep(1);
    rename("prepend.tmp" ,$parseOptions->outputFilename);
    echo "Done." . PHP_EOL;
}

function prependLambdaTapeHeader(&$parseOptions,$sinclairBasic)
{
    if($parseOptions->verboseMode)echo "Writing LAMBDA Header".PHP_EOL;
        
    $fileSize = filesize($parseOptions->outputFilename);
    $inputHandle = fopen($parseOptions->outputFilename, 'r') or die("Unable to open file!");
    $outputHandle = fopen("prepend.tmp", "w") or die("Unable to open file!");


    //Set D_File size, Lambda always has a full sized D-FILE
    $D_FILE_SIZE = 0x0318;

    // Make sysvars: 116 bytes from 0x4009 (16384) to 16509
    //Create 116 byte array for the header and zero it
    $header=array_fill(0, 116, 0);
    //Program start address 17302
    $programStartAddress = 0x4396;
    echo "File size is: ".$fileSize.PHP_EOL;
    //Skip 	Version
    $header[0] = 0xFF; //VERSN - 16393 - 1 Byte - 00h=ZX81, FFh=Lambda
    $header[1] = 0x7D; //D_FILE Address - 16394 - 1 Word
    $header[2] = 0x40; //
    $header[3] = ($programStartAddress & 0x00FF); //PROGRAM - 16396 - 1 Word = 17302
    $header[4] = ($programStartAddress & 0xFF00)>>8;
    $header[5] = 0x7D; //DF-CC - Address of print position in display file - 16398 - 1 Word -
    $header[6] = 0x40; //
    $header[7] = (($programStartAddress + $fileSize+ 1) & 0x00FF); //VARS - 16400 - 1 Word
    $header[8] = (($programStartAddress + $fileSize+ 1) & 0xFF00)>>8;//
    $header[9] = 0x00;//DEST - 16402 - 1 Word
    $header[10] = 0x00;//
    $header[11] = (($programStartAddress + $fileSize+ 1) & 0x00FF); //E-LINE - 16404 - 1 Word
    $header[12] = (($programStartAddress + $fileSize+ 1) & 0xFF00)>>8;
    $header[13] = (($programStartAddress + $fileSize+ 1) & 0x00FF);//CH-ADD - 16406 - 1 Word
    $header[14] = (($programStartAddress + $fileSize+ 1) & 0xFF00)>>8; //
    $header[15] = 0x3B;//X-PTR - 16408 - 1 Word
    $header[16] = 0x40; //
    $header[17] = (($programStartAddress + $fileSize+ 1) & 0x00FF);//STKBOT - 16410 - 1 Word
    $header[18] = (($programStartAddress + $fileSize+ 1) & 0xFF00)>>8; //
    $header[19] = (($programStartAddress + $fileSize+ 1) & 0x00FF);//STKEND - 16412 - 1 Word
    $header[20] = (($programStartAddress + $fileSize+ 1) & 0xFF00)>>8;//
    $header[21] = 0x80;//BERG - 16414 - Byte
    $header[22] = 0x5D;//MEM - 16415 - 1 Word
    $header[23] = 0x40;//
    $header[24] = 0x19; //MUNIT - 16417 - 1 Byte - TEMPO, for music
    $header[25] = 0x02;//DF-SZ - 16418 - 1 Byte - Number of lines (including 1 blank line) in lower part of the screen
    //Skip S-TOP - 16419 - 1 Word
    //
    $header[28] = 0xFF; //LAST_K - 16421 - 1 Word
    $header[29] = 0xFF; //
    $header[30] = 0x0F; //BOUNCE - 16423 - 1 Byte
    $header[31] = 0x1F; //MARGIN - 16424 - 1 Byte - Number of scanlines before start of display (55=PAL, 31=NTSC)
    //$header[32] = ($parseOptions->firstLineNum & 0x00FF); //E_PPC - 16425 - 1 Word - Line number of line which has the edit cursor
    //$header[33] = ($parseOptions->firstLineNum & 0xFF00)>>8;// E_PPC  high
    
    $header[32] = 0x1E; //E_PPC - 16425 - 1 Word - Line number of line which has the edit cursor
    $header[33] = 0x00;// E_PPC  high

    //Skip OLDPPC - 16427 - 1 Word - CONT
    //Skip FLAG X - 16429 - 1 Byte
    //Skip STRLEN - 16430 - 1 Word
    //Skip
    $header[39] = 0x34; //T_ADDR - 16432 - 1 Word
    $header[40] = 0x1B; //
    //Skip SEED - 16434 - 1 Word
    //Skip
    $header[43] = 0x7A; //FRAMES - 16436 - 1 Word
    $header[44] = 0xE4;//
    //Skip PPC - 16438 - 1 Word
    //
    $header[47] = 0x3C; //PR CC - 16440 - 1 Byte
    $header[48] = 0x21; //S_POSN - 16441 - 1 Word
    $header[49] = 0x18; //
    $header[50] = 0x40;//CDFLAG - 16443 - 1 Byte (additional bit4=graphics_cursor, bit5=beep_disable)
    //PrintBuffer 33 Bytes
    $header[83] = 0x76; //End of printbuffer
    //MEMBOT - 16477 - 30 Bytes
    $header[114] = 0x76; //BLINK - 16507 - 1 Word
    $header[115] = 0x43; //BLINK - 16507 - 1 Word
    
    // Dump header
    for ($i=0;$i<116;$i++) fputs($outputHandle, chr($header[$i]), 1);

    //Unlike ZX80 or ZX81 we now write the D_FILE
    fputs($outputHandle, chr(0x76));
    //Now write an empty display buffer
    //Each line of the D_FILE (display file) is 0-32 characters long and ends with a HALT 0x76
    //There are 24 of these lines. A collapsed D_FILE might only ha
    var_dump($parseOptions->D_FILE);
    for ( $i= 0; $i<24; $i++ )
    {
        //Check if D_FILE has content
        if(isset($parseOptions->D_FILE[$i]))
        {
            //We have content so output it to currentline, each line of D_FILE is held as an array of 32 BYTES
            foreach ( $parseOptions->D_FILE[$i] AS $D_BYTE )
                fputs($outputHandle,chr($D_BYTE), 1);
        }
        else
        {
            for ($j= 0; $j< 32; $j++)
                fputs($outputHandle,chr(0x00), 1);
        }
        
        fputs($outputHandle,chr(0x76), 1);
    }
    
    // Now we dump the original file
    while (!feof($inputHandle))
    {
        fputs($outputHandle,fgetc($inputHandle),1);
    }
    //Add start and end markers of empty VARS area
    fputs($outputHandle,chr(0xFF), 1);
    fputs($outputHandle,chr(0x80), 1);

    fclose($inputHandle);
    fclose($outputHandle);
    unlink($parseOptions->outputFilename);
    //pause to allow temp file to be unlocked
    echo "Pausing to allow time for the temp file to unlock." . PHP_EOL;
    sleep(1);
    rename("prepend.tmp" ,$parseOptions->outputFilename);
    echo "Done." . PHP_EOL;
}

function prependTZXHeader(&$parseOptions)
{
    $fileSize = filesize($parseOptions->outputFilename); // Final file size
    $header = array();
    //Intro Block
    $header[]= ord('Z');
    $header[]= ord('X');
    $header[]= ord('T');
    $header[]= ord('a');
    $header[]= ord('p');
    $header[]= ord('e');
    $header[]= ord('!');
    $header[]= 0x1A; // Soft EOF
    $header[]= 0x01; // Issue
    $header[]= 0x0D; // Version
    // Start Text block
    $header[]= 0x30; 
    $header[]= 0x11;//Length of text block - Alter this to match the section below
    $header[]= ord('C');
    $header[]= ord('R');
    $header[]= ord('E');
    $header[]= ord('A');
    $header[]= ord('T');
    $header[]= ord('E');
    $header[]= ord('D');
    $header[]= ord(' ');
    $header[]= ord('B');
    $header[]= ord('Y');
    $header[]= ord(' ');
    $header[]= ord('B');
    $header[]= ord('A');
    $header[]= ord('T');
    $header[]= ord('O');
    $header[]= ord('C');
    $header[]= ord('O');
    // Standard Speed Block
    $header[]= 0x10;  
    $header[]= 0xE8;   //Pause after this block in milliseconds  
    $header[]= 0x03;        
    //$header[]= $fileSize & 0x00FF;  // Two bytes for data size
    //$header[]= ($fileSize & 0xFF00) >> 8;
    
    $outputHandle = fopen("prepend.tmp", "w") or die("Unable to open file!");
    // Dump header
    for ($i=0;$i<sizeof($header);$i++) fputs($outputHandle, chr($header[$i]), 1);

    fwrite($outputHandle,file_get_contents($parseOptions->outputFilename));
    fclose($outputHandle);
    unlink($parseOptions->outputFilename);
    //pause to allow temp file to be unlocked
    echo "Pausing to allow time for the temp file to unlock." . PHP_EOL;
    sleep(1);
    rename("prepend.tmp" ,$parseOptions->outputFilename);
}
//--------------------------------------------End Add File Headers-----------------------------------------------------

//-------------------------------------------------Init Functions------------------------------------------------------

function initArrays(&$keywordArray, &$characterArray, &$parseOptions)
{
    switch ($parseOptions->machineType)
    {
        case "SPECTRUM" :
            initSpectrumArrays($keywordArray, $characterArray);
            $parseOptions->subMachine = "SPECTRUM";
            $parseOptions->caseSensitive = true;
            $parseOptions->lineEnding = 0x0D;
            if($parseOptions->outputTZX)
            {
                $tempFilename = pathinfo($parseOptions->outputFilename);
                $parseOptions->outputFilename = $tempFilename['filename'].'.'."tzx";
            }
            break;
        
        case "PLUS3" :
            initSpectrumArrays($keywordArray, $characterArray);
            $parseOptions->subMachine = "SPECTRUM";
            $parseOptions->caseSensitive = true;
            $parseOptions->lineEnding = 0x0D;
            if($parseOptions->outputFilename == DEFAULT_OUTPUT)
                $parseOptions->outputFilename = "Output";
            $parseOptions->outputFormat = "DOS";
            break;
        
        case "TIMEX" :
            initTimexArrays($keywordArray, $characterArray);
            $parseOptions->subMachine = "SPECTRUM";
            $parseOptions->caseSensitive = true;
            $parseOptions->lineEnding = 0x0D;
            if($parseOptions->outputTZX)
            {
                $tempFilename = pathinfo($parseOptions->outputFilename);
                $parseOptions->outputFilename = $tempFilename['filename'].'.'."tzx";
            }
            break;
        
        case "LAMBDA1" :
            //LAMBDA1 is the same as LAMBDA2, except 2 has extra keywords for Colour,etc
            initLambdaArrays($keywordArray, $characterArray);
            $parseOptions->subMachine = "ZX81";
            $parseOptions->caseSensitive = false;
            $parseOptions->lineEnding = 0x76;
            $parseOptions->outputFormat = "LAMBDA";
            if($parseOptions->outputFilename == DEFAULT_OUTPUT)
                $parseOptions->outputFilename = DEFAULT_OUTPUT_81;
            break;
        
        case "LAMBDA2" :
            initLambda2Arrays($keywordArray, $characterArray);
            $parseOptions->subMachine = "ZX81";
            $parseOptions->caseSensitive = false;
            $parseOptions->lineEnding = 0x76;
            $parseOptions->outputFormat = "LAMBDA";
            if($parseOptions->outputFilename == DEFAULT_OUTPUT)
                $parseOptions->outputFilename = DEFAULT_OUTPUT_81;
            break;
        
        case "NF300" :
            initNF300Arrays($keywordArray, $characterArray);
            $parseOptions->subMachine = "ZX81";
            $parseOptions->caseSensitive = false;
            $parseOptions->lineEnding = 0x76;
            $parseOptions->outputFormat = "LAMBDA";
            if($parseOptions->outputFilename == DEFAULT_OUTPUT)
                $parseOptions->outputFilename = DEFAULT_OUTPUT_81;
            break;
            
        case "ZX80" :
            initZX80Arrays($keywordArray, $characterArray);
            $parseOptions->subMachine = "ZX80";
            $parseOptions->caseSensitive = false;
            $parseOptions->lineEnding = 0x76;
            $parseOptions->outputFormat = "O80";
            if($parseOptions->outputFilename == DEFAULT_OUTPUT)
                $parseOptions->outputFilename = DEFAULT_OUTPUT_80;
            break;
            
        case "ZX81" :
            initZX81Arrays($keywordArray, $characterArray);
            $parseOptions->subMachine = "ZX81";
            $parseOptions->caseSensitive = false;
            $parseOptions->lineEnding = 0x76;
            $parseOptions->outputFormat = "P81";
            if($parseOptions->outputFilename == DEFAULT_OUTPUT)
                $parseOptions->outputFilename = DEFAULT_OUTPUT_81;
            break;
            
        case "NEXT" :
            initZX81Arrays($keywordArray, $characterArray);
            $parseOptions->subMachine = "SPECTRUM";
            $parseOptions->caseSensitive = true;
            $parseOptions->lineEnding = 0x0D;
            if($parseOptions->outputFilename == DEFAULT_OUTPUT)
                $parseOptions->outputFilename = "Output";
            $parseOptions->outputFormat = "DOS";
            break;

        default :
            Error("Invalid machine type specified: Valid machines are SPECTRUM, LAMBDA1, LAMBDA2, ZX80, ZX81, TIMEX, PLUS3, NEXT");
        }
}

function checkOutputFile(&$parseOptions)
{
    $Extension = strrpos($parseOptions->outputFilename,"."); 
    if($Extension===false)
    {
        Warning("Output file name ".$parseOptions->outputFilename." has no extension. Suitable extension being added.");
        switch ($parseOptions->machineType)
        {
            case "SPECTRUM" :
            case "TIMEX" :
                $parseOptions->outputFilename=$parseOptions->outputFilename.".TAP";
                break;
            case "PLUS3" :
            case "NEXT" :
                break;
            case "ZX81" :
            case "NF300" :
            case "LAMBDA1" :
            case "LAMBDA2" :
                $parseOptions->outputFilename=$parseOptions->outputFilename.".P";
                break;
            case "ZX80" :
                $parseOptions->outputFilename=$parseOptions->outputFilename.".O";
                break;
            default:
                Warning("Machine type unknown adding TAP extension");
                $parseOptions->outputFilename=$parseOptions->outputFilename.".TAP";
                break;
        }
    }
    else
    {
        $Extension = strtoupper(substr($parseOptions->outputFilename,$Extension+1));
        switch (strtoupper($Extension))
        {
            case "TAP":
                if(($parseOptions->machineType !== "SPECTRUM") and ($parseOptions->machineType !== "TIMEX") and ($parseOptions->machineType !== "PLUS3") and ($parseOptions->machineType !== "NEXT"))
                    Warning("Unusal file extension ".$Extension." given for machine type ".$parseOptions->machineType.". Trusting you know best.");
                break;
            case "TZX":
                if($parseOptions->machineType !== "SPECTRUM" and $parseOptions->machineType !== "TIMEX")
                    Warning("Unusal file extension ".$Extension." given for machine type ".$parseOptions->machineType.". Trusting you know best.");
                break;
            case "P":
                if($parseOptions->subMachine = "ZX81")
                    Warning("Unusal file extension ".$Extension." given for machine type ".$parseOptions->machineType.". Trusting you know best.");
                break;
            case "O":
                if($parseOptions->machineType !== "ZX80")
                    Warning("Unusal file extension ".$Extension." given for machine type ".$parseOptions->machineType.". Trusting you know best.");
                break;
            default:
                Warning("Unusal file extension ".$Extension." given for machine type ".$parseOptions->machineType.". Trusting you know best.");
                break;
        }
    }
}

function initSpectrumArrays(&$keywordArray, &$characterArray)
{
    $keywordArray = array(
    
        "RND"=>165,
        "INKEY$"=>166,
        "PI"=>167,
        "FN"=>168,
        "POINT"=>169,
        "SCREEN$"=>170,
        "ATTR"=>171,
        "AT"=>172,
        "TAB"=>173,
        "VAL$"=>174,
        "CODE"=>175,
        "VAL"=>176,
        "LEN"=>177,
        "SIN"=>178,
        "COS"=>179,
        "TAN"=>180,
        "ASN"=>181,
        "ACS"=>182,
        "ATN"=>183,
        "LN"=>184,
        "EXP"=>185,
        "INT"=>186,
        "SQR"=>187,
        "SGN"=>188,
        "ABS"=>189,
        "PEEK"=>190,
        "IN"=>191,
        "USR"=>192,
        "STR$"=>193,
        "CHR$"=>194,
        "NOT"=>195,
        "BIN"=>196,
        "OR"=>197,
        "AND"=>198,
        "LINE"=>202,
        "THEN"=>203,
        "TO"=>204,
        "STEP"=>205,
        "DEF FN"=>206,
        "CAT"=>207,
        "FORMAT"=>208,
        "MOVE"=>209,
        "ERASE"=>210,
        "OPEN #"=>211,
        "CLOSE #"=>212,
        "MERGE"=>213,
        "VERIFY"=>214,
        "BEEP"=>215,
        "CIRCLE"=>216,
        "INK"=>217,
        "PAPER"=>218,
        "FLASH"=>219,
        "BRIGHT"=>220,
        "INVERSE"=>221,
        "OVER"=>222,
        "OUT"=>223,
        "LPRINT"=>224,
        "LLIST"=>225,
        "STOP"=>226,
        "READ"=>227,
        "DATA"=>228,
        "RESTORE"=>229,
        "NEW"=>230,
        "BORDER"=>231,
        "CONTINUE"=>232,
        "DIM"=>233,
        "REM"=>234,
        "FOR"=>235,
        "GO TO"=>236,
        "GOTO"=>236,
        "GO SUB"=>237,
        "GOSUB"=>237,
        "INPUT"=>238,
        "LOAD"=>239,
        "LIST"=>240,
        "LET"=>241,
        "PAUSE"=>242,
        "NEXT"=>243,
        "POKE"=>244,
        "PRINT"=>245,
        "PLOT"=>246,
        "RUN"=>247,
        "SAVE"=>248,
        "RANDOMIZE"=>249,
        "RANDOMISE"=>249,
        "IF"=>250,
        "CLS"=>251,
        "DRAW"=>252,
        "CLEAR"=>253,
        "RETURN"=>254,
        "COPY"=>255
        );

        $characterArray = array(
            "PRINT comma"=>6,
            "Edit"=>7,
            "Backspace"=>12,
            "Enter"=>13,
            "number"=>14,
            "Not used"=>15,
            "INK control"=>16,
            "PAPER control"=>17,
            "FLASH control"=>18,
            "BRIGHT control"=>19,
            "INVERSE control"=>20,
            "OVER control"=>21,
            "AT control"=>22,
            "TAB control"=>23,
            "SPACE"=>32,
            " "=>32,
            "!"=>33,
            "\""=>34,
            "#"=>35,
            "$"=>36,
            "%"=>37,
            "&"=>38,
            "'"=>39,
            "("=>40,
            ")"=>41,
            "*"=>42,
            "+"=>43,
            ","=>44,
            "-"=>45,
            "."=>46,
            "/"=>47,
            "0"=>48,
            "1"=>49,
            "2"=>50,
            "3"=>51,
            "4"=>52,
            "5"=>53,
            "6"=>54,
            "7"=>55,
            "8"=>56,
            "9"=>57,
            ":"=>58,
            ";"=>59,
            "<"=>60,
            "="=>61,
            ">"=>62,
            "?"=>63,
            "@"=>64,
            "A"=>65,
            "B"=>66,
            "C"=>67,
            "D"=>68,
            "E"=>69,
            "F"=>70,
            "G"=>71,
            "H"=>72,
            "I"=>73,
            "J"=>74,
            "K"=>75,
            "L"=>76,
            "M"=>77,
            "N"=>78,
            "O"=>79,
            "P"=>80,
            "Q"=>81,
            "R"=>82,
            "S"=>83,
            "T"=>84,
            "U"=>85,
            "V"=>86,
            "W"=>87,
            "X"=>88,
            "Y"=>89,
            "Z"=>90,
            "["=>91,
            "¡"=>91,
            "\\"=>92,
            "Ñ"=>92,
            "]"=>93,
            "¿"=>93,
            "^"=>94,
            "_"=>95,
            "£"=>96,
            "€"=>96,
            "a"=>97,
            "b"=>98,
            "c"=>99,
            "d"=>100,
            "e"=>101,
            "f"=>102,
            "g"=>103,
            "h"=>104,
            "i"=>105,
            "j"=>106,
            "k"=>107,
            "l"=>108,
            "m"=>109,
            "n"=>110,
            "o"=>111,
            "p"=>112,
            "q"=>113,
            "r"=>114,
            "s"=>115,
            "t"=>116,
            "u"=>117,
            "v"=>118,
            "w"=>119,
            "x"=>120,
            "y"=>121,
            "z"=>122,
            "{"=>123,
            "|"=>124,
            "ñ"=>124,
            "}"=>125,
            "~"=>126,
            "©"=>127,
            "BLANKBLANK"=>128,
            "BLANK'"=>129,
            "'BLANK"=>130,
            "''"=>131,
            "BLANK."=>132,
            "BLANK:"=>133,
            "'."=>134,
            "':"=>135,
            ".BLANK"=>136,
            ".'"=>137,
            ":BLANK"=>138,
            ":'"=>139,
            ".."=>140,
            ".:"=>141,
            ":."=>142,
            "::"=>143,
            "(a)"=>144,
            "(b)"=>145,
            "(c)"=>146,
            "(d)"=>147,
            "(e)"=>148,
            "(f)"=>149,
            "(g)"=>150,
            "(h)"=>151,
            "(i)"=>152,
            "(j)"=>153,
            "(k)"=>154,
            "(l)"=>155,
            "(m)"=>156,
            "(n)"=>157,
            "(o)"=>158,
            "(p)"=>159,
            "(q)"=>160,
            "(r)"=>161,
            "(s)"=>162,
            "(t)"=>163,
            "(u)"=>164,
            "<="=>199,
            ">="=>200,
            "<>"=>201);

}

function initTimexArrays(&$keywordArray, &$characterArray)
{
    //Call Spectrum setup and then replace the few keywords that are different
    initSpectrumArrays($keywordArray, $characterArray);
    unset($characterArray["{"]);
    unset($characterArray["|"]);
    unset($characterArray["}"]);
    unset($characterArray["~"]);
    unset($characterArray["©"]);
    $keywordArray += ["ON ERR" => 123, "STICK" => 124, "SOUND" => 125, "FREE" => 126, "RESET" => 127];
}

function initLambdaArrays(&$keywordArray, &$characterArray)
{
    $characterArray = array(
        "BLANKBLANK"=>0,
        " "=>0,
        "'BLANK"=>1,
        "BLANK'"=>2,
        "''"=>3,
        ".BLANK"=>4,
        ":BLANK"=>5,
        ".'"=>6,
        ":'"=>7,
        "!:"=>8, //Car
        ":>"=>9, //Triangle top left
        "<:"=>10, //Triangle bottom right
        "\""=>11,
        "]["=>12, //Spider
        "$"=>13,
        ")("=>14, //Butterfly
        "##"=>15, //Ghost
        "("=>16,
        ")"=>17,
        ">"=>18,
        "<"=>19,
        "="=>20,
        "+"=>21,
        "-"=>22,
        "*"=>23,
        "/"=>24,
        ";"=>25,
        ","=>26,
        "."=>27,
        "0"=>28,
        "1"=>29,
        "2"=>30,
        "3"=>31,
        "4"=>32,
        "5"=>33,
        "6"=>34,
        "7"=>35,
        "8"=>36,
        "9"=>37,
        "A"=>38,
        "a"=>38,
        "B"=>39,
        "b"=>39,
        "C"=>40,
        "c"=>40,
        "D"=>41,
        "d"=>41,
        "E"=>42,
        "e"=>42,
        "F"=>43,
        "f"=>43,
        "G"=>44,
        "g"=>44,
        "H"=>45,
        "h"=>45,
        "I"=>46,
        "i"=>46,
        "J"=>47,
        "j"=>47,
        "K"=>48,
        "k"=>48,
        "L"=>49,
        "l"=>49,
        "M"=>50,
        "m"=>50,
        "N"=>51,
        "n"=>51,
        "O"=>52,
        "o"=>52,
        "P"=>53,
        "p"=>53,
        "Q"=>54,
        "q"=>54,
        "R"=>55,
        "r"=>55,
        "S"=>56,
        "s"=>56,
        "T"=>57,
        "t"=>57,
        "U"=>58,
        "u"=>58,
        "V"=>59,
        "v"=>59,
        "W"=>60,
        "w"=>60,
        "X"=>61,
        "x"=>61,
        "Y"=>62,
        "y"=>62,
        "Z"=>63,
        "z"=>63,
        "UP"=>112,
        "DOWN"=>113,
        "LEFT"=>114,
        "RIGHT"=>115,
        "GRAPHICS"=>116,
        "EDIT"=>117,
        "ENTER"=>118,
        "DELETE"=>119,
        "L MODE"=>120,
        "BREAK"=>121,
        "LINE NO"=>121,
        "NUMBER"=>126,
        "CURSOR"=>127,
        "::"=>128,
        "% "=>128,
        ".:"=>129,
        ":."=>130,
        ".."=>131,
        "':"=>132,
        "BLANK:"=>133,
        "'."=>134,
        "BLANK."=>135,
        ":!"=>136, //Car inverse
        ">:"=>137, //Triangle bottom right
        ":<"=>138, //Triangle bottom left
        "%\""=>139,
        "[]"=>140, //Spider inverse
        "#$"=>141,
        "()"=>142, //Butterfly inverse
        "@@"=>143, //Ghost inverse
        "%("=>144,
        "%)"=>145,
        "%>"=>146,
        "%<"=>147,
        "%="=>148,
        "%+"=>149,
        "%-"=>150,
        "%*"=>151,
        "%/"=>152,
        "%;"=>153,
        "%,"=>154,
        "%."=>155,
        "%0"=>156,
        "%1"=>157,
        "%2"=>158,
        "%3"=>159,
        "%4"=>160,
        "%5"=>161,
        "%6"=>162,
        "%7"=>163,
        "%8"=>164,
        "%9"=>165,
        "%A"=>166,
        "%a"=>166,
        "%B"=>167,
        "%b"=>167,
        "%C"=>168,
        "%c"=>168,
        "%D"=>169,
        "%d"=>169,
        "%E"=>170,
        "%e"=>170,
        "%F"=>171,
        "%f"=>171,
        "%G"=>172,
        "%g"=>172,
        "%H"=>173,
        "%h"=>173,
        "%I"=>174,
        "%i"=>174,
        "%J"=>175,
        "%j"=>175,
        "%K"=>176,
        "%k"=>176,
        "%L"=>177,
        "%l"=>177,
        "%M"=>178,
        "%m"=>178,
        "%N"=>179,
        "%n"=>179,
        "%O"=>180,
        "%o"=>180,
        "%P"=>181,
        "%p"=>181,
        "%Q"=>182,
        "%q"=>182,
        "%R"=>183,
        "%r"=>183,
        "%S"=>184,
        "%s"=>184,
        "%T"=>185,
        "%t"=>185,
        "%U"=>186,
        "%u"=>186,
        "%V"=>187,
        "%v"=>187,
        "%W"=>188,
        "%w"=>188,
        "%X"=>189,
        "%x"=>189,
        "%Y"=>190,
        "%y"=>190,
        "%Z"=>191,
        "%z"=>191,
        "**"=>214,
        "<="=>217,
        ">="=>218,
        "<>"=>219
    );
    $keywordArray = array(
        

        
        "THEN"=>64,
        "TO"=>65,
        "STEP"=>66,
        "RND"=>67,
        "INKEY$"=>68,
        "PI"=>69,
        "CODE"=>192,
        "VAL"=>193,
        "LEN"=>194,
        "SIN"=>195,
        "COS"=>196,
        "TAN"=>197,
        "ASN"=>198,
        "ACS"=>199,
        "ATN"=>200,
        "LOG"=>201,
        "EXP"=>202,
        "INT"=>203,
        "SQR"=>204,
        "SGN"=>205,
        "ABS"=>206,
        "PEEK"=>207,
        "USR"=>208,
        "STR$"=>209,
        "CHR$"=>210,
        "NOT"=>211,
        "AT"=>212,
        "TAB"=>213,
        "OR"=>215,
        "AND"=>216,
        "TEMPO"=>220,
        "MUSIC"=>221,
        "SOUND"=>222,
        "BEEP"=>223,
        "NOBEEP"=>224,
        "LPRINT"=>225,
        "LLIST"=>226,
        "STOP"=>227,
        "SLOW"=>228,
        "FAST"=>229,
        "NEW"=>230,
        "SCROLL"=>231,
        "CONT"=>232,
        "DIM"=>233,
        "REM"=>234,
        "FOR"=>235,
        "GOTO"=>236,
        "GOSUB"=>237,
        "INPUT"=>238,
        "LOAD"=>239,
        "LIST"=>240,
        "LET"=>241,
        "PAUSE"=>242,
        "NEXT"=>243,
        "POKE"=>244,
        "PRINT"=>245,
        "PLOT"=>246,
        "RUN"=>247,
        "SAVE"=>248,
        "RAND"=>249,
        "IF"=>250,
        "CLS"=>251,
        "UNPLOT"=>252,
        "CLEAR"=>253,
        "RETURN"=>254,
        "COPY"=>255,
    );
}
function initLambda2Arrays(&$keywordArray, &$characterArray)
{
    //Call Lambda setup and then replace the few keywords that are different
    initLambdaArrays($keywordArray, $characterArray);
    $keywordArray += ["INK" => 70, "PAPER" => 71, "BORDER" => 72];
}

function initNF300Arrays(&$keywordArray, &$characterArray)
{
    //Call Lambda2 setup and then replace the few keywords that are different
    initLambda2Arrays($keywordArray, $characterArray);
    $keywordArray += ["READ" => 73, "DATA" => 74, "RESTORE" => 75];
}

function initZX81Arrays(&$keywordArray, &$characterArray)
{
    $characterArray = array(
        "BLANKBLANK"=>0,
        " "=>0,
        "'BLANK"=>1,
        "BLANK'"=>2,
        "''"=>3,
        ".BLANK"=>4,
        ":BLANK"=>5,
        ".'"=>6,
        ":'"=>7,
        "##"=>8, //Pixel block
        "~~"=>9, //Pixel top block
        ",,"=>10, //Pixel bottom block
        "\""=>11,
        "£"=>12,
        "$"=>13,
        ":"=>14,
        "?"=>15,
        "("=>16,
        ")"=>17,
        ">"=>18,
        "<"=>19,
        "="=>20,
        "+"=>21,
        "-"=>22,
        "*"=>23,
        "/"=>24,
        ";"=>25,
        ","=>26,
        "."=>27,
        "0"=>28,
        "1"=>29,
        "2"=>30,
        "3"=>31,
        "4"=>32,
        "5"=>33,
        "6"=>34,
        "7"=>35,
        "8"=>36,
        "9"=>37,
        "A"=>38,
        "a"=>38,
        "B"=>39,
        "b"=>39,
        "C"=>40,
        "c"=>40,
        "D"=>41,
        "d"=>41,
        "E"=>42,
        "e"=>42,
        "F"=>43,
        "f"=>43,
        "G"=>44,
        "g"=>44,
        "H"=>45,
        "h"=>45,
        "I"=>46,
        "i"=>46,
        "J"=>47,
        "j"=>47,
        "K"=>48,
        "k"=>48,
        "L"=>49,
        "l"=>49,
        "M"=>50,
        "m"=>50,
        "N"=>51,
        "n"=>51,
        "O"=>52,
        "o"=>52,
        "P"=>53,
        "p"=>53,
        "Q"=>54,
        "q"=>54,
        "R"=>55,
        "r"=>55,
        "S"=>56,
        "s"=>56,
        "T"=>57,
        "t"=>57,
        "U"=>58,
        "u"=>58,
        "V"=>59,
        "v"=>59,
        "W"=>60,
        "w"=>60,
        "X"=>61,
        "x"=>61,
        "Y"=>62,
        "y"=>62,
        "Z"=>63,
        "z"=>63,
        "UP"=>112,
        "DOWN"=>113,
        "LEFT"=>114,
        "RIGHT"=>115,
        "GRAPHICS"=>116,
        "EDIT"=>117,
        "NEWLINE"=>118,
        "RUBOUT"=>119,
        "K/L MODE"=>120,
        "FUNCTION"=>121,
        "NUMBER"=>126,
        "CURSOR"=>127,
        "::"=>128,
        "% "=>128,
        ".:"=>129,
        ":."=>130,
        ".."=>131,
        "':"=>132,
        "BLANK:"=>133,
        "'."=>134,
        "BLANK."=>135,
        "@@"=>136, //Inverse pixel block
        "!!"=>137, //Inverse pixel top
        "::"=>138, //Inverse pixel bottom
        "%\""=>139,
        "%£"=>140,
        "%$"=>141,
        "%:"=>142,
        "%?"=>143,
        "%("=>144,
        "%)"=>145,
        "%>"=>146,
        "%<"=>147,
        "%="=>148,
        "%+"=>149,
        "%-"=>150,
        "%*"=>151,
        "%/"=>152,
        "%;"=>153,
        "%,"=>154,
        "%."=>155,
        "%0"=>156,
        "%1"=>157,
        "%2"=>158,
        "%3"=>159,
        "%4"=>160,
        "%5"=>161,
        "%6"=>162,
        "%7"=>163,
        "%8"=>164,
        "%9"=>165,
        "%A"=>166,
        "%a"=>166,
        "%B"=>167,
        "%b"=>167,
        "%C"=>168,
        "%c"=>168,
        "%D"=>169,
        "%d"=>169,
        "%E"=>170,
        "%e"=>170,
        "%F"=>171,
        "%f"=>171,
        "%G"=>172,
        "%g"=>172,
        "%H"=>173,
        "%h"=>173,
        "%I"=>174,
        "%i"=>174,
        "%J"=>175,
        "%j"=>175,
        "%K"=>176,
        "%k"=>176,
        "%L"=>177,
        "%l"=>177,
        "%M"=>178,
        "%m"=>178,
        "%N"=>179,
        "%n"=>179,
        "%O"=>180,
        "%o"=>180,
        "%P"=>181,
        "%p"=>181,
        "%Q"=>182,
        "%q"=>182,
        "%R"=>183,
        "%r"=>183,
        "%S"=>184,
        "%s"=>184,
        "%T"=>185,
        "%t"=>185,
        "%U"=>186,
        "%u"=>186,
        "%V"=>187,
        "%v"=>187,
        "%W"=>188,
        "%w"=>188,
        "%X"=>189,
        "%x"=>189,
        "%Y"=>190,
        "%y"=>190,
        "%Z"=>191,
        "%z"=>191,
        "“”"=>192,
        "**"=>216,
        "<="=>219,
        ">="=>220,
        "<>"=>221
    );
    $keywordArray = array(
        

        
        "RND"=>64,
        "INKEY$"=>65,
        "PI"=>66,
        "AT"=>193,
        "TAB"=>194,
        "CODE"=>196,
        "VAL"=>197,
        "LEN"=>198,
        "SIN"=>199,
        "COS"=>200,
        "TAN"=>201,
        "ASN"=>202,
        "ACS"=>203,
        "ATN"=>204,
        "LN"=>205,
        "EXP"=>206,
        "INT"=>207,
        "SQR"=>208,
        "SQN"=>209,
        "ABS"=>210,
        "PEEK"=>211,
        "USR"=>212,
        "STR$"=>213,
        "CHR$"=>214,
        "NOT"=>215,
        "OR"=>217,
        "AND"=>218,
        "THEN"=>222,
        "TO"=>223,
        "STEP"=>224,
        "LPRINT"=>225,
        "LLIST"=>226,
        "STOP"=>227,
        "SLOW"=>228,
        "FAST"=>229,
        "NEW"=>230,
        "SCROLL"=>231,
        "CONT"=>232,
        "DIM"=>233,
        "REM"=>234,
        "FOR"=>235,
        "GOTO"=>236,
        "GOSUB"=>237,
        "INPUT"=>238,
        "LOAD"=>239,
        "LIST"=>240,
        "LET"=>241,
        "PAUSE"=>242,
        "NEXT"=>243,
        "POKE"=>244,
        "PRINT"=>245,
        "PLOT"=>246,
        "RUN"=>247,
        "SAVE"=>248,
        "RAND"=>249,
        "IF"=>250,
        "CLS"=>251,
        "UNPLOT"=>252,
        "CLEAR"=>253,
        "RETURN"=>254,
        "COPY"=>255,
    );
}

function initZX80Arrays(&$keywordArray, &$characterArray)
{
    $characterArray = array(
        "BLANKBLANK"=>0,
        " "=>0,
        "\""=>1,
        "BLANK:"=>2,
        ".."=>3,
        "'BLANK"=>4,
        "BLANK'"=>5,
        ".BLANK"=>6,
        "BLANK."=>7,
        ".'"=>8,
        "##"=>9, //Pixel Block
        "~~"=>10, //Pixel Top
        ",,"=>11, //Pixel Bottom
        "£"=>12,
        "$"=>13,
        ":"=>14,
        "?"=>15,
        "("=>16,
        ")"=>17,
        "-"=>18,
        "+"=>19,
        "*"=>20,
        "/"=>21,
        "="=>22,
        ">"=>23,
        "<"=>24,
        ";"=>25,
        ","=>26,
        "."=>27,
        "0"=>28,
        "1"=>29,
        "2"=>30,
        "3"=>31,
        "4"=>32,
        "5"=>33,
        "6"=>34,
        "7"=>35,
        "8"=>36,
        "9"=>37,
        "A"=>38,
        "a"=>38,
        "B"=>39,
        "b"=>39,
        "C"=>40,
        "c"=>40,
        "D"=>41,
        "d"=>41,
        "E"=>42,
        "e"=>42,
        "F"=>43,
        "f"=>43,
        "G"=>44,
        "g"=>44,
        "H"=>45,
        "h"=>45,
        "I"=>46,
        "i"=>46,
        "J"=>47,
        "j"=>47,
        "K"=>48,
        "k"=>48,
        "L"=>49,
        "l"=>49,
        "M"=>50,
        "m"=>50,
        "N"=>51,
        "n"=>51,
        "O"=>52,
        "o"=>52,
        "P"=>53,
        "p"=>53,
        "Q"=>54,
        "q"=>54,
        "R"=>55,
        "r"=>55,
        "S"=>56,
        "s"=>56,
        "T"=>57,
        "t"=>57,
        "U"=>58,
        "u"=>58,
        "V"=>59,
        "v"=>59,
        "W"=>60,
        "w"=>60,
        "X"=>61,
        "x"=>61,
        "Y"=>62,
        "y"=>62,
        "Z"=>63,
        "z"=>63,
        "UP"=>112,
        "DOWN"=>113,
        "LEFT"=>114,
        "RIGHT"=>115,
        "GRAPHICS"=>116,
        "EDIT"=>117,
        "NEL"=>118,
        "RUBOUT"=>119,
        "NUMBER"=>126,
        "CURSOR"=>127,
        "% "=>128,
        "%\""=>129,
        "BLANK:"=>130,
        "''"=>131,
        ".:"=>132,
        ":."=>133,
        "':"=>134,
        ":'"=>135,
        "'."=>136,
        "@@"=>137, //Inverse pixel block
        "!!"=>138, //Inverse pixel top
        "::"=>139, //Inverse pixel bottom
        "%£"=>140,
        "%$"=>141,
        "%:"=>142,
        "%?"=>143,
        "%("=>144,
        "%)"=>145,
        "%-"=>146,
        "%+"=>147,
        "%*"=>148,
        "%/"=>149,
        "%="=>150,
        "%>"=>151,
        "%<"=>152,
        "%;"=>153,
        "%,"=>154,
        "%."=>155,
        "%0"=>156,
        "%1"=>157,
        "%2"=>158,
        "%3"=>159,
        "%4"=>160,
        "%5"=>161,
        "%6"=>162,
        "%7"=>163,
        "%8"=>164,
        "%9"=>165,
        "%A"=>166,
        "%a"=>166,
        "%B"=>167,
        "%b"=>167,
        "%C"=>168,
        "%c"=>168,
        "%D"=>169,
        "%d"=>169,
        "%E"=>170,
        "%e"=>170,
        "%F"=>171,
        "%f"=>171,
        "%G"=>172,
        "%g"=>172,
        "%H"=>173,
        "%h"=>173,
        "%I"=>174,
        "%i"=>174,
        "%J"=>175,
        "%j"=>175,
        "%K"=>176,
        "%k"=>176,
        "%L"=>177,
        "%l"=>177,
        "%M"=>178,
        "%m"=>178,
        "%N"=>179,
        "%n"=>179,
        "%O"=>180,
        "%o"=>180,
        "%P"=>181,
        "%p"=>181,
        "%Q"=>182,
        "%q"=>182,
        "%R"=>183,
        "%r"=>183,
        "%S"=>184,
        "%s"=>184,
        "%T"=>185,
        "%t"=>185,
        "%U"=>186,
        "%u"=>186,
        "%V"=>187,
        "%v"=>187,
        "%W"=>188,
        "%w"=>188,
        "%X"=>189,
        "%x"=>189,
        "%Y"=>190,
        "%y"=>190,
        "%Z"=>191,
        "%z"=>191,
        "“”"=>212,
        ";"=>215,
        "'"=>216,
        ")"=>217,
        "("=>218,
        "-"=>220,
        "+"=>221,
        "*"=>222,
        "/"=>223,
        "**"=>226,
        "="=>227,
        ">"=>228,
        "<"=>229
    );
    $keywordArray = array(
        "THEN"=>213,
        "TO"=>214,
        "NOT"=>219,
        "AND"=>224,
        "OR"=>225,
        "LIST"=>230,
        "RETURN"=>231,
        "CLS"=>232,
        "DIM"=>233,
        "SAVE"=>234,
        "FOR"=>235,
        "GOTO"=>236,
        "GO TO"=>236,
        "POKE"=>237,
        "INPUT"=>238,
        "RANDOMISE"=>239,
        "RANDOMIZE"=>239,
        "NEXT"=>243,
        "PRINT"=>244,
        "NEW"=>246,
        "RUN"=>247,
        "STOP"=>248,
        "CONTINUE"=>249,
        "IF"=>250,
        "GOSUB"=>251,
        "GO SUB"=>251,
        "LOAD"=>252,
        "CLEAR"=>253,
        "REM"=>254
    );
}

function initNextArrays(&$keywordArray, &$characterArray)
{
    $keywordArray = array(
    
        "DPEEK"=>138,
        "MOD"=>139,
        "UNTIL"=>142,
        "ERROR"=>143,
        "ON"=>144,
        "DEFPROC"=>145,
        "ENDPROC"=>146,
        "PROC"=>147,
        "LOCAL"=>148,
        "DRIVER"=>149,
        "WHILE"=>150,
        "REPEAT"=>151,
        "ELSE"=>152,
        "REMOUNT"=>153,
        "BANK"=>154,
        "TILE"=>155,
        "LAYER"=>156,
        "PALETTE"=>157,
        "SPRITE"=>158,
        "PWD"=>159,
        "CD"=>160,
        "MKDIR"=>161,
        "RMDIR"=>162,
        "SPECTRUM"=>163,
        "PLAY"=>164,
        "RND"=>165,
        "INKEY$"=>166,
        "PI"=>167,
        "FN"=>168,
        "POINT"=>169,
        "SCREEN$"=>170,
        "ATTR"=>171,
        "AT"=>172,
        "TAB"=>173,
        "VAL$"=>174,
        "CODE"=>175,
        "VAL"=>176,
        "LEN"=>177,
        "SIN"=>178,
        "COS"=>179,
        "TAN"=>180,
        "ASN"=>181,
        "ACS"=>182,
        "ATN"=>183,
        "LN"=>184,
        "EXP"=>185,
        "INT"=>186,
        "SQR"=>187,
        "SGN"=>188,
        "ABS"=>189,
        "PEEK"=>190,
        "IN"=>191,
        "USR"=>192,
        "STR$"=>193,
        "CHR$"=>194,
        "NOT"=>195,
        "BIN"=>196,
        "OR"=>197,
        "AND"=>198,
        "LINE"=>202,
        "THEN"=>203,
        "TO"=>204,
        "STEP"=>205,
        "DEF FN"=>206,
        "CAT"=>207,
        "FORMAT"=>208,
        "MOVE"=>209,
        "ERASE"=>210,
        "OPEN #"=>211,
        "CLOSE #"=>212,
        "MERGE"=>213,
        "VERIFY"=>214,
        "BEEP"=>215,
        "CIRCLE"=>216,
        "INK"=>217,
        "PAPER"=>218,
        "FLASH"=>219,
        "BRIGHT"=>220,
        "INVERSE"=>221,
        "OVER"=>222,
        "OUT"=>223,
        "LPRINT"=>224,
        "LLIST"=>225,
        "STOP"=>226,
        "READ"=>227,
        "DATA"=>228,
        "RESTORE"=>229,
        "NEW"=>230,
        "BORDER"=>231,
        "CONTINUE"=>232,
        "DIM"=>233,
        "REM"=>234,
        "FOR"=>235,
        "GO TO"=>236,
        "GOTO"=>236,
        "GO SUB"=>237,
        "GOSUB"=>237,
        "INPUT"=>238,
        "LOAD"=>239,
        "LIST"=>240,
        "LET"=>241,
        "PAUSE"=>242,
        "NEXT"=>243,
        "POKE"=>244,
        "PRINT"=>245,
        "PLOT"=>246,
        "RUN"=>247,
        "SAVE"=>248,
        "RANDOMIZE"=>249,
        "RANDOMISE"=>249,
        "IF"=>250,
        "CLS"=>251,
        "DRAW"=>252,
        "CLEAR"=>253,
        "RETURN"=>254,
        "COPY"=>255
        );

        $characterArray = array(
            "PRINT comma"=>6,
            "Edit"=>7,
            "Backspace"=>12,
            "Enter"=>13,
            "number"=>14,
            "Not used"=>15,
            "INK control"=>16,
            "PAPER control"=>17,
            "FLASH control"=>18,
            "BRIGHT control"=>19,
            "INVERSE control"=>20,
            "OVER control"=>21,
            "AT control"=>22,
            "TAB control"=>23,
            "SPACE"=>32,
            " "=>32,
            "!"=>33,
            "\""=>34,
            "#"=>35,
            "$"=>36,
            "%"=>37,
            "&"=>38,
            "'"=>39,
            "("=>40,
            ")"=>41,
            "*"=>42,
            "+"=>43,
            ","=>44,
            "-"=>45,
            "."=>46,
            "/"=>47,
            "0"=>48,
            "1"=>49,
            "2"=>50,
            "3"=>51,
            "4"=>52,
            "5"=>53,
            "6"=>54,
            "7"=>55,
            "8"=>56,
            "9"=>57,
            ":"=>58,
            ";"=>59,
            "<"=>60,
            "="=>61,
            ">"=>62,
            "?"=>63,
            "@"=>64,
            "A"=>65,
            "B"=>66,
            "C"=>67,
            "D"=>68,
            "E"=>69,
            "F"=>70,
            "G"=>71,
            "H"=>72,
            "I"=>73,
            "J"=>74,
            "K"=>75,
            "L"=>76,
            "M"=>77,
            "N"=>78,
            "O"=>79,
            "P"=>80,
            "Q"=>81,
            "R"=>82,
            "S"=>83,
            "T"=>84,
            "U"=>85,
            "V"=>86,
            "W"=>87,
            "X"=>88,
            "Y"=>89,
            "Z"=>90,
            "["=>91,
            "¡"=>91,
            "\\"=>92,
            "Ñ"=>92,
            "]"=>93,
            "¿"=>93,
            "^"=>94,
            "_"=>95,
            "£"=>96,
            "€"=>96,
            "a"=>97,
            "b"=>98,
            "c"=>99,
            "d"=>100,
            "e"=>101,
            "f"=>102,
            "g"=>103,
            "h"=>104,
            "i"=>105,
            "j"=>106,
            "k"=>107,
            "l"=>108,
            "m"=>109,
            "n"=>110,
            "o"=>111,
            "p"=>112,
            "q"=>113,
            "r"=>114,
            "s"=>115,
            "t"=>116,
            "u"=>117,
            "v"=>118,
            "w"=>119,
            "x"=>120,
            "y"=>121,
            "z"=>122,
            "{"=>123,
            "|"=>124,
            "ñ"=>124,
            "}"=>125,
            "~"=>126,
            "©"=>127,
            "BLANKBLANK"=>128,
            "BLANK'"=>129,
            "'BLANK"=>130,
            "''"=>131,
            "BLANK."=>132,
            "BLANK:"=>133,
            "'."=>134,
            "':"=>135,
            ".BLANK"=>136,
            ".'"=>137,
            ":BLANK"=>138,
            ":'"=>139,
            ".."=>140,
            ".:"=>141,
            ":."=>142,
            "::"=>143,
            "(a)"=>144,
            "(b)"=>145,
            "(c)"=>146,
            "(d)"=>147,
            "(e)"=>148,
            "(f)"=>149,
            "(g)"=>150,
            "(h)"=>151,
            "(i)"=>152,
            "(j)"=>153,
            "(k)"=>154,
            "(l)"=>155,
            "(m)"=>156,
            "(n)"=>157,
            "(o)"=>158,
            "(p)"=>159,
            "(q)"=>160,
            "(r)"=>161,
            "(s)"=>162,
            "(t)"=>163,
            "(u)"=>164,
            "<="=>199,
            "=>"=>200,
            "<>"=>201,
            "<<"=>140,
            ">>"=>141);
}
//-------------------------------------------------End Init Functions--------------------------------------------------
//-------------------------------------------------MAIN----------------------------------------------------------------




// Parse optional parameters
$parseOptions= new stdClass();
$parseOptions->verboseMode = false;
$parseOptions->autostartLine = 0x8000;
$parseOptions->spectrumFilename = "out.tap";
$parseOptions->outputFilename = DEFAULT_OUTPUT;
$parseOptions->outputFileExtension = DEFAULT_EXTENSION;
$parseOptions->zx81Mode = false;
$parseOptions->outputTapeMode = true;
$parseOptions->outputRawFileMode = false;
$parseOptions->outputTZX = false;
$parseOptions->outputPlus3DOSFileMode = false;
$parseOptions->useLabels = false;
$parseOptions->setLabelModeIncrement = 2;
$parseOptions->setLabelsModeStartLineNumber = 10;
$parseOptions->inputFilename = "default.txt";
$parseOptions->outputFormat = "TAP";
$parseOptions->machineType = "SPECTRUM"; //This would allow for greater range of machine types SPECTRUM16K,SPECTRUM48K,LAMBDA1,LAMBDA2,etc for users to code for
$parseOptions->subMachine = "SPECTRUM"; //While internally the submachine treats them alike where needed
$parseOptions->caseSensitive = true; //ZX80,ZX81 and Lambda only do upper case, these would be false
$parseOptions->firstLineNum = -1;
$parseOptions->full_D_FILE = true;
$parseOptions->D_FILE = array();
if ($_SERVER["REQUEST_METHOD"] == "POST") 
{
    parsePostOptions($_POST, $parseOptions);
}
else
{
    if(PHP_SAPI === 'cli')
    {
        // Check params need at least arg[0] the php file name and arg[1] the input file nmae
        if (sizeof($argv) < 2) usageHelp();
        parseCliOptions($argv, 2, $parseOptions);
    }
    else{
    
        parseURLOptions($parseOptions);
    }
}

//Setup the keywords
$sinclairBasicKeywords = array();
$sinclairBasic = array();
initArrays($sinclairBasicKeywords, $sinclairBasic, $parseOptions);
checkOutputFile($parseOptions);

if($parseOptions->verboseMode)echo "Machine Type = ".$parseOptions->machineType." Sub machine type = ".$parseOptions->subMachine.PHP_EOL;

//SaNiTy checks

if($parseOptions->useLabels && ($parseOptions->setLabelModeIncrement < 1) || ($parseOptions->setLabelModeIncrement > 1000))
{
    Error("Label line incr. must be in the range 1 to 1000.");
}

if((!$parseOptions->autostartLine == 0x8000) && (($parseOptions->autostartLine < 0) || ($parseOptions->autostartLine > 9999)))
{
    Error("Autostart line number must be in the range 1 to 9999.");
}

// Check input file exists
if($parseOptions->verboseMode)
    echo "Inputfile name is : ".$parseOptions->inputFilename. " ".PHP_EOL;
if (!file_exists($parseOptions->inputFilename)) Error('File not found');

$basicLines = file($parseOptions->inputFilename, FILE_IGNORE_NEW_LINES);
//Open input file and add all the lines to $basicLines array
//$fp = @fopen($parseOptions->inputFilename, 'r'); 
if($parseOptions->verboseMode)echo "Opening file";
// Add each line to an array
/*if ($fp) {
   $basicLines = explode("\n", fread($fp, filesize($parseOptions->inputFilename)));
   //$basicLines = explode(0x0A, fread($fp, filesize($parseOptions->inputFilename)));
}*/
if($parseOptions->verboseMode)
{
echo "Input file contains: ",count($basicLines)," lines",PHP_EOL;
    foreach ($basicLines as $x)
    {
        echo "$x",PHP_EOL;
    }
}

//------------------------------------- Start Parsing File---------------------------------

//var_dump($basicLines);
$Linenum=0;


$currentLineNum=0;
$TempBuffer = [];
$TempString = "";
$unSetLines=false;

//First pass clean up input and sort labels
if($parseOptions->useLabels) $Linenum=$parseOptions->setLabelsModeStartLineNumber;
$LabelNumberArray = [];
foreach ($basicLines as $CurrentLine)
{
    
    $TempBuffer = [];
    if($parseOptions->verboseMode)echo "Current line number: ",$currentLineNum," Current Linenumber to write: ",$Linenum,PHP_EOL;
    $Ptr = 0;   
    //Delete all empty lines
    if (strlen(trim($CurrentLine)) === 0) 
    {
        unset( $basicLines[$currentLineNum] );
        if($parseOptions->verboseMode)echo "Unset: ",$currentLineNum,PHP_EOL;
        $currentLineNum++;
        continue;
    }

    //TO DO
    //If last character of the line equals \\ then append next line to this one and delete next line
    if($CurrentLine[strlen($CurrentLine)-1] == '\\')
    {
        $basicLines[$currentLineNum] = rtrim($basicLines[$currentLineNum]," \n\r\t\v\x00\\") . ltrim($basicLines[$currentLineNum+1]);
        unset( $basicLines[$currentLineNum+1] );
        if($parseOptions->verboseMode)echo "Split line detected. next line unset: ",$currentLineNum,PHP_EOL;
    }

    //Walk the line skipping empty spaces 
    while(ctype_space($CurrentLine[$Ptr])) $Ptr++;
    switch ($CurrentLine[$Ptr])
    {
        case "1":
        case "2":
        case "3":
        case "4":
        case "5":
        case "6":
        case "7":
        case "8":
        case "9":
            if($parseOptions->useLabels)
            {
                //Line number used when in label mode
                Error("Line: " . $currentLineNum . " Line number used in label mode.");
                //Kind of unnecessary as we exited above already
                break;
            }
            //If a number and not using labels we are done with processing this line
            else break;
        case "#": 
            //Delete lines starting with # as they are shell comments
            if($CurrentLine[$Ptr+1] == "!")
            {
                //If a line starts with '#!', it is a special comment that can define additional properties for the P-File generation.

                //If you don't use any '#!' in your BASIC program, a P-File is created that contains the BASIC program, but an empty DFILE (blank screen). When you load the P-File, it is loaded but not started.
                
                //To make the program automatically start, you need to add a special comment like:
                
                #!basic-start:100
                
                //which will start the BASIC program at line 100 after loading.
                
                //With lines like:
                
                //#!dfile:
                //#!dfile:   HELLO
                //#!dfile:   WORLD
                
                //you would create a DFILE with the text "HELLO" "WORLD" spanning over two lines, starting at the second line and the third column. You can omit the trailing (unused) DFILE lines. When converting, they are appended automatically.
                //By default, an expanded DFILE is created. If you want to use a collapsed DFILE, you can add:
                
                //#!dfile-collapsed:

                //It is also possible to write code that only appears for a certain target

                //#!machine:ZX81

                //Would include all code until

                //#!end-machine:

                //Multiple machines can be listed

                //#!machine:ZX81,LAMBDA1, LAMBDA2,ZX80

                //Additionally to exclude code for a certain machine

                //#!machine-not:ZX81

                //#!warning: Warning message

                //Batoco gives a warning message

                //#!error: Error message

                //Batoco gives an error message and quits

                //These comments are not nestable
                $Ptr=strpos($CurrentLine,":");
                $TempBuffer=strtoupper(substr($CurrentLine,2,$Ptr-2));
                
                if($parseOptions->verboseMode)echo "Found PreProcessor token ".$TempBuffer." in Line No. ".$currentLineNum.PHP_EOL;
                switch($TempBuffer)
                {
                    case "BASIC-START":
                        if($parseOptions->autostartLine != 0x8000)
                        { 
                            Warning("AutoStart line set by parameters, about to be overridden in Line no".$currentLineNum);
                        }
                        if($parseOptions->useLabels)
                        {
                            //If using labels store label and we will adjust it once we have processed all labels
                            $parseOptions->autostartLine=substr($CurrentLine,$Ptr+1);
                        }
                        else
                        {
                            //If not using labels store the line number value
                            $parseOptions->autostartLine=(int)substr($CurrentLine,$Ptr+1);
                        }
                        //Set $parseOptions->autostartLine to number given
                        if($parseOptions->verboseMode)echo "Setting autostart line, Line No. ".$currentLineNum.PHP_EOL;
                        break;
                    case "DFILE":
                        //Add contents after : to a new line of the D_FILE
                        $Ptr++; //Skip :
                        $parseOptions->full_D_FILE = true;
                        //Clear TempBuffer
                        $TempBuffer=[];
                        
                        while($Ptr < strlen($CurrentLine))
                        {
                            $TempBuffer[] = $sinclairBasic[$CurrentLine[$Ptr]];
                            $Ptr++;
                        }
                        //Check line length
                        if(count($TempBuffer) > 32)
                            Error("Line is too long for D_FILE line. Line No. ".$currentLineNum);
                        
                        //Pad line to 32 characters
                        $TempBuffer = array_pad($TempBuffer,32,$sinclairBasic[" "]);
                        //Add line to D_FILE
                        $parseOptions->D_FILE[] = $TempBuffer;

                        if($parseOptions->verboseMode)echo "Adding line '".implode($TempBuffer)."' to D_FILE, Line No. ".$currentLineNum.PHP_EOL;
                        break;
                    case "DFILE-COLLAPSED": 
                        //Check if D_FILE is actually empty if not warn that d_file is not empty
                        if(!empty($parseOptions->D_FILE))
                            Warning("D_FILE collapse requested, but D_FILE has content. Request ignored, Line No. ".$currentLineNum);
                        else
                            //Set D_FILE collapsed
                            $parseOptions->full_D_FILE = false;
                        
                        if($parseOptions->verboseMode)echo "Collapsing D_FILE, Line No. ".$currentLineNum.PHP_EOL;
                        break;
                    case "MACHINE":
                        //Get list of machines
                        $MachineList=explode(",",strtoupper(substr($CurrentLine,$Ptr+1)));
                        //var_dump($MachineList);
                        //Check if current machine is in specified list
                        if(in_array($parseOptions->machineType,$MachineList))
                        {
                            if($parseOptions->verboseMode)echo "Machine in include list Line No. ".$currentLineNum.PHP_EOL;
                            //For an include case just need to remove end-machine, so nothing more to do
                            if($parseOptions->verboseMode)echo "Machine Type is ".$parseOptions->machineType.". In array, Line No. ".$currentLineNum.PHP_EOL;
                            $unSetLines=false;
                        }
                        else
                        {
                            //Need to unset everything up to end-machine
                            $unSetLines=true;
                        }
                        //Need to remember to remove end-machine line from listing
                        break;
                    case "MACHINE-NOT":
                        //Get list of machines
                        $MachineList=explode(",",strtoupper(substr($CurrentLine,$Ptr+1)));
                        //var_dump($MachineList);
                        if(!in_array($parseOptions->machineType,$MachineList))
                        {
                            if($parseOptions->verboseMode)echo "Machine in exclude list Line No. ".$currentLineNum.PHP_EOL;
                            
                            if($parseOptions->verboseMode)echo "Machine Type is ".$parseOptions->machineType.". Not in array, Line No. ".$currentLineNum.PHP_EOL;
                            //For a not case unset everyline until end-machine
                            $unSetLines=false;
                        }
                        else
                        {
                            $unSetLines=true;
                        }
                        break;
                    case "END-MACHINE":
                        $unSetLines = false;
                        if($parseOptions->verboseMode)echo "Machine Type is ".$parseOptions->machineType.". End machine, Line No. ".$currentLineNum.PHP_EOL;
                        break;
                    case "WARNING":
                        if(!$unSetLines)
                            Warning(substr($CurrentLine,$Ptr+1));
                        break;
                    case "ERROR":
                        if(!$unSetLines)
                            Error(substr($CurrentLine,$Ptr+1));
                        break;
                }
                //Now remove line from listing
                unset( $basicLines[$currentLineNum] );
                if($parseOptions->verboseMode)echo "Unsetting Preprocessor line. Line No. ",$currentLineNum,PHP_EOL;
            }
            else
            {
                unset( $basicLines[$currentLineNum] );
                if($parseOptions->verboseMode)echo "Unsetting comment line. Line No. ",$currentLineNum,PHP_EOL;
            }
            
            $currentLineNum++;
            continue 2;
        case "@":
            //If using labels we now have a label that needs to be assigned a number
            if($parseOptions->useLabels)
            {
                $TempPos = strpos($CurrentLine,":");
                if($TempPos)
                {
                    if($TempPos < $Ptr)
                    {
                        Error("Line: " . $currentLineNum . "Label end tag ':' occurs before label start tag '@'");
                    }
                }
                else
                {
                    Error("Line: " . $currentLineNum . "Incomplete token definition, label should close with an end tag ':'");
                }
                $TempBuffer[] = $CurrentLine[$Ptr];
                $Ptr++;
                while(!ctype_punct($CurrentLine[$Ptr]))
                {
                    $TempBuffer[] = $CurrentLine[$Ptr];
                    $Ptr++;
                }
                //Check if label has been used before
                if (array_key_exists(implode($TempBuffer), $LabelNumberArray))
                    Error("Line: " . $currentLineNum . "Attempt to redefine label" . implode($TempBuffer));
                $LabelNumberArray[implode($TempBuffer)] = $Linenum;
                $TempBuffer = [];
            }
            //Now delete line whether we are using labels or not
            unset( $basicLines[$currentLineNum] );
            


            break;
        default:
            // If using labels add line number
            if($parseOptions->useLabels)
            {
                $TempString = (string)$Linenum . " " . $CurrentLine;
                $basicLines[$currentLineNum] = $TempString;
                
                if($parseOptions->verboseMode)echo "Processed: ",$currentLineNum,PHP_EOL;
                //If using labels increment linenumber
                if($parseOptions->useLabels) $Linenum = $Linenum+$parseOptions->setLabelModeIncrement; 
            }
    }
    //If we are here it should be a line of code check if it needs deleting
    if($unSetLines==true)
    {
        unset( $basicLines[$currentLineNum] );
        if($parseOptions->verboseMode)echo "Wrong machine type, unsetting line. Line No. ",$currentLineNum,PHP_EOL;
    }
    $currentLineNum++;
}
$basicLines = array_values( $basicLines );
//If using labels see if we have a startline
if($parseOptions->useLabels)
{
    if($parseOptions->autostartLine != 0x8000)
    {
        if(in_array($parseOptions->autostartLine,$LabelNumberArray))
            $parseOptions->autostartLine=$LabelNumberArray[$parseOptions->autostartLine];
        else
            Error("Auto start line either contains unknown label or was set on commandline while using label mode.");
    }
}

echo "\n\n";
$currentLineNum = 0;
//We now need a second pass to replace labels with line numbers
if($parseOptions->useLabels)
{
    $Ptr = 0; 
    foreach ($basicLines as $CurrentLine)
    {
        $basicLines[$currentLineNum] = strReplaceAssoci($LabelNumberArray,$CurrentLine);
        $currentLineNum++;
    } 
}

$currentLineNum = 0;
$Linenum = 0;
$TempPtr = 0;
//var_dump($basicLines);

//Open output file
$OutputFile = fopen($parseOptions->outputFilename,"w") or die("Unable to open file!");

echo ">";
foreach ($basicLines as $CurrentLine)
{
    
    echo "=";
    $currentLineNum++;
    $LastLinenum=$Linenum;
    
    //Create TempOutputLine String to hold the contents to write to the output file.
    $TempBuffer=[];
    //Skip Empty Lines, containing only spaces, tabs or newlines
    if (strlen(trim($CurrentLine)) === 0) continue;
    $CurrentLineLength = strlen($CurrentLine);
    $Ptr = 0;    
    // get line number

        //Skip empty space at start of line
        while(ctype_space($CurrentLine[$Ptr])) $Ptr++;
        if(!ctype_digit($CurrentLine[$Ptr]))
        {
            Error("line: " . $currentLineNum ." missing line number");
        }
        //Keep reading until not a digit
        for($Ptr; $Ptr < $CurrentLineLength; $Ptr++)
        {
            if(ctype_digit($CurrentLine[$Ptr]))
                $TempBuffer[] = $CurrentLine[$Ptr];
            else
                break;
        }
        //Convert array to string then cast to an integer
        $Linenum = (int)implode("",$TempBuffer);
        if($parseOptions->verboseMode)echo "Found line number: ",$Linenum,PHP_EOL;
        //Clear temp buffer
        $TempBuffer = [];
        //Check line number has increased
        if($Linenum<=$LastLinenum)
        {
            Error("line: " . $currentLineNum . " line no. not greater than previous one");
        }
    //SaNiTy check
    if($Linenum<0 || $Linenum>9999)
    {
          Error("line: ".$currentLineNum." line no. out of range, should be 1 - 9999, found ".$Linenum.PHP_EOL);
    }

        //TO DO
    //If ZX81 Before writing line number check if it matches autostart line
    //If so record current size of output and store that in $parseOptions->autostartLine + programs start address
    if($Linenum === $parseOptions->autostartLine and $parseOptions->subMachine = "ZX81")
    {
        if($parseOptions->verboseMode)echo "Found auto start line number: ",$Linenum,PHP_EOL;
    }

    //Write line number to buffer, MSB first, LSB second
    $TempBuffer[] = ($Linenum  & 0xff00) >> 8;
    $TempBuffer[] = $Linenum & 0xff;
    
    // Remember the first line number to use when writing the sysvars
    if ($parseOptions->firstLineNum == -1) $parseOptions->firstLineNum = $Linenum;

    //Now add 2 empty indexes to fill later with the line length unless machine is ZX80 which doesn't store the line length
    if($parseOptions->machineType != "ZX80")
    {
        $TempBuffer[] = 0;
        $TempBuffer[] = 0;
    }

    //Skip empty space before first keyword
    while(ctype_space($CurrentLine[$Ptr])) $Ptr++;
    //Start line by saying we are not in a string
    $InString = false;
    //Now parse the rest of the line
    while($Ptr < strlen($CurrentLine))
    {
        //Deal with strings
        if($CurrentLine[$Ptr] == "\"")
        {
            $InString = !$InString;
            //Add opening/closing string quotes
            $TempBuffer[] = $sinclairBasic["\""];
            $Ptr++;
            continue;
        }
        if($InString)
        {
            //Deal with blocks, escape characters here here
            if($CurrentLine[$Ptr] == "\\")
            {
                switch($parseOptions->subMachine)
                {
                    case "SPECTRUM":
                        if(ctype_alpha($CurrentLine[$Ptr+1]) && !strchr("VWXYZvwxyz",$CurrentLine[$Ptr+1]))
                        {
                            $TempBuffer[] = $sinclairBasic["(".$CurrentLine[$Ptr+1].")"];
                            $Ptr=$Ptr+2;
                            continue 2;
                        }
                        else //Now UDGS are out of the way, lets check for other escape characters
                        {
                            switch($CurrentLine[$Ptr+1])
                            {
                                //case "\\":
                                case "@":
                                    $TempBuffer[] = $sinclairBasic[$CurrentLine[$Ptr+1]];
                                    $Ptr = $Ptr + 2;
                                    continue 3;
                                    break; 
                                case "*": // copyright symbol
                                    $TempBuffer[] = $sinclairBasic["@"];
                                    $Ptr = $Ptr + 2;
                                    continue 3;
                                    break;
                                case '\'': case '.': case ':': case ' ': // block graphics char
                                    $TempBuffer[] = $sinclairBasic[($CurrentLine[$Ptr+1]==" "?"BLANK":$CurrentLine[$Ptr+1]).($CurrentLine[$Ptr+2]==" "?"BLANK":$CurrentLine[$Ptr+2])];
                                    $Ptr = $Ptr + 3;
                                    continue 3;
                                    break;
                                default:
                                    Warning("line: " . $currentLineNum .  "unknown escape charater  " . $CurrentLine[$Ptr] . $CurrentLine[$Ptr+1] . " inserting literally");
                                    $TempBuffer[] =  $CurrentLine[$Ptr];
                                    $TempBuffer[] =  $CurrentLine[$Ptr+1];
                                    $Ptr = $Ptr + 2;
                                    continue 3;
                                    break;
                            }
                        }
                        break;
                    case "ZX80" : case "ZX81" :
                        switch($CurrentLine[$Ptr+1])
                        {
                            //case "\\":
                            case "%": //Inverse characters
                                $TempBuffer[] = $sinclairBasic[$CurrentLine[$Ptr+1].$CurrentLine[$Ptr+2]];
                                $Ptr = $Ptr + 3;
                                continue 3;
                                break; 
                            case '\'': case '.': case ':': case ' ': case "'": case '!': case '>': case '<':case '#':case '~':case ')': case '(':case '@':// block graphics char
                                //echo ($CurrentLine[$Ptr+1]==" "?"BLANK":$CurrentLine[$Ptr+1]).($CurrentLine[$Ptr+2]==" "?"BLANK":$CurrentLine[$Ptr+2])." returns ".$sinclairBasic[($CurrentLine[$Ptr+1]==" "?"BLANK":$CurrentLine[$Ptr+1]).($CurrentLine[$Ptr+2]==" "?"BLANK":$CurrentLine[$Ptr+2])].PHP_EOL;
                                $TempBuffer[] = $sinclairBasic[($CurrentLine[$Ptr+1]==" "?"BLANK":$CurrentLine[$Ptr+1]).($CurrentLine[$Ptr+2]==" "?"BLANK":$CurrentLine[$Ptr+2])];
                                $Ptr = $Ptr + 3;
                                continue 3;
                                break;
                        }
                        break;

                }
                
            }
            if($parseOptions->caseSensitive == true)
            {
                if(array_key_exists($CurrentLine[$Ptr],$sinclairBasic))
                {
                    $TempBuffer[] = $sinclairBasic[$CurrentLine[$Ptr]];

                }
            }
            else
            {
                if(array_key_exists(strtoupper($CurrentLine[$Ptr]),$sinclairBasic))
                    $TempBuffer[] = $sinclairBasic[strtoupper($CurrentLine[$Ptr])];

            }
        }
        if($Ptr == strlen($CurrentLine))
            continue;
        
        //If not in a string then anything starting with a character is either a keyword or variable
        if((ctype_alpha($CurrentLine[$Ptr])) && !$InString && ($Ptr != strlen($CurrentLine)-1) ) 
        {
            $TextBuffer="";
            $TextBuffer2="";
            if($Ptr < strlen($CurrentLine))
            {
                //Need to get all alphabetical characters and $ to allow for SCREEN$,VAL$, etc and String variables
                while($Ptr < strlen($CurrentLine) and (ctype_alpha($CurrentLine[$Ptr])) or ($CurrentLine[$Ptr] == "$"))
                {
                    $TextBuffer=$TextBuffer . $CurrentLine[$Ptr];
                    /*if($Ptr < strlen($CurrentLine)-1)
                        $Ptr++;
                    else
                        continue;*/
                    //The code commented out above should prevent out of range errors which it does
                    //However it generates an extra character on the end of the line after $ characters
                    //For now we just $Ptr++
                    $Ptr++;
                }

            }
            
            if($parseOptions->verboseMode)echo "Current Buffer State: ".strtoupper($TextBuffer).PHP_EOL;
            //Check if REM, if so record keyword and then transfer everything after REM to $TempBuffer and continue
            if(strtoupper($TextBuffer) == "REM")
            {
                $TempBuffer[] =  $sinclairBasicKeywords[strtoupper($TextBuffer)];
                //Eat one more space
                $Ptr++;
                while($Ptr < strlen($CurrentLine))
                {
                    $TempBuffer[] = $sinclairBasic[$CurrentLine[$Ptr]];
                    if($parseOptions->verboseMode)echo "Current Character = ".$sinclairBasic[$CurrentLine[$Ptr]].PHP_EOL;
                    $Ptr++;
                }
                if($parseOptions->verboseMode)echo "Found keyword: ".strtoupper($TextBuffer).PHP_EOL;
                //Now jump to next line
                continue;
            }
            //Check if it is GO, if so keep GOing for TO or SUB
            if(strcasecmp($TextBuffer,"GO") == 0)
            {
                
                //Set TempPtr to current location we will need this to go back to if it is a variable named GO
                $TempPtr = $Ptr;
                $Ptr++;
                while((!ctype_alpha($CurrentLine[$Ptr])))
                {
                    
                    $Ptr++;
                    if($Ptr >= strlen($CurrentLine))
                        break;
                }

                while((ctype_alpha($CurrentLine[$Ptr])))
                {
                    $TextBuffer2=$TextBuffer2 . $CurrentLine[$Ptr];
                    $Ptr++;
                }
                if((strcasecmp($TextBuffer2,"TO" == 0)) or (strcasecmp($TextBuffer2,"SUB" == 0)))
                {
                    $TextBuffer=$TextBuffer . $TextBuffer2;
                    if(array_key_exists(strtoupper($TextBuffer),$sinclairBasicKeywords))
                    {
                        $TempBuffer[] = $sinclairBasicKeywords[strtoupper($TextBuffer)];
                        $TextBuffer = "";
                    }
                    if($parseOptions->verboseMode)echo "Found keyword: ".strtoupper($TextBuffer).PHP_EOL;
                    $Ptr++;
                    continue;
                }
                else
                {
                    //It wasn't a keyword so reset Ptr
                    if($parseOptions->verboseMode)echo "Found GO, but not followed by SUB or TO. Reseting pointer. ".PHP_EOL;
                    $Ptr = $TempPtr;
                }
            }
            //Check if OPEN or CLOSE, if so seek for hash sign and write to file
            if((strcasecmp($TextBuffer,"OPEN") == 0) or (strcasecmp($TextBuffer,"CLOSE") == 0))
            {
                //Set TempPtr to current location we will need this to go back to if it is a variable named GO
                $TempPtr = $Ptr;
                while(!((strcmp($CurrentLine[$Ptr],"#")==0)))
                {
                    $Ptr++;
                    if($Ptr >= strlen($CurrentLine))
                        break;
                }

                if(strcasecmp($CurrentLine[$Ptr],"#" == 0))
                {
                    $TextBuffer=$TextBuffer . $CurrentLine[$Ptr];
                    if(array_key_exists(strtoupper($TextBuffer),$sinclairBasicKeywords))
                        $TempBuffer[] = $sinclairBasicKeywords[strtoupper($TextBuffer)];
                    if($parseOptions->verboseMode)echo "Found keyword: ".strtoupper($TextBuffer).PHP_EOL;
                    continue;
                }
                else
                {
                    //It wasn't a keyword so reset Ptr
                    $Ptr = $TempPtr;
                }
            }
            //If BIN deal with the first of our number types, anything after BIN shoukd be 1s and 0s
            if((strcasecmp($TextBuffer,"BIN") == 0))
            {
                if(array_key_exists(strtoupper($TextBuffer),$sinclairBasicKeywords))
                        $TempBuffer[] = $sinclairBasicKeywords[strtoupper($TextBuffer)];
                $Ptr++;
                if($parseOptions->verboseMode)echo "Found keyword: ".strtoupper($TextBuffer).PHP_EOL;

                $TempBinNumber = 0;
                //Get rid of blank spaces
                while(ctype_space($CurrentLine[$Ptr])) $Ptr++;
                //We are expecting a 1 or 0 after a BIN
                if($CurrentLine[$Ptr]!='0' && $CurrentLine[$Ptr]!='1')
                {
                    Error("line: ". $currentLineNum . "Bad BIN number" . PHP_EOL);
                }

                //Here the current character is a 1 or 0
                //Check if next is a x
                if($CurrentLine[$Ptr]=='0' && strtoupper($CurrentLine[$Ptr+1])=='X')
                {
                    //Move on pointer
                    $TempHexNumber = "";
                    $Ptr += 2;
                    while(strchr("0123456789AaBbCcDdEeFf",$CurrentLine[$Ptr]))
                    {
                        $TempHexNumber = $TempHexNumber . $CurrentLine[$Ptr];
                        $Ptr++;
                        if($Ptr >= strlen($CurrentLine))
                        break;
                    }
                    //We should now have a hex number
                    if($parseOptions->verboseMode)echo "Found hex number: ".strtoupper($TempHexNumber).PHP_EOL;
                    //Convert hex number to binary and write back into $TempBuffer
                    $chars = str_split(base_convert($TempHexNumber, 16, 2));
                    foreach($chars as $char)
                    {
                        //Multiply by 2
                        $TempBinNumber*=2;
                        //Add the 1 or 0
                        $TempBinNumber+=(int)$char;
                        //Write phyiscal number out
                        if(array_key_exists($char,$sinclairBasic))
                            $TempBuffer[] = $sinclairBasic[$char];
                    }
                    //Now continue as if we had a binary file.
                }
                else
                {
                    while((strcmp($CurrentLine[$Ptr],'0')==0) or (strcmp($CurrentLine[$Ptr],'1')==0))
                    {
                        //Multiply by 2
                        $TempBinNumber*=2;
                        //Add the 1 or 0
                        $TempBinNumber+=(int)$CurrentLine[$Ptr];
                        //Write phyiscal number out
                        if(array_key_exists($CurrentLine[$Ptr],$sinclairBasic))
                            $TempBuffer[] = $sinclairBasic[$CurrentLine[$Ptr]];
                        //Move to next number
                        $Ptr++;
                        if($Ptr >= strlen($CurrentLine))
                        break;
                    }
                }

                //Sinclair basic then stores the value of the binary number after the physical representation
                if($parseOptions->verboseMode)echo "Temp number = " . $TempBinNumber . PHP_EOL;
                $exponentMantissaArray = frexp($TempBinNumber,$parseOptions->subMachine);
                foreach($exponentMantissaArray as $element)
                {
                    $TempBuffer[] = $element;
                }
                continue;
            }
            //Check for DEF it is either KEYWORD DEF FN or variable def
            if(strcasecmp($TextBuffer,"DEF") == 0)
            {
                //Set TempPtr to current location we will need this to go back to if it is a variable named DEF
                $TempPtr = $Ptr;
                $Ptr++;
                while((!ctype_alpha($CurrentLine[$Ptr])))
                {
                    $Ptr++;
                    if($Ptr >= strlen($CurrentLine))
                        break;
                }

                while((ctype_alpha($CurrentLine[$Ptr])))
                {
                    $TextBuffer2=$TextBuffer2 . $CurrentLine[$Ptr];
                    $Ptr++;
                }
                if(strcasecmp($TextBuffer2,"FN") == 0)
                {
                    $TextBuffer=$TextBuffer ." ". $TextBuffer2;
                    if(array_key_exists(strtoupper($TextBuffer),$sinclairBasicKeywords))
                        $TempBuffer[] = $sinclairBasicKeywords[strtoupper($TextBuffer)];
                    if($parseOptions->verboseMode)echo "Found keyword: ".strtoupper($TextBuffer).PHP_EOL;
                    $Ptr++;
                    continue;
                }
                else
                {
                    //It wasn't a keyword so reset Ptr
                    if($parseOptions->verboseMode)echo "Found DEF, but not followed by FN. Reseting pointer. ".PHP_EOL;
                    $Ptr = $TempPtr;
                }
            }
            //If Timex or Spectrum Next check for ON ERR
            if($parseOptions->machineType == "TIMEX"  or $parseOptions->machineType == "NEXT")
            {
                if(strcasecmp($TextBuffer2,"ON") == 0)
                {
                    //Set TempPtr to current location we will need this to go back to if it is a variable named DEF
                    $TempPtr = $Ptr;
                    $Ptr++;
                    while((!ctype_alpha($CurrentLine[$Ptr])))
                    {
                        $Ptr++;
                        if($Ptr >= strlen($CurrentLine))
                            break;
                    }
    
                    while((ctype_alpha($CurrentLine[$Ptr])))
                    {
                        $TextBuffer2=$TextBuffer2 . $CurrentLine[$Ptr];
                        $Ptr++;
                    }
                    if(strcasecmp($TextBuffer2,"ERR") == 0)
                    {
                        $TextBuffer=$TextBuffer ." ". $TextBuffer2;
                        if(array_key_exists(strtoupper($TextBuffer),$sinclairBasicKeywords))
                            $TempBuffer[] = $sinclairBasicKeywords[strtoupper($TextBuffer)];
                        if($parseOptions->verboseMode)echo "Found keyword: ".strtoupper($TextBuffer).PHP_EOL;
                        $Ptr++;
                        continue;
                    }
                    else
                    {
                        //It wasn't a keyword so reset Ptr
                        if($parseOptions->verboseMode)echo "Found ON, but not followed by ERR. Reseting pointer. ".PHP_EOL;
                        $Ptr = $TempPtr;
                    }
                }
            }
            
            //Check if matches token array if so write to file
            if(array_key_exists(strtoupper($TextBuffer),$sinclairBasicKeywords))
            {
                $TempBuffer[] = $sinclairBasicKeywords[strtoupper($TextBuffer)];
                //Eat up a space if there is a space following
                if(ctype_space($CurrentLine[$Ptr]))
                    $Ptr++;
                if($parseOptions->verboseMode)echo "Found keyword: ".strtoupper($TextBuffer).PHP_EOL;
                $TextBuffer = "";
                continue;
            }

            //If we are here it must be a variable
            //Write what we have to the output file and clear the buffer
            
            $chars = str_split($TextBuffer,1);
            foreach($chars as $char)
            {
                if(array_key_exists($char,$sinclairBasic))
                {
                    if($parseOptions->caseSensitive)
                    {
                        $TempBuffer[] = $sinclairBasic[$char];
                    }
                    else
                    {
                        $TempBuffer[] = $sinclairBasic[strtoupper($char)];
                    }
                }
            }

            $TextBuffer = "";

            //we might have more of the same variable if it ends in a number or contains spaces e.g. Dog Faced Boy 23 which is legal in Sinclair Basic
            
            while((ctype_alpha($CurrentLine[$Ptr])) or (ctype_digit($CurrentLine[$Ptr])) or (ctype_space($CurrentLine[$Ptr])))
            {
                if(!(ctype_space($CurrentLine[$Ptr])))
                {
                    $TextBuffer=$TextBuffer . $CurrentLine[$Ptr];
                    if($parseOptions->verboseMode)echo "Start of variable output ptr value: ".$CurrentLine[$Ptr].PHP_EOL;
                    if($parseOptions->verboseMode)echo "Start of variable output TestBuffer: ".$TextBuffer.PHP_EOL;
                } 
                else
                {
                    //Check if we have reached a key word
                    if(array_key_exists(strtoupper($TextBuffer),$sinclairBasicKeywords))
                    {
                        if($parseOptions->verboseMode)echo "Found keyword while searching variables: ".strtoupper($TextBuffer).PHP_EOL;
                        $TempBuffer[] = $sinclairBasicKeywords[strtoupper($TextBuffer)];
                        $TextBuffer = "";
                        if($Ptr+1 < strlen($CurrentLine))
                            $Ptr++;
                        continue 2;
                    }
                    else 
                    {
                        //The rest should be variable write it out and clear buffer
                        
                        if($parseOptions->verboseMode)echo "Writing TestBuffer: ".$TextBuffer.PHP_EOL;
                        $chars = str_split($TextBuffer,1);
                        foreach($chars as $char)
                        {
                            if(array_key_exists($char,$sinclairBasic))
                            {
                                if($parseOptions->caseSensitive)
                                {
                                    $TempBuffer[] = $sinclairBasic[$char];
                                }
                                else
                                {
                                    $TempBuffer[] = $sinclairBasic[strtoupper($char)];
                                }
                            }
                        }
                        $TextBuffer = "";
                    }
                }              
                $Ptr++;
                if($Ptr == strlen($CurrentLine))
                {
                    //Break here so we can write variable to buffer
                    break;
                }
            }
        }
        //Flush buffer
        $chars = str_split($TextBuffer,1);
        foreach($chars as $char)
        {
            if(array_key_exists($char,$sinclairBasic))
            {
                if($parseOptions->caseSensitive)
                {
                    $TempBuffer[] = $sinclairBasic[$char];
                }
                else
                {
                    $TempBuffer[] = $sinclairBasic[strtoupper($char)];
                }
            }
            
        }
        $TextBuffer = "";
        //Hopefully we have dealt with anynumbers on the end of variable names so now convert any variables
        if(!$InString && (ctype_digit($CurrentLine[$Ptr]) or $CurrentLine[$Ptr] == "."))
        {
            $TempNumberBuffer="";
            while(ctype_digit($CurrentLine[$Ptr]) or (strcmp($CurrentLine[$Ptr], ".") == 0) or (strcmp($CurrentLine[$Ptr], "e")==0))
            {
                $TempNumberBuffer = $TempNumberBuffer . $CurrentLine[$Ptr];
                if(array_key_exists($CurrentLine[$Ptr],$sinclairBasic))
                    $TempBuffer[] = $sinclairBasic[$CurrentLine[$Ptr]];
                $Ptr++;
                if($Ptr >= strlen($CurrentLine))
                    break;
            }
            if($parseOptions->verboseMode)echo "Temp number buffer =".$TempNumberBuffer .PHP_EOL;
            $exponentMantissaArray = frexp(floatval($TempNumberBuffer),$parseOptions->subMachine);
            foreach($exponentMantissaArray as $element)
            {
                $TempBuffer[] = $element;
            }
            continue;
        }

        //If not inside a string and we have got this far everything else must be directly translatable
        if(!$InString)
        {
            //Check for awkward characters like <=,>= & <>
            if(strchr("<>*\"",$CurrentLine[$Ptr]))
            {
                switch($CurrentLine[$Ptr])
                {
                    case "<":
                        if(strcmp(">" ,$CurrentLine[$Ptr+1])== 0)
                        {
                            $TempBuffer[] = $sinclairBasic[$CurrentLine[$Ptr].$CurrentLine[$Ptr+1]];
                            if($parseOptions->verboseMode)echo "Ptr =" . $CurrentLine[$Ptr] . "Ptr+1 =" . $CurrentLine[$Ptr+1] . "Sinclairbasic returns: ". $sinclairBasic[$CurrentLine[$Ptr].$CurrentLine[$Ptr+1]];
                            $Ptr=$Ptr+2;
                            continue 2;
                        }
                    else if (strcmp("=" ,$CurrentLine[$Ptr+1])== 0)
                    {
                        $TempBuffer[] = $sinclairBasic[$CurrentLine[$Ptr].$CurrentLine[$Ptr+1]];
                        if($parseOptions->verboseMode)echo "Ptr =" . $CurrentLine[$Ptr] . "Ptr+1 =" . $CurrentLine[$Ptr+1] . "Sinclairbasic returns: ". $sinclairBasic[$CurrentLine[$Ptr].$CurrentLine[$Ptr+1]];
                        $Ptr=$Ptr+2;
                        continue 2;
                    }
                    case ">":
                        if (strcmp("=" ,$CurrentLine[$Ptr+1])== 0)
                    {
                        $TempBuffer[] = $sinclairBasic[$CurrentLine[$Ptr].$CurrentLine[$Ptr+1]];
                        if($parseOptions->verboseMode)echo "Ptr =" . $CurrentLine[$Ptr] . "Ptr+1 =" . $CurrentLine[$Ptr+1] . "Sinclairbasic returns: ". $sinclairBasic[$CurrentLine[$Ptr].$CurrentLine[$Ptr+1]];
                        $Ptr=$Ptr+2;
                        continue 2;
                    }
                    case "*":
                    if (strcmp("*" ,$CurrentLine[$Ptr])== 0 and strcmp("*" ,$CurrentLine[$Ptr+1])== 0)
                    {
                        $TempBuffer[] = $sinclairBasic[$CurrentLine[$Ptr].$CurrentLine[$Ptr+1]];
                        if($parseOptions->verboseMode)echo "Ptr =" . $CurrentLine[$Ptr] . "Ptr+1 =" . $CurrentLine[$Ptr+1] . "Sinclairbasic returns: ". $sinclairBasic[$CurrentLine[$Ptr].$CurrentLine[$Ptr+1]];
                        $Ptr=$Ptr+2;
                        continue 2;
                    }
                    case "\"":
                    if (strcmp("\"" ,$CurrentLine[$Ptr])== 0 and strcmp("\"" ,$CurrentLine[$Ptr+1])== 0)
                    {
                        $TempBuffer[] = $sinclairBasic[$CurrentLine[$Ptr].$CurrentLine[$Ptr+1]];
                        if($parseOptions->verboseMode)echo "Ptr =" . $CurrentLine[$Ptr] . "Ptr+1 =" . $CurrentLine[$Ptr+1] . "Sinclairbasic returns: ". $sinclairBasic[$CurrentLine[$Ptr].$CurrentLine[$Ptr+1]];
                        $Ptr=$Ptr+2;
                        continue 2;
                    }
                }
            }
            //If zx81 or zx80 also need to check ** and ""
            if($parseOptions->machineType == "ZX80"  or $parseOptions->subMachine = "ZX81")
            {
                //Check for awkward characters like **,"" raise to power of and display file quotes
                if(strchr("*\"",$CurrentLine[$Ptr]))
                {
                    switch($CurrentLine[$Ptr])
                    {
                        case "*":
                            if (strcmp("*" ,$CurrentLine[$Ptr])== 0 and strcmp("*" ,$CurrentLine[$Ptr+1])== 0)
                            {
                                $TempBuffer[] = $sinclairBasic[$CurrentLine[$Ptr].$CurrentLine[$Ptr+1]];
                                if($parseOptions->verboseMode)echo "Ptr =" . $CurrentLine[$Ptr] . "Ptr+1 =" . $CurrentLine[$Ptr+1] . "Sinclairbasic returns: ". $sinclairBasic[$CurrentLine[$Ptr].$CurrentLine[$Ptr+1]];
                                $Ptr=$Ptr+2;
                                continue 2;
                            }
                        case "\"":
                            if (strcmp("\"" ,$CurrentLine[$Ptr])== 0 and strcmp("\"" ,$CurrentLine[$Ptr+1])== 0)
                            {
                                $TempBuffer[] = $sinclairBasic[$CurrentLine[$Ptr].$CurrentLine[$Ptr+1]];
                                if($parseOptions->verboseMode)echo "Ptr =" . $CurrentLine[$Ptr] . "Ptr+1 =" . $CurrentLine[$Ptr+1] . "Sinclairbasic returns: ". $sinclairBasic[$CurrentLine[$Ptr].$CurrentLine[$Ptr+1]];
                                $Ptr=$Ptr+2;
                                continue 2;
                            }
                    }
                }
            }
            //Anything left just converts directly
            if(!ctype_space($CurrentLine[$Ptr]))
                if(array_key_exists($CurrentLine[$Ptr],$sinclairBasic))
                    $TempBuffer[] = $sinclairBasic[$CurrentLine[$Ptr]];
        }
        $Ptr++;
    }
    //Add endline
    $TempBuffer[] = $parseOptions->lineEnding;
    if($parseOptions->machineType != "ZX80")
    {
        //Line length = size of the array minus 2 for the line number and the line size
        $TempLineLength = count($TempBuffer) - 4;
        // Add line length to array elements
        $TempBuffer[2] = ($TempLineLength & 0xff);
        $TempBuffer[3] = ($TempLineLength & 0xff00) >> 8;
    }
    if($parseOptions->verboseMode)
    {
        for($x = 0; $x < count($TempBuffer); $x++)
        {
            if($parseOptions->verboseMode)echo $TempBuffer[$x] . " ";
        }
        if($parseOptions->verboseMode)echo  " String length: " . count($TempBuffer) . PHP_EOL;
    }
    //Write to file byte by byte
    for($x = 0; $x < count($TempBuffer); $x++)
    {
        fputs($OutputFile, chr($TempBuffer[$x]), 1);
    }
    //Write Spectrum Header

}
fclose($OutputFile);

//Now we append the spectrum header and write file
switch($parseOptions->outputFormat)
{
    case "TAP" : 
        prependSpectrumTapeHeader($parseOptions);
        break;
    case "RAW" :
        //Do nothing as file has already been written
        break;
    case "DOS" :
        prependPlus3Header($parseOptions);
        break;
    case "P81" :
        prependZX81TapeHeader($parseOptions,$sinclairBasic);
        break;
    case "O80" :
        prependZX80TapeHeader($parseOptions,$sinclairBasic);
        break;
    case "LAMBDA":
        prependLambdaTapeHeader($parseOptions,$sinclairBasic);
        break;
}

if($parseOptions->outputTZX)
{
    //SAnitY ChEckS
    if($parseOptions->machineType != "SPECTRUM" and $parseOptions->machineType != "TIMEX")
        Error("TZX file type is only supported for SPECTRUM and TIMEX Tape based outputs.");
    else
        prependTZXHeader($parseOptions);
}

/*
TO DO

IF THEN needs a special case - DONE

IF is48 THEN GOTO is currently reading is48THENGOTO as a variable if there is an IF we need to stop variable reading if there is a THEN with space either side - DONE

This works l$>64, this doesn't l$<64 - DONE

Floats, Binary and Integers insert value - DONE?

Add ability to use HEX afer BIN - DONE

Deal with split lines - DONE?

DEF FN - DONE?

Maybe allow SCREEN$ without a space between keyword and () - DONE

FUTURE

Add PLUS 3 - ?DONE

Add ZX81 - ?DONE

Add ZX80 - ?DONE

Add NEXT

Future

Lambda support, Timex, etc

TZX Support

Add filename endings .P,.O,.TAP,.TZX



Output WAV support



TO DO

4. Fix autorun address (for ZX81,Lambda, ZX80?)*/

?>
