# batoco
[Ba]sic [To] [Co]de
A PHP clone of zmakebas

The PHP file can be called from the commandline in Windows via
 
<path to PHP install> batoco.PHP <input file name>
 
e.g. c:\PHP\PHP batoco.PHP .\Test_Files\GOTO_Test_Labels.bas -v -L -n GTO-TEST -o GOTOTestPHP.tap
 
In unix you should be able to just run batoco.PHP
 
e.g. batoco.PHP .\Test_Files\GOTO_Test_Labels.bas -v -L -n GTO-TEST -o GOTOTestPHP.tap
 
It can also be called via a URL using POST or GET to send the arguments
 
e.g. http://localhost/batoco.php?input=.\Test_Files\ZMAKEBAS_Test_Labels.bas&l=on&n=ZMB-TEST&o=ZMAKEBASTestPHP.tap
 
It is functionally equivalent to zmakebas.c though it is mostly written from scratch. It has all the extra features, like labels and shortcuts to embed UDGs.
 
It supports these options from the commandline though some are currently non-functional
 
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
    // s=<StartNumber>          : in labels mode, set starting line number ";
    // m=<Machine>              : Select machine to output for from ZX80,ZX81,Lambda,Timex,Spectrum,Plus3
 
and these via URL
 
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
    // input=<Input filename>   : Name of the file to read and convert;
    // m=<Machine>              : Select machine to output for from ZX80,ZX81,Lambda,Timex,Spectrum,Plus3

Differences to zmakebas

Hex numbers -

Like zmakebas you can use Hex numbers after a BIN keyword. These are expected to start 0x. However, these are translated back into a binary representation in the outputted file.

E.g.  BIN 0xAA4E will appear in the output as BIN 1010101001001110

Commandline parameters -

The input filename is given as the first parameter not the last parameter

E.g.

zmakebas -l inputfile.bas

PHP batoco.php inputfile.bas -l

Bugs
 
There will be bugs, when you find them if you can send me the BASIC file I'll see what is broken.
