<?php 
/**
 * Reformat the code so that class properties come first and class methods come later 
 */
namespace PHPtoCExt\Converter;

class CodeReformatConverter extends \PHPtoCExt\Converter
{
  public function convert()
  {
    $classMap = $this->getClassMap();

    foreach($classMap as $className => $classInfo) {

      $originalClassContent = implode("\n",array_slice($this->codeLines, $classInfo->startLine - 1, $classInfo->endLine - $classInfo->startLine + 1));

      $content = "";
      $abstract = $classInfo->isAbstract ? "abstract " : "";
      $content .= $abstract."class ".array_pop(explode("\\",$classInfo->className))."\n";
      $content .= "{\n";
      
      ///////// Static Properties //////////// 
      foreach($classInfo->staticProperties as $propertyInfo) {
        $content .= $propertyInfo->code."\n";
      }
      
      //////// Instance Properties ////////// 
      foreach($classInfo->properties as $propertyInfo) {
        $content .= $propertyInfo->code."\n";
      }

      //////// Methods //////////
      foreach($classInfo->methodInfos as $methodInfo) {
        $content .= implode("\n",array_slice($this->codeLines, $methodInfo->startLine - 1, $methodInfo->endLine - $methodInfo->startLine + 1));
        $content .= "\n"; 
      }
      
      $content .= "}\n";
      $this->searchAndReplace($originalClassContent, $content);
    }
  }
}
