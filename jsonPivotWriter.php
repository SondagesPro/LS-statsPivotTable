<?php
class jsonPivotWriter extends Writer
{
    /**
     * The open filehandle
     */
    private $file = null;
    /**
     * first don't need seperator
     */
    protected $havePrev = false;

    public function init(SurveyObj $survey, $sLanguageCode, FormattingOptions $oOptions)
    {
        parent::init($survey, $sLanguageCode, $oOptions);
        $sStartOutput='[';
        if ($oOptions->output=='display') {
            header("Content-type: application/json");
            echo $sStartOutput;
        } elseif ($oOptions->output == 'file') {
            $this->file = fopen($this->filename, 'w');
            fwrite($this->file, $sStartOutput);
        }

    }

    protected function outputRecord($headers, $values, FormattingOptions $oOptions)
    {
        $aJson=array_combine ($headers,$values);
        $sJson=json_encode($aJson);
        if($this->havePrev){
            $sJson=','.$sJson;
        }
        $this->havePrev=true;
        if ($oOptions->output=='display')
        {
            echo $sJson;
        } elseif ($oOptions->output == 'file') {
            fwrite($this->file, $sJson);
        }

    }

    /**
     * @see Writer::getFullHeading , replace with code + abbreviated heading
     */
    public function getFullHeading(SurveyObj $survey, FormattingOptions $oOptions, $fieldName){
        $oOptions->headingTextLength=30;
        $headingText=parent::getAbbreviatedHeading($survey,$oOptions,$fieldName);
        $oOptions->useEMCode=true;
        $headingCode=parent::getHeadingCode($survey,$oOptions,$fieldName);
        return $headingCode." ".$headingText."";
    }
    /**
     * @see Writer::getLongAnswer , replace with code + abbreviated heading
    */
    public function getLongAnswer(SurveyObj $oSurvey, FormattingOptions $oOptions, $fieldName,$sValue)
    {
        if(is_null($sValue)){
            return '';
        }
        $answer= parent::getLongAnswer($oSurvey,$oOptions, $fieldName,$sValue);
        if($this->needCode($oSurvey->fieldMap[$fieldName]['type'],$fieldName)){
            $answer = parent::getShortAnswer($oSurvey,$oOptions, $fieldName,$sValue).":".$answer;
        }
        return $answer;
    }

    public function close()
    {
        $sEndOutput=']';
        if (!$this->file)
        {
            echo $sEndOutput;
        }
        else
        {
            fwrite($this->file, $sEndOutput);
            fclose($this->file);
        }
    }

    /*
    * Get if field need a code
    * @param string $sFieldType : the field type
    * @param string $sFieldName : the field name
    * @return boolean
    */
    public function needCode($sFieldType,$sFieldName)
    {
        // Have only code : 5 point, arry 5 point, arry 10 point, language (this one can/must be fixed ?)
        $aOnlyCode=array("5","A","B","I");
        // Not need code
        $aNotneedCode=array("G","Y");
        // Have only text : Text and numeric + file upload language (this one can/must be fixed ?)
        $aOnlyText=array("K","N","Q","S","T","U","X",'*',';',':',"|","D");
        // No field type, but some can have specific answers (date) : maybe default must be true ?
        $aDateField=array("submitdate","startdate","datestamp");
        $aInfoField=array("id","lastpage","startlanguage","ipaddr","refurl");

        // Have other question type
        $aOtherType=array("L","M","P","!");
        // Have comment question type
        $aCommentType=array("O","P");
        if(in_array($sFieldType,$aOnlyCode))
            return false;
        if(in_array($sFieldType,$aNotneedCode))
            return false;
        if(in_array($sFieldType,$aOnlyText))
            return false;
        if(in_array($sFieldName,$aInfoField) || in_array($sFieldName,$aDateField))
            return false;
        if(in_array($sFieldType,$aOtherType) && substr_compare($sFieldName, "other", -5, 5) === 0)
            return false;
        if(in_array($sFieldType,$aCommentType))
            return false;
        return true;
    }

}
