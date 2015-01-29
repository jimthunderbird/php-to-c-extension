<?php 
namespace PHPtoCExt;

class FileFilter 
{
  private $sourceFile;
  private $targetFile;

  public function __construct($sourceFile, $targetFile)
  {
    $this->sourceFile = $sourceFile;
    $this->targetFile = $targetFile;
  }

  public function filter()
  {
    $sourceFileContent = file_get_contents($this->sourceFile);  

    //load all converters 
    $converterClasses = array(
      "PHPtoCExt\ForLoopToWhileLoopConverter",
      "PHPtoCExt\PrintToEchoConverter"
    );

    try {
      $targetFileContent = $sourceFileContent;
      //go through all converters to convert the source code
      foreach ($converterClasses as $converterClass) {
        $converter = new $converterClass($targetFileContent);
        $targetFileContent = $converter->convert();
      }
      file_put_contents($this->targetFile, $targetFileContent);

    } catch (ConverterException $e) {
      echo $e->getMessage();
    }  
  } 
}
