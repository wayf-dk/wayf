<?php
ini_set('error_reporting',E_ALL);
ini_set('display_errors','1');

$globalConfig = SimpleSAML_Configuration::getInstance();

$t = new SimpleSAML_XHTML_Template($globalConfig, 'kvalidate:showstatus.tpl.php');

if(isset($_REQUEST['md_url'])) {
	$config = array();
	
	$t->data['show_success'] = isset($_REQUEST['show_success']) ? true : false;	
	$t->data['show_warning'] = isset($_REQUEST['show_warning']) ? true : false;
	$t->data['show_xml'] = isset($_REQUEST['show_xml']) ? true : false;
	$config['REMOVE_ENTITYDESCRIPTOR'] = isset($_REQUEST['remove_ed']) ? true : false;

	$md_url = filter_var($_REQUEST['md_url'], FILTER_VALIDATE_URL);
	$t->data['md_url'] = $md_url;
	$xml = file_get_contents($md_url);
	
	$validator = new sspmod_kvalidate_Validator($config);

	$t->data['xml'] = $validator->validate($xml);
	$t->data['messages'] = $validator->getMessages();
	$t->data['status'] = $validator->getStatus();
}

$t->show();
exit;