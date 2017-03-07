<?php

/**
 * statsPivotTable
 *
 *
 * @category Plugin
 */

class statsPivotTable extends \ls\pluginmanager\PluginBase
{
    static protected $description = 'See dynamic statitics with pivot table';
    static protected $name = 'statsPivotTable';

    protected $storage = 'DbStorage';

    /**
     * Init stuff
     *
     * @return void
     */
    public function init()
    {
        //~ $this->subscribe('newDirectRequest');
        //~ $this->subscribe('afterSurveyMenuLoad');
        /* To add the menu icon in javascript */
        //~ $this->subscribe('beforeControllerAction');
        /* Add language */
        $this->subscribe('beforeToolsMenuRender');

        /* Add the link in menu */
        $this->subscribe('beforeToolsMenuRender');

        /* Display the json */
        $this->subscribe('newDirectRequest');
        $this->subscribe('listExportPlugins');
        $this->subscribe('newExport');

    }

    /**
     * see beforeToolsMenuRender event
     *
     * @return void
     */
    public function beforeToolsMenuRender()
    {
        $event = $this->getEvent();
        $surveyId = $event->get('surveyId');

        $href = Yii::app()->createUrl(
            'admin/pluginhelper',
            array(
                'sa' => 'sidebody',
                'plugin' => get_class($this),
                'method' => 'actionIndex',
                'surveyId' => $surveyId
            )
        );

        $menuItem = new \ls\menu\MenuItem(
            array(
                'label' => $this->_translate('Pivot table'),
                'iconClass' => 'fa fa-table',
                'href' => $href
            )
        );
        $event->append('menuItems', array($menuItem));
    }

    /**
     * Main function
     * @param int $surveyId Survey id
     *
     * @return string
     */
    public function actionIndex($surveyId)
    {
        if(!Permission::model()->hasSurveyPermission($surveyId,'statistics','read')){
            throw new CHttpException(401);
        }
        $aData=array();
        /* Language part */
        $aData['gT']['title']=$this->_translate('Pivot table');
        /* Usage part */

        $jsOptions=array(
            'jsonUrl'=>Yii::app()->createUrl('plugins/direct',array(
                'plugin'=>get_class($this),
                'action'=>'view',
                'sid'=>$surveyId
            )),
            'lang'=>array(

            ),
        );

        $jsInit = "var LS = LS || {};\n"
                . "LS.plugin = LS.plugin || {};\n"
                . "LS.plugin.statsPivotTable = ".json_encode($jsOptions).";\n";
        $assetUrl=Yii::app()->assetManager->publish(dirname(__FILE__) . '/assets');
        $pivotAssetUrl=Yii::app()->assetManager->publish(dirname(__FILE__) . '/vendor/pivottable/dist');
        $c3AssetUrl=Yii::app()->assetManager->publish(dirname(__FILE__) . '/vendor/c3');
        $d3AssetUrl=Yii::app()->assetManager->publish(dirname(__FILE__) . '/vendor/d3');

        Yii::app()->clientScript->registerScript('statsPivotTable-jsInit',$jsInit,CClientScript::POS_END);
        Yii::app()->clientScript->registerScriptFile($d3AssetUrl."/d3.js",CClientScript::POS_BEGIN);
        Yii::app()->clientScript->registerScriptFile($c3AssetUrl."/c3.js",CClientScript::POS_BEGIN);
        Yii::app()->clientScript->registerScriptFile($pivotAssetUrl."/pivot.js",CClientScript::POS_BEGIN);
        Yii::app()->clientScript->registerScriptFile($pivotAssetUrl."/d3_renderers.js",CClientScript::POS_BEGIN);
        Yii::app()->clientScript->registerScriptFile($pivotAssetUrl."/c3_renderers.js",CClientScript::POS_BEGIN);
        Yii::app()->clientScript->registerScriptFile($pivotAssetUrl."/export_renderers.js",CClientScript::POS_BEGIN);

        Yii::app()->clientScript->registerScriptFile($assetUrl."/statsPivotTable.js",CClientScript::POS_BEGIN);

        Yii::app()->clientScript->registerCssFile($c3AssetUrl."/c3.css");
        Yii::app()->clientScript->registerCssFile($pivotAssetUrl."/pivot.css");

        Yii::app()->clientScript->registerCssFile($assetUrl."/statsPivotTable.css");

        $content = $this->renderPartial('index', $aData, true);
        return $content;
    }

