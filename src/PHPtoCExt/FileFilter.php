<?php 
namespace PHPtoCExt;

class FileFilter 
{
  private $sourceFile;
  private $targetFile;

  private $postSearches;
  private $postReplaces;

  public function __construct($sourceFile, $targetFile)
  {
    $this->sourceFile = $sourceFile;
    $this->targetFile = $targetFile;
    $this->postSearches = array();
    $this->postReplaces = array();
  }

  public function filter()
  {
    $sourceFileContent = trim(file_get_contents($this->sourceFile));  

    //first, remove all comments in file content 
    $sourceFileContent = $this->removeAllComments($sourceFileContent);
    $sourceFileContent = $this->putBracketsInNewLine($sourceFileContent);

    $parser = new \PhpParser\Parser(new \PhpParser\Lexer);
    $serializer = new \PhpParser\Serializer\XML();

    try {
      $stmts = $parser->parse($sourceFileContent);

      $codeLines = explode("\n", $sourceFileContent);
      $codeASTXML = $serializer->serialize($stmts);
      $codeASTXMLLines = explode("\n", $codeASTXML);

      //load all converters 

      $converterFiles = scandir(__DIR__."/Converter");
      $converterClasses = array();
      foreach($converterFiles as $f) {
        if ($f !== "." && $f !== "..") {
          $fileInfo = new \SplFileInfo($f);
          if ($fileInfo->getExtension() == "php") {
            $converterClasses[] = str_replace(".php","",__NAMESPACE__."\\Converter\\".$fileInfo->getBaseName());
          } 
        }
      }

      $searches = array();
      $replaces = array();
      $postSearches = array();
      $postReplaces = array();
      //go through all converters to convert the source code 
      foreach ($converterClasses as $converterClass) {
        $converter = new $converterClass($codeLines, $codeASTXMLLines);
        $converter->convert();
        $searches = array_merge($searches, $converter->getSearches());
        $replaces = array_merge($replaces, $converter->getReplaces());
        $postSearches = array_merge($postSearches, $converter->getPostSearches());
        $postReplaces = array_merge($postReplaces, $converter->getPostReplaces());
      }

      $this->postSearches = $postSearches;
      $this->postReplaces = $postReplaces;

      $targetFileContent = str_replace($searches, $replaces, $sourceFileContent);
      file_put_contents($this->targetFile, $targetFileContent);

    } catch (\PhpParser\Error $e) {
      throw new PHPtoCExtException("PHP Parser Error: ".$e->getMessage());
    }

  } 

  public function getPostSearches()
  {
    return $this->postSearches;
  } 

  public function getPostReplaces()
  {
    return $this->postReplaces;
  }

  /**
   * remove all comments in php code 
   * credit: http://stackoverflow.com/questions/503871/best-way-to-automatically-remove-comments-from-php-code  
   */
  private function removeAllComments($content)
  {
    $result = '';

    $commentTokens = array(T_COMMENT);

    if (defined('T_DOC_COMMENT'))
      $commentTokens[] = T_DOC_COMMENT; // PHP 5
    if (defined('T_ML_COMMENT'))
      $commentTokens[] = T_ML_COMMENT;  // PHP 4

    $tokens = token_get_all($content);

    foreach ($tokens as $token) {    
      if (is_array($token)) {
        if (in_array($token[0], $commentTokens))
          continue;

        $token = $token[1];
      }

      $result .= $token;
    }

    return $result;
  }

  private function putBracketsInNewLine($content)
  {
    $lines = explode("\n",$content);
    $result = "";
    foreach($lines as $index => $line) {
      $indentLevel = strlen($line) - strlen(ltrim($line));
      $indentation = str_repeat(" ", $indentLevel);
      $lines[$index] = str_replace(array("{","}"), array("\n".$indentation."{", "\n".$indentation."}"), $line);
    }

    $result = implode("\n", $lines);

    //now remove all blank lines, credit: http://stackoverflow.com/questions/709669/how-do-i-remove-blank-lines-from-text-in-php   
    $result = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $result);
    return $result;
  }
}
