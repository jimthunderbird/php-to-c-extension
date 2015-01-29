<?php 
namespace PHPtoCExt;

abstract class Converter 
{
  protected $code; //the code to convert

  public function __construct($code)
  {
    $this->code = $code;
  }

  abstract public function convert(); 
}
