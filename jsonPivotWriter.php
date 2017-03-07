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
}
