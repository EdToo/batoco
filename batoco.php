<?php
// (C) Taskmaster Software 2025 - This code is released under the GPL v3 license
// Uses code from UTO's drb.php from DAAD Ready
// Uses inspiration and some converted code from zmakebas.c by Russell Marks, 1998

//Set route to php for unix environments
#!/usr/bin/php

global $parseOptions;
global $basicLines;

define("VERSION_NUMBER","0.0.1");
define("DEFAULT_OUTPUT","out.tap");

$SinclairBasicKeywords = array(
    
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

$Sinclair_Basic = array(
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
"\\"=>92,
"]"=>93,
"^"=>94,
"_"=>95,
"£"=>96,
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
"<>"=>201,);

//================================================================= helper functions =====================================================
// Replace all instances of array keys in  string with array values
function strReplaceAssoc(array $replace, $subject) {

    return str_replace(array_keys($replace), array_values($replace), $subject);    
 
 }
 // Replace all instances of array keys in  string with array values case insensitive
 function strReplaceAssoci(array $replace, $subject) {

    return str_ireplace(array_keys($replace), array_values($replace), $subject);    
 
 }
 /* dbl2spec() converts a double to an inline-basic-style speccy FP number.
 *
 * usage: dbl2spec(num,&exp,&man);
 *
 * num is double to convert.
 * pexp is an int * to where to return the exponent byte.
 * pman is an unsigned long * to where to return the 4 mantissa bytes.
 * bit 31 is bit 7 of the 0th (first) mantissa byte, and bit 0 is bit 0 of
 * the 3rd (last). As such, the unsigned long returned *must* be written
 * to whatever file in big-endian format to make any sense to a speccy.
 *
 * returns 1 if ok, 0 if exponent too big.
 */
function frexp ( $number ) 
{
    
    $returnArray = array();
    //Check if not a float and between -65535 and 65535
    if($number==(int)$number && $number>=-65535 && $number<=65535)
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
        $exponent = ( floor(log($number, 2)) + 1 );
        echo "Exponent = " . $exponent;
        if($exponent<-128 || $exponent>127) 
        {
            Error("Exponent out of range (number too big).");
        };
        $mantissa = ( $number * pow(2, -$exponent) );
        echo "Mantissa = " . $mantissa;
        $mantissaArray = unpack("c*", pack("f", $mantissa));
        //A numerical constant in the program is followed by its binary form, using the character CHR$ 14 followed by five bytes for the number itself.
        $returnArray[] = 0x0E; 
        $returnArray[] = 0x80+$exponent;
        $returnArray[] = $mantissaArray[3];
        $returnArray[] = $mantissaArray[2];
        $returnArray[] = $mantissaArray[1];
        $returnArray[] = $mantissaArray[0];
    
        var_dump($returnArray);

    }
    return $returnArray;
  
}


//================================================================= end helper functions =================================================
function usageHelp() {
    echo "zmakebas.php - public domain by TaskmasterSoftware.\n\n";

    echo "usage: php zmakebas [input_file] [-hlpr3v(vn)] [-a line] [-i incr] [-n speccy_filename]\n";
    echo "                [-o output_file] [-s line]\n\n";

    echo "        -vn     output version number.\n";
    echo "        -v      verbose mode.\n";
    echo "        -a      set auto-start line of basic file (default none).\n";
    echo "        -h      give this usage help.\n";
    echo "        -i      in labels mode, set line number incr. (default 2).\n";
    echo "        -l      use labels rather than line numbers.\n";
    echo "        -n      set Spectrum filename (to be given in tape header).";
    echo "\n        -o      specify output file (default",DEFAULT_OUTPUT,").\n";
    echo "        -p      output .p instead (set ZX81 mode).\n";
    echo "        -r      output raw headerless file (default is .tap file).\n";
    echo "        -3      output a +3DOS compatible file (default is .tap file).\n";
    echo "        -s      in labels mode, set starting line number ";
    echo "(default 10).\n";
    exit(1);
}

function Error($msg)
{
 echo "Error: $msg.\n";
 exit(2);
}

function Warning($msg)
{
 echo "Warning: $msg.\n";
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
                case "-3" : $parseOptions->outputTapeMode = false;$parseOptions->outputPlus3DOSFileMode = true;$parseOptions->outputFormat = "+3";break;
                case "-S" : $parseOptions->setLabelsModeStartLineNumber = $argv[$nextParam];break;
                default: Error("$currentParam is not a valid option");
            }
        } 
    }
    $parseOptions->inputFilename = $argv[1];
}

