<?php
ini_set('error_reporting',E_ALL);
ini_set('display_errors','1');

$kv_config = SimpleSAML_Configuration::getConfig('module_kvalidate.php');
$globalConfig = SimpleSAML_Configuration::getConfig();

if(isset($_GET['tag'])) {
	$tags = $kv_config->getArray('tags');
	if(isset($tags[$_GET['tag']])) {
		$tag = $tags[$_GET['tag']];
	} else {
		$error = 'Tag not defined';
		exit;
	}
} else {
	$error = 'No tag given';
	exit;
}

$t = new SimpleSAML_XHTML_Template($globalConfig, 'kvalidate:groupvalidatestatus.tpl.php');

$t->data['entities'] = array();
$t->data['group'] = htmlentities($_GET['tag']);
	
$config['REMOVE_ENTITYDESCRIPTOR'] = $kv_config->getBoolean('remove.entitydescriptor', false);
	
$validator = new sspmod_kvalidate_Validator($config);

ksort($tag);

foreach($tag AS $k => $entity) {
	$md_url = filter_var($entity['url'], FILTER_VALIDATE_URL);
	$xml = file_get_contents($md_url);
	
	$t->data['entities'][$k]['xml'] = $validator->validate($xml);
	$t->data['entities'][$k]['messages'] = $validator->getMessages();
	$t->data['entities'][$k]['status'] = $validator->getStatus();
	$t->data['entities'][$k]['name'] = $entity['name'];
	$t->data['entities'][$k]['description'] = $entity['description'];
	$t->data['entities'][$k]['url'] = $md_url;
}

$t->show();
exit;