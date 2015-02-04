<?php 
/**
 * convert isset to !empty 
 * convert !isset to empty
 */
namespace PHPtoCExt\Converter;

class IssetToNotEmptyConverter extends \PHPtoCExt\Converter
{
  public function convert()
  {
    $this->searchAndReplace("isset","!empty");
    $this->searchAndReplace("!!","");
  }
}