    /**
     * @see PluginManager->newDirectRequest()
     */
    public function newDirectRequest()
    {
        if($this->event->get('target')!=get_class($this)){
            return;
        }
        $iSurveyId=\Yii::app()->getRequest()->getParam('sid');
        if(!\Permission::model()->hasSurveyPermission($iSurveyId,'statistics','read')){
            throw new \CHttpException(401);
        }
        if (!tableExists('{{survey_' . $iSurveyId . '}}')) {
            throw new \CHttpException(500,'Invalid survey id');
        }
        if(!\SurveyDynamic::model($iSurveyId)->getMaxId()) {
            header("Content-type: application/json");
            echo json_encode(array());
            return;
        }
        $language=\Survey::model()->findByPk($iSurveyId)->language;
        $aFields=array_keys(createFieldMap($iSurveyId,'full',true,false,$language));
        Yii::app()->loadHelper('admin/exportresults');
        Yii::import('application.helpers.viewHelper');
        Yii::setPathOfAlias(get_class($this), dirname(__FILE__));
        Yii::import(get_class($this).".JsonPivotWriter");
        $oFormattingOptions=new \FormattingOptions();
        $oFormattingOptions->responseMinRecord=1;
        $oFormattingOptions->responseMaxRecord=SurveyDynamic::model($iSurveyId)->getMaxId();
        $oFormattingOptions->selectedColumns=$aFields;
        $oFormattingOptions->responseCompletionState='complete';
        $oFormattingOptions->headingFormat='complete';// Maybe make own to have code + abbreviated
        $oFormattingOptions->answerFormat='long';
        $oFormattingOptions->output='display';
        /* Hack action id to set to remotecontrol */
        $action = new stdClass();
        $action->id='remotecontrol';
        Yii::app()->controller->__set('action',$action);
        /* Export as display */
        $oExport=new \ExportSurveyResultsService();
        $content=$oExport->exportSurvey($iSurveyId,$language, 'json-pivot',$oFormattingOptions, '');
    }

    /**
     * @see newExport event
     */
    public function newExport()
    {
        $event = $this->getEvent();
        $type = $event->get('type');
        switch ($type) {
            case "json-pivot":
            default:
                $writer = new jsonPivotWriter();
                break;
        }
        $event->set('writer', $writer);
    }
    /**
     * Registers this export type
     */
    public function listExportPlugins()
    {
        $event = $this->getEvent();
        $exports = $event->get('exportplugins');

        $exports['json-pivot'] = get_class();
        $event->set('exportplugins', $exports);
    }

    public function beforeControllerAction()
    {
        // TODO
    }

    /**
     * Launch a json arry to browser
     * @param mixed $object to display
     */
    private function _displayJson($object)
    {
        header("Content-type: application/json");
        echo json_encode($object);
        Yii::app()->end();
    }

    /**
     * get translation
     * @param string
     * @return string
     */
    private function _translate($string){
        return Yii::t('',$string,array(),get_class($this));
    }
    /**
     * Add this translation just after loaded all plugins
     * @see event afterPluginLoad
     */
    public function afterPluginLoad(){
        // messageSource for this plugin:
        $messageSource=array(
            'class' => 'CGettextMessageSource',
            'cacheID' => get_class($this).'Lang',
            'cachingDuration'=>3600,
            'forceTranslation' => true,
            'useMoFile' => true,
            'basePath' => __DIR__ . DIRECTORY_SEPARATOR.'locale',
            'catalog'=>'messages',// default from Yii
        );
        Yii::app()->setComponent(get_class($this),$messageSource);
    }
}
