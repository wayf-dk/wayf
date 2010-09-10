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
/**
 * Configuration for the Kalmar2 metadata validator
 *
 * The array contains different options. At the moment the options are only
 * used by the goupe validation. The following options can be set:
 * - tags, groups of URLs for metadata
 * - remove.entitydescriptor, if set to TRUE, all EntityDescriptors that do not validate will be removed
 * 
 * @var array
 */
$config = array(
	'tags' => array(
		'kalmar' => array(
			'da' => array(
				'url' => 'https://wayf.wayf.dk/module.php/aggregator/?id=wayfkalmarexport&mimetype=application/xml',
				'name' => 'Denmark',
				'description' => 'Danish metadata',
			),
			'no' => array(
				'url' => 'https://kalmar.feide.no/simplesaml/module.php/aggregator/?id=feidekalmarexport',
				'name' => 'Norway',
				'description' => 'Norwegian metadata',
			),
			'fi' => array(
				'url' => 'https://haka.funet.fi/fed/haka-kalmar-metadata.xml',
				'name' => 'Finland',
				'description' => 'Finish metadata',
			),
			'se' => array(
				'url' => 'https://wayf.swamid.se/md/swamid-kalmar-1.0.xml',
				'name' => 'Sweden',
				'description' => 'Swedish metadata',
			),
		),
		'kalmar-full' => array(
			'kalmar' => array(
				'url' => 'https://kalmar2.org/simplesaml/module.php/aggregator/?id=kalmarcentral&set=saml2',
				'name' => 'Kalmar2',
				'description' => 'Kalmar2 full metadata',
			),
		),
		'kalmar-test' => array(
			'da' => array(
				'url' => 'https://betawayf.wayf.dk/module.php/aggregator/?id=wayfkalmarexport&mimetype=application/xml',
				'name' => 'Denmark',
				'description' => 'Danish test metadata',
			),
			'is' => array(
				'url' => 'https://betawayf.wayf.dk/module.php/aggregator/?id=icelandkalmarexport&mimetype=application/xml',
				'name' => 'Iceland',
				'description' => 'Icelandic test metadata',
			),
			'no' => array(
				'url' => 'https://kalmar.feide.no/simplesaml/module.php/aggregator/?id=feidekalmarexport',
				'name' => 'Norway',
				'description' => 'Norwegian metadata',
			),
			'fi' => array(
				'url' => 'https://haka.funet.fi/fed/haka-kalmar-metadata.xml',
				'name' => 'Finland',
				'description' => 'Finish metadata',
			),
			'se' => array(
				'url' => 'https://wayf.swamid.se/md/swamid-kalmar-1.0.xml',
				'name' => 'Sweden',
				'description' => 'Swedish metadata',
			),
		),
		
	),
	
	'remove.entitydescriptor' => false,
);