function parsePostOptions(&$parseOptions)
{//This file can be called like http://localhost/zmakebas.php?input=input.txt
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

    //e.g. http://localhost/zmakebas.php?input=input.txt&n=Game&o=Game.tap

    if (is_array($_POST)) {
        $ok_key_names = ['input', 'v', 'a', 'i', 'l', 'n', 'o', 'p', 'r', '3', 's'];
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
        $parseOptions->outputFormat = "+3";
    }
    if (array_key_exists("S",$a) )
    {
        $parseOptions->setLabelsModeStartLineNumber = (int) $a['S'];
    }
    if (array_key_exists("INPUT",$a) )
    {
        $parseOptions->inputFilename = $a['INPUT'];
    }

}
function parseURLOptions(&$parseOptions)
{
    //This file can be called like http://localhost/zx_htm2tap/zmakebas.php?input=inputfile.bas
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

    //e.g. http://localhost/zx_htm2tap/zmakebas.php?input=inputfile.bas&v=on&l=on&n=ZMB-TEST&o=outputfile.tap
    
    if (is_array($_GET)) {
        $ok_key_names = ['input', 'v', 'a', 'i', 'l', 'n', 'o', 'p', 'r', '3', 's'];
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
        $parseOptions->outputFormat = "+3";
    }
    if (array_key_exists("S",$a) )
    {
        $parseOptions->setLabelsModeStartLineNumber = (int) $a['S'];
    }
    if (array_key_exists("INPUT",$a) )
    {
        $parseOptions->inputFilename = $a['INPUT'];
    }
             
}
//--------------------------------------------Add File Headers--------------------------------------------------------
function prependPlus3Header($outputFileName)
{
    $fileSize = filesize($outputFileName) + 128; // Final file size wit header
    $inputHandle = fopen($outputFileName, 'r') or die("Unable to open file!");
    $outputHandle = fopen("prepend.tmp", "w") or die("Unable to open file!");
    $header = array();
    $header[]= ord('P');
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

function prependZX81TapeHeader($outputFileName)
{
    // TO DO
    /*$fileSize = filesize($outputFileName) + 128; // Final file size wit header
    $inputHandle = fopen($outputFileName, 'r');
    $outputHandle = fopen("prepend.tmp", "w");

    $header = array();
    $header[]= 0x00;//First byte is zero
    while(sizeof($header)<11)$header[]=0x20; //Set 10 byte filename to blank default
    //Now copy in the filename
    $chars = str_split($parseOptions->spectrumFilename);
    $c=1;
    foreach ($chars as $char) {
        $header[Sc]=ord($char);
    }
    $header[]= $fileSize & 0x00FF;  // Two bytes for data size
    $header[]= ($fileSize & 0xFF00) >> 8;
    $header[]= $parseOptions->autostartLine & 0x00FF;  // Two bytes for data size
    $header[]= $parseOptions->autostartLine & 0xFF00) >> 8;
    $header[]= $fileSize & 0x00FF;  // Two bytes for data size
    $header[]= ($fileSize & 0xFF00) >> 8;
    //
    $checksum = 0;
    for ($i=0;$i<17;$i++)  $checksum+=$header[$i];
    $header[]= $checksum & 0xFF; // Checksum
    
    // Dump header
    for ($i=0;$i<18;$i++) fputs($outputHandle, chr($header[$i]), 1);

    // Dump original file
    while (!feof($inputHandle))
    {
        $c = fgetc($inputHandle);
        fputs($outputHandle,$c,1);
    }
    fclose($inputHandle);
    fclose($outputHandle);
    unlink($outputFileName);
    rename("prepend.tmp" ,$outputFileName);*/
}
//-------------------------------------------------MAIN----------------------------------------------------------------




// Parse optional parameters
$parseOptions= new stdClass();
$parseOptions->verboseMode = false;
$parseOptions->autostartLine = 0x8000;
$parseOptions->spectrumFilename = "out.tap";
$parseOptions->outputFilename = DEFAULT_OUTPUT;
$parseOptions->zx81Mode = false;
$parseOptions->outputTapeMode = true;
$parseOptions->outputRawFileMode = false;
$parseOptions->outputPlus3DOSFileMode = false;
$parseOptions->useLabels = false;
$parseOptions->setLabelModeIncrement = 2;
$parseOptions->setLabelsModeStartLineNumber = 10;
$parseOptions->inputFilename = "default.txt";
$parseOptions->outputFormat = "TAP";
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


//SaNiTy checks

if($parseOptions->useLabels && ($parseOptions->setLabelModeIncrement < 1) || ($parseOptions->setLabelModeIncrement > 1000))
{
    Error("Label line incr. must be in the range 1 to 1000.");
}

if((!$parseOptions->autostartLine == 0x8000) && (($parseOptions->autostartLine < 0) || ($parseOptions->autostartLine > 1000)))
{
    Error("Autostart line number must be in the range 1 to 1000.");
}

// Check input file exists
if($parseOptions->verboseMode)
    echo "Inputfile name is : ".$parseOptions->inputFilename. " ".PHP_EOL;
if (!file_exists($parseOptions->inputFilename)) Error('File not found');
//Open input file and add all the lines to $basicLines array
$fp = @fopen($parseOptions->inputFilename, 'r'); 
if($parseOptions->verboseMode)echo "Opening file";
// Add each line to an array
if ($fp) {
   $basicLines = explode(PHP_EOL, fread($fp, filesize($parseOptions->inputFilename)));
}
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


$CurrentLinenum=0;
$TempBuffer = [];
$TempString = "";

//First pass clean up input and sort labels
if($parseOptions->useLabels) $Linenum=$parseOptions->setLabelsModeStartLineNumber;
$LabelNumberArray = [];
foreach ($basicLines as $CurrentLine)
{
    
    if($parseOptions->verboseMode)echo "Current line number: ",$CurrentLinenum," Current Linenumber to write: ",$Linenum,PHP_EOL;
    $Ptr = 0;   
    //Delete all empty lines
    if (strlen(trim($CurrentLine)) === 0) 
    {
        unset( $basicLines[$CurrentLinenum] );
        if($parseOptions->verboseMode)echo "Unset: ",$CurrentLinenum,PHP_EOL;
        $CurrentLinenum++;
        continue;
    }

    //TO DO
    //If last character of the line equals \\ then append next line to this one and delete next line
    if($CurrentLine[strlen($CurrentLine)-1] == '\\')
    {
        $basicLines[$CurrentLinenum] = $basicLines[$CurrentLinenum] . ltrim($basicLines[$CurrentLinenum+1]);
        unset( $basicLines[$CurrentLinenum+1] );
        if($parseOptions->verboseMode)echo "Split line detected. next line unset: ",$CurrentLinenum,PHP_EOL;
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
                Error("Line: " . $CurrentLinenum . " Line number used in label mode.");
                //Kind of unnecessary as we exited above already
                break;
            }
            //If a number and not using labels we are done with processing this line
            else break;
        case "#": 
            //Delete lines starting with # as they are shell comments
            //array_splice($basicLines,$CurrentLinenum);
            unset( $basicLines[$CurrentLinenum] );
            break;
        case "@":
            //If using labels we now have a label that needs to be assigned a number
            if($parseOptions->useLabels)
            {
                $TempPos = strpos($CurrentLine,":");
                if($TempPos)
                {
                    if($TempPos < $Ptr)
                    {
                        Error("Line: " . $CurrentLinenum . "Label end tag ':' occurs before label start tag '@'");
                    }
                }
                else
                {
                    Error("Line: " . $CurrentLinenum . "Incomplete token definition, label should close with an end tag ':'");
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
                    Error("Line: " . $CurrentLinenum . "Attempt to redefine label" . implode($TempBuffer));
                $LabelNumberArray[implode($TempBuffer)] = $Linenum;
                $TempBuffer = [];
            }
            //Now delete line whether we are using labels or not
            unset( $basicLines[$CurrentLinenum] );


            break;
        default:
            //If we are here it should be a line of code so if using labels add line number
            if($parseOptions->useLabels)
            {
                $TempString = (string)$Linenum . " " . $CurrentLine;
                $basicLines[$CurrentLinenum] = $TempString;
                
                if($parseOptions->verboseMode)echo "Processed: ",$CurrentLinenum,PHP_EOL;
                //If using labels increment linenumber
                if($parseOptions->useLabels) $Linenum = $Linenum+$parseOptions->setLabelModeIncrement; 
            }
    }
    $CurrentLinenum++;
}
$basicLines = array_values( $basicLines );


echo "\n\n";
$CurrentLinenum = 0;
//We now need a second pass to replace labels with line numbers
if($parseOptions->useLabels)
{
    $Ptr = 0; 
    foreach ($basicLines as $CurrentLine)
    {
        $basicLines[$CurrentLinenum] = strReplaceAssoci($LabelNumberArray,$CurrentLine);
        $CurrentLinenum++;
    } 
}

$CurrentLinenum = 0;
$Linenum = 0;
$TempPtr = 0;
//var_dump($basicLines);

//Open output file
$OutputFile = fopen($parseOptions->outputFilename,"w") or die("Unable to open file!");

echo ">";
foreach ($basicLines as $CurrentLine)
{
    
    echo "=";
    $CurrentLinenum++;
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
            Error("line: " . $CurrentLinenum ." missing line number");
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
            Error("line: " . $CurrentLinenum . " line no. not greater than previous one");
        }
    //SaNiTy check
    if($Linenum<0 || $Linenum>9999)
    {
          Error("line: ".$CurrentLinenum." line no. out of range, should be 1 - 9999, found ".$Linenum.PHP_EOL);
    }
    //Write line number to buffer, MSB first, LSB second
    $TempBuffer[] = ($Linenum  & 0xff00) >> 8;
    $TempBuffer[] = $Linenum & 0xff;

    //Now add 2 empty indexes to fill later with the line length
    $TempBuffer[] = 0;
    $TempBuffer[] = 0;

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
            $TempBuffer[] = $Sinclair_Basic["\""];
            $Ptr++;
            continue;
        }
        if($InString)
        {
            //Deal with blocks, escape characters here here
            if($CurrentLine[$Ptr] == "\\")
            {
                //First look for UDGs they are letters, but not V-Z
                if(ctype_alpha($CurrentLine[$Ptr+1]) && !strchr("VWXYZvwxyz",$CurrentLine[$Ptr+1]))
                {
                    $TempBuffer[] = $Sinclair_Basic["(".$CurrentLine[$Ptr+1].")"];
                    $Ptr=$Ptr+2;
                    continue;
                }
                else //Now UDGS are out of the way, lets check for other escape characters
                {
                    switch($CurrentLine[$Ptr+1])
                    {
                        case "\\":
                        case "@":
                            $TempBuffer[] = $Sinclair_Basic[$CurrentLine[$Ptr+1]];
                            $Ptr = $Ptr + 2;
                            continue 2;
                            break; 
                        case "*": // copyright symbol
                            $TempBuffer[] = $Sinclair_Basic["@"];
                            $Ptr = $Ptr + 2;
                            continue 2;
                            break;
                        case '\'': case '.': case ':': case ' ': // block graphics char
                            $TempBuffer[] = $Sinclair_Basic[($CurrentLine[$Ptr+1]==" "?"BLANK":$CurrentLine[$Ptr+1]).($CurrentLine[$Ptr+2]==" "?"BLANK":$CurrentLine[$Ptr+2])];
                            $Ptr = $Ptr + 3;
                            continue 2;
                            break;
                        default:
                            Warning("line: " . $CurrentLinenum .  "unknown escape charater  " . $CurrentLine[$Ptr] . $CurrentLine[$Ptr+1] . " inserting literally");
                            $TempBuffer[] =  $CurrentLine[$Ptr];
                            $TempBuffer[] =  $CurrentLine[$Ptr+1];
                            $Ptr = $Ptr + 2;
                            continue 2;
                            break;
                    }
                }
            }
            //Sendcontents to $TempBuffer[]
            
            if(array_key_exists($CurrentLine[$Ptr],$Sinclair_Basic))
                $TempBuffer[] = $Sinclair_Basic[$CurrentLine[$Ptr]];
        }
        //If not in a string then anything starting with a character is either a keyword or variable
        if((ctype_alpha($CurrentLine[$Ptr])) && !$InString && ($Ptr != strlen($CurrentLine)-1) ) 
        {
            $TextBuffer="";
            $TextBuffer2="";
            if($Ptr < strlen($CurrentLine))
            {
                //Need to get all alphabetical characters and $ to allow for SCREEN$,VAL$, etc and String variables
                while((ctype_alpha($CurrentLine[$Ptr])) or ($CurrentLine[$Ptr] == "$"))
                {
                    $TextBuffer=$TextBuffer . $CurrentLine[$Ptr];
                    $Ptr++;
                    if(!($Ptr < strlen($CurrentLine)))
                    continue 2;
                }

            }
            
            //Check if REM, if so record keyword and then transfer everything after REM to $TempBuffer and continue
            if(strtoupper($TextBuffer) == "REM")
            {
                $TempBuffer[] =  $SinclairBasicKeywords[strtoupper($TextBuffer)];
                //Eat one more space
                $Ptr++;
                while($Ptr < strlen($CurrentLine))
                {
                    $TempBuffer[] = $Sinclair_Basic[$CurrentLine[$Ptr]];
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
                    if(array_key_exists(strtoupper($TextBuffer),$SinclairBasicKeywords))
                        $TempBuffer[] = $SinclairBasicKeywords[strtoupper($TextBuffer)];
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
                    if(array_key_exists(strtoupper($TextBuffer),$SinclairBasicKeywords))
                        $TempBuffer[] = $SinclairBasicKeywords[strtoupper($TextBuffer)];
                    if($parseOptions->verboseMode)echo "Found keyword: ".strtoupper($TextBuffer).PHP_EOL;
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
                if(array_key_exists(strtoupper($TextBuffer),$SinclairBasicKeywords))
                        $TempBuffer[] = $SinclairBasicKeywords[strtoupper($TextBuffer)];
                $Ptr++;
                if($parseOptions->verboseMode)echo "Found keyword: ".strtoupper($TextBuffer).PHP_EOL;

                $TempBinNumber = 0;
                //Get rid of blank spaces
                while(ctype_space($CurrentLine[$Ptr])) $Ptr++;
                //We are expecting a 1 or 0 after a BIN
                if($CurrentLine[$Ptr]!='0' && $CurrentLine[$Ptr]!='1')
                {
                    Error("line: ". $CurrentLinenum . "Bad BIN number" . PHP_EOL);
                }

                //Here the current character is a 1 or 0
                //Check if next is a x
                if($CurrentLine[$Ptr]=='0' && strtoupper($CurrentLine[$Ptr])=='X')
                {
                    //Move on pointer
                    $Ptr += 2;
                    //TO DO
                    //Here we can implement using Hex numbers after a BIN statement.
                    //Step 1. check for a x after a 0 - DONE
                    //Step 2 read all the letters and numbers off and store them.
                    //Step 3. Convert to binary $hexadecimal = '0xA37334'; echo base_convert($hexadecimal, 16, 2);
                    //Step 4. Write binary out to file
                    //Step 5. Write out the value to file
                }


                while((strcmp($CurrentLine[$Ptr],'0')==0) or (strcmp($CurrentLine[$Ptr],'1')==0))
                {
                    //Multiply by 2
                    $TempBinNumber*=2;
                    //Add the 1 or 0
                    $TempBinNumber+=(int)$CurrentLine[$Ptr];
                    //Write phyiscal number out
                    if(array_key_exists($CurrentLine[$Ptr],$Sinclair_Basic))
                        $TempBuffer[] = $Sinclair_Basic[$CurrentLine[$Ptr]];
                    //Move to next number
                    $Ptr++;
                    if($Ptr >= strlen($CurrentLine))
                    break;
                }
                //Sinclair basic then stores the value of the binary number after the physical representation
                echo "Temp number = " . $TempBinNumber . PHP_EOL;
                $exponentMantissaArray = frexp($TempBinNumber);
                foreach($exponentMantissaArray as $element)
                {
                    $TempBuffer[] = $element;
                }
                continue;
            }
            //Check for DEF it is either KEYWORD DEF FN or variable def
            if(strcasecmp($TextBuffer,"DEF") == 0)
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
                if(strcasecmp($TextBuffer2,"FN") == 0)
                {
                    $TextBuffer=$TextBuffer ." ". $TextBuffer2;
                    if(array_key_exists(strtoupper($TextBuffer),$SinclairBasicKeywords))
                        $TempBuffer[] = $SinclairBasicKeywords[strtoupper($TextBuffer)];
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

            //Check if matches token array if so write to file
            if(array_key_exists(strtoupper($TextBuffer),$SinclairBasicKeywords))
            {
                $TempBuffer[] = $SinclairBasicKeywords[strtoupper($TextBuffer)];
                //Eat one extra space unless the is an opening brace next eg SCREEN$(0,0)
                if($Ptr < strlen($CurrentLine))
                    if(strcmp($CurrentLine[$Ptr],"(" ) == 0)
                        if(array_key_exists("(",$Sinclair_Basic))
                            $TempBuffer[] = $Sinclair_Basic["("];
                $Ptr++;
                if($parseOptions->verboseMode)echo "Found keyword: ".strtoupper($TextBuffer).PHP_EOL;
                continue;
            }
            //If we are here it must be a variable, check if it is followed by any numbers then write to file
            //Ignore spaces and suck up any alpha or digits, stop when neither.
            while((ctype_alpha($CurrentLine[$Ptr])) or (ctype_digit($CurrentLine[$Ptr])) or (ctype_space($CurrentLine[$Ptr])))
            {
                if(!(ctype_space($CurrentLine[$Ptr])))
                {

                    $TextBuffer=$TextBuffer . $CurrentLine[$Ptr];
                }                
                $Ptr++;
                if($Ptr == strlen($CurrentLine))
                    continue 2;
            }
            //Write text buffer to TempBuffer
            $chars = str_split($TextBuffer,1);
            foreach($chars as $char)
            {
                if(array_key_exists($char,$Sinclair_Basic))
                    $TempBuffer[] = $Sinclair_Basic[$char];
            }
            continue;

        }

        //Hopefully we have dealt with anynumbers on the end of variable names so now convert any variables
        if(ctype_digit($CurrentLine[$Ptr]) or $CurrentLine[$Ptr] == ".")
        {
            $TempNumberBuffer="";
            while(ctype_digit($CurrentLine[$Ptr]) or (strcmp($CurrentLine[$Ptr], ".") == 0) or (strcmp($CurrentLine[$Ptr], "e")==0))
            {
                $TempNumberBuffer = $TempNumberBuffer . $CurrentLine[$Ptr];
                if(array_key_exists($CurrentLine[$Ptr],$Sinclair_Basic))
                    $TempBuffer[] = $Sinclair_Basic[$CurrentLine[$Ptr]];
                $Ptr++;
                if($Ptr >= strlen($CurrentLine))
                    break;
            }
            
            $exponentMantissaArray = frexp(floatval($TempNumberBuffer));
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
            if(strchr("<>=",$CurrentLine[$Ptr]))
            {
                switch($CurrentLine[$Ptr])
                {
                    case "<":
                        if(strcmp(">",$CurrentLine[$Ptr]))
                        {
                            $TempBuffer[] = $Sinclair_Basic[$CurrentLine[$Ptr].$CurrentLine[$Ptr+1]];
                            $Ptr=$Ptr+2;
                            continue 2;
                        }
                    else if (strcmp("=",$CurrentLine[$Ptr]))
                    {
                        $TempBuffer[] = $Sinclair_Basic[$CurrentLine[$Ptr].$CurrentLine[$Ptr+1]];
                        $Ptr=$Ptr+2;
                        continue 2;
                    }
                    case "<":
                        if (strcmp("=",$CurrentLine[$Ptr]))
                    {
                        $TempBuffer[] = $Sinclair_Basic[$CurrentLine[$Ptr].$CurrentLine[$Ptr+1]];
                        $Ptr=$Ptr+2;
                        continue 2;
                    }
                }
            }

            //Anything left just converts directly
            if(!ctype_space($CurrentLine[$Ptr]))
                if(array_key_exists($CurrentLine[$Ptr],$Sinclair_Basic))
                    $TempBuffer[] = $Sinclair_Basic[$CurrentLine[$Ptr]];
        }
        $Ptr++;
    }
    //Add endline
    $TempBuffer[] = 0x0D;
    //Line length = size of the array minus 2 for the line number and the line size
    $TempLineLength = count($TempBuffer) - 4;
    // Add line length to array elements
    $TempBuffer[2] = ($TempLineLength & 0xff);
    $TempBuffer[3] = ($TempLineLength & 0xff00) >> 8;
    if($parseOptions->verboseMode)
    {
        for($x = 0; $x < count($TempBuffer); $x++)
        {
            echo $TempBuffer[$x] . " ";
        }
        echo  " String length: " . count($TempBuffer) . PHP_EOL;
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
    case "+3" :
        prependPlus3Header($parseOptions);
        break;
    case "P81" :
        prependZX81TapeHeader($parseOptions);
        break;
}

/*
TO DO

IF THEN needs a special case

IF is48 THEN GOTO is currently reading is48THENGOTO as a variable if there is an IF we need to stop variable reading if there is a THEN with space either side

This works l$>64, this doesn't l$<64

Floats, Binary and Integers insert value - DONE?

Add ability to use HEX afer BIN?

Deal with split lines - DONE?

DEF FN - DONE?

Maybe allow SCREEN$ without a space between keyword and () - DONE

Future

Add PLUS 3

Add ZX81

Add NEXT

Future

Lambda support, Timex, etc

?>
