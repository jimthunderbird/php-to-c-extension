<?php 
namespace PHPtoCExt;

abstract class Converter 
{
  protected $codeLines;
  protected $codeASTXMLLines;
  protected $searches;
  protected $replaces;

  public function __construct($codeLines, $codeASTXMLLines)
  {
    $this->codeLines = $codeLines;
    $this->codeASTXMLLines = $codeASTXMLLines;
    $this->searches = array();
    $this->replaces = array();
  }

  public function getSearches()
  {
    return $this->searches;
  }

  public function getReplaces()
  {
    return $this->replaces;
  }

  protected function searchAndReplace($search, $replace)
  {
    $this->searches[] = $search; 
    $this->replaces[] = $replace; 
  }

  abstract public function convert(); 
}
