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
 
function xmlpp($xml) {
	try {
		$xml_obj = new SimpleXMLElement($xml);   
	}
    catch (Exception $e) {
    	return false;
    }
    
    $indent = 0; // current indentation level  
    $pretty = array();  
      
    // get an array containing each XML element  
    $xml = explode("\n", preg_replace('/>\s*</', ">\n<", $xml_obj->asXML()));  

    // shift off opening XML tag if present  
    if (count($xml) && preg_match('/^<\?\s*xml/', $xml[0])) {  
      $pretty[] = array_shift($xml);  
    }  
  
    foreach ($xml as $el) {  
      if (preg_match('/^<([\w])+[^>]*[^\/]{1}>$/U', $el)) {  
          // opening tag, increase indent  
          $pretty[] = str_repeat('#SPACE#', $indent) . $el;  
          $indent++;  
      } else {  
        if (preg_match('/^<\/.+>$/', $el)) {              
          $indent--;  // closing tag, decrease indent  
        }  
        if ($indent < 0) {  
          $indent = 0;  
        }  
        $pretty[] = str_repeat('#SPACE#', $indent) . $el;  
      }  
    }   
      	
    $xml = implode("\n", $pretty);     

	// Color the XML
    $xml = htmlspecialchars($xml);
	$xml = preg_replace("#&lt;([/]*?)(.*)([\s]*?)&gt;#sU", "<font color=\"#0000FF\">&lt;\\1\\2\\3&gt;</font>", $xml);
    $xml = preg_replace("#&lt;([\?])(.*)([\?])&gt;#sU", "<font color=\"#800000\">&lt;\\1\\2\\3&gt;</font>", $xml);
    $xml = preg_replace("#&lt;([^\s\?/=])(.*)([\[\s/]|&gt;)#iU", "&lt;<font color=\"#808000\">\\1\\2</font>\\3", $xml);
    $xml = preg_replace("#&lt;([/])([^\s]*?)([\s\]]*?)&gt;#iU", "&lt;\\1<font color=\"#808000\">\\2</font>\\3&gt;", $xml);
    $xml = preg_replace("#([^\s]*?)\=(&quot;|')(.*)(&quot;|')#isU", "<font color=\"#800080\">\\1</font>=<font color=\"#FF00FF\">\\2\\3\\4</font>", $xml);
	// To use the regex below you need to set the backtrack limit to 10000000 or higher
	// ini_set('pcre.backtrack_limit', 10000000);
	//$xml = preg_replace("#&lt;(.*)(\[)(.*)(\])&gt;#isU", "&lt;\\1<font color=\"#FF0080\">\\2\\3\\4</font>&gt;", $xml);
	$xml = preg_replace('/#SPACE#/', '&nbsp', $xml);
	$xml = rtrim($xml);
	
	return nl2br($xml);
}  

// --------------------------------------------------------------
$this->data['header'] = $this->t('{kvalidate:kvalidate:title}');

$this->data['head'] = '
	<style type="text/css">
		.num {
			float: left;
			color: gray;
			font-size: 13px;
			font-family: monospace;
			text-align: right;
			margin-right: 6pt;
			padding-right: 6pt;
			border-right: 1px solid gray;
		}
		.xml {
			font-size: 13px;
			font-family: monospace;
			text-align: left;
		}
		body {margin: 0px; margin-left: 5px;}
		td {vertical-align: top;}
		code {white-space: nowrap;}
	</style>';


$this->includeAtTemplateBase('includes/header.php');

echo '<h1>' . $this->t('{kvalidate:kvalidate:validation_header}') . '</h1>';
echo '<h2>' . $this->t('{kvalidate:kvalidate:input_header}') . '</h2>';
echo '
<form method="post" action="?">
	<table style="width: 95%;">';
if(isset($this->data['show_md_url'])) {
    echo '
		<tr>
			<td style="padding-right: 10px; width: 20%;">URL:</td>
			<td>
				<input type="text" name="md_url" value="';
    if(isset($this->data['md_url'])) {
	    echo $this->data['md_url'];
    } 
    echo '" style="width: 99%;"/>
			</td>			
            </tr>';
} else {
    echo '
        <tr>
            <td style="width: 20%;">XML:</td>
            <td>
            <textarea name="md_xml" style="width: 100%;" rows=20></textarea>
            </td>
        </tr>';
}
echo '
		<tr>
			<td>Show completed checks:</td>
			<td>
				<input type="checkbox" name="show_success">
			</td>
		</tr>
		<tr>
			<td>Show warnings:</td>
			<td>
				<input type="checkbox" name="show_warning" checked="checked">
			</td>
		</tr>
		<tr>
			<td>Show XML:</td>
			<td>
				<input type="checkbox" name="show_xml" checked="checked">
			</td>
		</tr>
		<tr>
			<td>Remove invalid EntityDescriptor:</td>
			<td>
				<input type="checkbox" name="remove_ed">
			</td>
		</tr>
        <tr>
            <td colspan="2">
                <input type="submit" name="submit" value="' . $this->t('{kvalidate:kvalidate:validate}') . '" />			
            </td>
        </tr>
	</table>
