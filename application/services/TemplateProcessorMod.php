<?php

namespace Services;



class TemplateProcessorMod extends \PhpOffice\PhpWord\TemplateProcessor {

    public function __construct($documentTemplate){
        parent::__construct($documentTemplate);
    }

    public function cloneBlock($blockname, $clones = 1, $replace = true, $indexVariables = false, $variableReplacements = null)
    {
        $cloneXML = '';
        $replaceXML = null;


        // location of blockname open tag
        $startPosition = strpos($this->tempDocumentMainPart, '${' . $blockname . '}');

        if ($startPosition) {
            // start position of area to be replaced, this is from the start of the <w:p before the blockname
            $startReplacePosition = strrpos($this->tempDocumentMainPart, '<w:p ',
                -(strlen($this->tempDocumentMainPart) - $startPosition));

            // start position of text we're going to clone, from after the </w:p> after the blockname
            $startClonePosition = strpos($this->tempDocumentMainPart, '</w:p>', $startPosition) + strlen('</w:p>');

            // location of the blockname close tag
            $endPosition = strpos($this->tempDocumentMainPart, '${/' . $blockname . '}');
            if ($endPosition) {
                // end position of the area to be replaced, to the end of the </w:p> after the close blockname
                $endReplace = strpos($this->tempDocumentMainPart, '</w:p>', $endPosition) + strlen('</w:p>');
                // end position of the text we're cloning, from the start of the <w:p before the close blockname
                $endClone = strrpos($this->tempDocumentMainPart, '<w:p ',
                    -(strlen($this->tempDocumentMainPart) - $endPosition));
                $cloneLength = ($endClone - $startClonePosition);
                $replaceLength = ($endReplace - $startReplacePosition);

                $cloneXML = substr($this->tempDocumentMainPart, $startClonePosition, $cloneLength);
                $replaceXML = substr($this->tempDocumentMainPart, $startReplacePosition, $replaceLength);
            }
        }

        if ($replaceXML != null) {
            $cloned = array();
            for ($i = 1; $i <= $clones; $i++) {
                $cloned[] = $cloneXML;
            }

            if ($replace) {
                $this->tempDocumentMainPart = str_replace($replaceXML, implode('', $cloned),
                    $this->tempDocumentMainPart);
            }
        }


        return $cloneXML;

    }

}