<?php 
/**
 * convert print statement to echo statement
 */
namespace PHPtoCExt\Converter;

class PrintToEchoConverter extends \PHPtoCExt\Converter
{
  public function convert()
  {
    $this->searchAndReplace("print ","echo ");
  }
}
