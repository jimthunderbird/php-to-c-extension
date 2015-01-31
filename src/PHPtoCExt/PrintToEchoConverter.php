<?php 
/**
 * convert print statement to echo statement
 */
namespace PHPtoCExt;

class PrintToEchoConverter extends Converter
{
  public function convert()
  {
    $this->searchAndReplace("print ","echo ");
  }
}
