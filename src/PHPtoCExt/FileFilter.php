<?php 
namespace PHPtoCExt;

class FileFilter 
{
  private $source_file;
  private $target_file;

  public function __construct($source_file, $target_file)
  {
    $this->source_file = $source_file;
    $this->target_file = $target_file;
  }

  public function filter()
  {
    $source_file_content = file_get_contents($this->source_file);  
    $convertor = new ForLoopToWhileLoopConvertor($source_file_content);
    $target_file_content = $convertor->convert();
    file_put_contents($this->target_file, $target_file_content);
  }
}
