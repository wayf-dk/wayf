<?php
/**
 * Kalmar2 metadata validator
 *
 * PHP version 5
 *
 * The Kalmar2 metadata validator is free software: you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General Public License
 * as published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * The Kalmar2 metadata validator is distributed in the hope that it will be
 * useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser
 * General Public License for more details.
 *
 * You should haveKalmar2 metadata validator. If not, see
 * <http://www.gnu.org/licenses/>.
 *
 * @package    SimpleSAMLphp
 * @subpackege Module
 * @author     Jacob Christiansen <jach@wayf.dk>
 * @copyright  2010 Jacob Christiansen 
 * @license    http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version    SVN: $Id$
 * @link       http://code.google.com/p/wayf/
 */

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
    /**
     * Can not be used because of bug in FILTER_VALIDATE_URL 
     * (http://bugs.php.net/bug.php?id=51192). Bug should be fixed in PHP 
     * 5.3.3/5.2.14
     */
    //$md_url = filter_var($entity['url'], FILTER_VALIDATE_URL);
	$md_url = $entity['url'];
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
