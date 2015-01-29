<?php 
/**
 * convert print statement to echo statement
 */
namespace PHPtoCExt;

class PrintToEchoConverter extends Converter
{
  public function convert()
  {
    $codeLines = explode("\n", $this->code);
    foreach($codeLines as $index => $line) {
      if(strpos($line, "print ") !== FALSE ) {
        $codeLines[$index] = str_replace("print ","echo ",$line);
      } 
    }
    $this->code = implode("\n", $codeLines);
    return $this->code;
  }
}
