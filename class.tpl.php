<?php 
class tpl extends Smarty{

	public function __construct(){

		parent::__construct();


		//set our folders;
        $this->setTemplateDir(dirname(__FILE__).'/template/pages/')
            ->setCompileDir(dirname(__FILE__).'/template/compiled/')
            ->setPluginsDir(dirname(__FILE__).'/template/plugins/')
            ->setCacheDir(dirname(__FILE__).'/template/cache/')
            ->setConfigDir(dirname(__FILE__).'/template/config/');
	}
}


 ?>