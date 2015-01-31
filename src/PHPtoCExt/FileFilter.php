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
    $sourceFileContent = trim(file_get_contents($this->sourceFile));  

    $parser = new \PhpParser\Parser(new \PhpParser\Lexer);
    $serializer = new \PhpParser\Serializer\XML();

    try {
      $stmts = $parser->parse($sourceFileContent);

      $codeLines = explode("\n", $sourceFileContent);
      $codeASTXML = $serializer->serialize($stmts);
      $codeASTXMLLines = explode("\n", $codeASTXML);

      //load all converters 
      $converterClasses = array(
        "PHPtoCExt\ForLoopToWhileLoopConverter",
        "PHPtoCExt\InterfaceToAbstractClassConverter",
        "PHPtoCExt\PrintToEchoConverter"
      );

      $searches = array();
      $replaces = array();
      //go through all converters to convert the source code 
      foreach ($converterClasses as $converterClass) {
        $converter = new $converterClass($codeLines, $codeASTXMLLines);
        $converter->convert();
        $searches = array_merge($searches, $converter->getSearches());
        $replaces = array_merge($replaces, $converter->getReplaces());
      }

      $targetFileContent = str_replace($searches, $replaces, $sourceFileContent);
      file_put_contents($this->targetFile, $targetFileContent);

    } catch (\PhpParser\Error $e) {
      throw new PHPtoCExtException("PHP Parser Error: ".$e->getMessage());
    }

  } 
}