</form>';
	
if(isset($this->data['xml'])) {
	echo '<h2>' . $this->t('{kvalidate:kvalidate:result_header}') . '</h2>';
	echo '<p>' . $this->t('{kvalidate:kvalidate:status_description}') . '</p>';	
	
	if($this->data['status'] == KV_STATUS_SUCCESS || $this->data['status'] == KV_STATUS_WARNING) {
		echo '<p style="text-align: center; font-size: 20px; font-weight: bold; border: 1px solid #000000; width: 95%;"><img src="/' . $this->data['baseurlpath'] . 'resources/icons/checkmark48.png" alt="Metadata is valid" style="display: inline; padding-left: 10px;" />' . '</h2>';
		echo $this->t('{kvalidate:kvalidate:status_ok}');
		echo '<img src="/' . $this->data['baseurlpath'] . 'resources/icons/checkmark48.png" alt="Metadata is valid" style="display: inline; padding-left: 10px;" />' . '</p>';
	} else {
		echo '<p style="text-align: center; font-size: 20px; font-weight: bold; border: 1px solid #000000; width: 95%;">' . $this->t('{kvalidate:kvalidate:status_error}') . '</p>';
	}
	
	echo '<h2>' . $this->t('{kvalidate:kvalidate:message_header}') . '</h2>';
	
	foreach($this->data['messages'] AS $msg) {
		if($msg['level'] == KV_STATUS_SUCCESS && $this->data['show_success']) {
			echo '<div style="border: 1px solid #7fa748; background: #5F911B; margin-bottom: 10px; padding: 5px; color: white; font-weight: bold; width: 95%;">';
			//echo '<img src="/' . $this->data['baseurlpath'] . 'resources/icons/checkmark48.png" alt="Metadata is valid" style="float: left;" />';
			//echo 'In line: ' . $msg['line'] . '<br />';
			echo $msg['msg'];
			echo '</div>';
		} else if($msg['level'] == KV_STATUS_ERROR) {
			echo '<div style="border: 1px solid #000000; background: #DD6D07; margin-bottom: 10px; padding: 5px; min-height: 48px; width: 95%;">';
			echo '<img src="/' . $this->data['baseurlpath'] . 'resources/icons/experience/gtk-dialog-error.48x48.png" alt="Metadata is NOT valid" style="float: left; padding-right: 5px;" />';
			echo 'In line: ' . $msg['line'] . '<br />';
			echo $msg['msg'];
			echo '</div>';
		} else if($msg['level'] == KV_STATUS_WARNING && $this->data['show_warning']) {
			echo '<div style="border: 1px solid #000000; background-color: #AAAA00; margin-bottom: 10px; padding: 5px; min-height: 48px; width: 95%;">';
			echo '<img src="/' . $this->data['baseurlpath'] . 'resources/icons/experience/gtk-dialog-warning.48x48.png" alt="Metadata is NOT valid" style="float: left; padding-right: 5px;" />';
			echo 'In line: ' . $msg['line'] . '<br />';
			echo $msg['msg'];
			echo '</div>';
		}
	}
	
	if($this->data['show_xml']) {
		// Display the XML source with line numbers
		echo '<h2>' . $this->t('{kvalidate:kvalidate:xml_source}') . '</h2>';
		echo '<p><b>NOTE</b> Do not copy paste the XML below as it is formatted for easy reading and is not correct. Please use the original source.</p>';
		
		if($xml = xmlpp($this->data['xml'])) {
			$lines = implode(range(1, preg_match_all('/\<br\s\/\>/', $xml, $matches)+1), '<br />');	
		
			echo '<table><tr><td class="num">' . $lines . '</td><td class="xml">' . $xml . '</td></tr></table>';
		} else {
			echo '<p>The XML surce can not be displayed</p>';
		}
	}
}

$this->includeAtTemplateBase('includes/footer.php');
