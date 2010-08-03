<?php
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