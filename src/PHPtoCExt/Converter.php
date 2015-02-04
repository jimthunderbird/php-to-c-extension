<?php 
namespace PHPtoCExt;

abstract class Converter 
{
  protected $codeLines;
  protected $codeASTXMLLines;
  protected $searches;
  protected $replaces;
  protected $postSearches;
  protected $postReplaces;

  public function __construct($codeLines, $codeASTXMLLines)
  {
    $this->codeLines = $codeLines;
    $this->codeASTXMLLines = $codeASTXMLLines;
    $this->searches = array();
    $this->replaces = array();
    $this->postSearches = array();
    $this->postReplaces = array();
  }

  public function getSearches()
  {
    return $this->searches;
  }

  public function getReplaces()
  {
    return $this->replaces;
  }

  public function getPostSearches()
  {
    return $this->postSearches;
  }

  public function getPostReplaces()
  {
    return $this->postReplaces;    
  }

  protected function searchAndReplace($search, $replace)
  {
    $this->searches[] = $search; 
    $this->replaces[] = $replace; 
  }

  protected function postSearchAndReplace($search, $replace)
  {
    $this->postSearches[] = $search; 
    $this->postReplaces[] = $replace; 
  }

  abstract public function convert(); 
}
