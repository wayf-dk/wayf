<?php
foreach ($this->data['idplist'] AS $idpentry) {
	if (isset($idpentry['name'])) {
		$this->includeInlineTranslation('idpname_' . $idpentry['entityid'], $idpentry['name']);
	} elseif (isset($idpentry['OrganizationDisplayName'])) {
		$this->includeInlineTranslation('idpname_' . $idpentry['entityid'], $idpentry['OrganizationDisplayName']);
	}
	if (isset($idpentry['description']))
		$this->includeInlineTranslation('idpdesc_' . $idpentry['entityid'], $idpentry['description']);
}

if(!array_key_exists('header', $this->data)) {
	$this->data['header'] = 'selectidp';
}
$this->data['header'] = $this->t($this->data['header']);
$this->data['jquery'] = array('version' => '1.6', 'core' => TRUE, 'ui' => TRUE, 'css' => TRUE);

$coords2 = "var tmp = null;\n";
foreach ($this->data['idplist'] AS $idpentry) {
    if(isset($idpentry['GeolocationHint'])) {
        $coords2 .= "tmp = new discotango.IdP(\"" . htmlspecialchars($idpentry['entityid']) . "\", \"" . htmlspecialchars($this->t('idpname_' . $idpentry['entityid'])) . "\");\n";
        foreach($idpentry['GeolocationHint'] AS $hint) {
            $coords2 .= "tmp.addLocation(new google.maps.LatLng(" . $hint['coord'] . "), \"" . $hint['description'] . "\");\n";
        }
        $coords2 .= "discotango.idps[\"" . htmlspecialchars($idpentry['entityid']) . "\"] = tmp;\n";
    }
}

$this->data['head'] = '    
        <meta name="viewport" content="initial-scale=1.0, user-scalable=no" /> 
        <link href="http://code.google.com/apis/maps/documentation/javascript/examples/default.css" rel="stylesheet" type="text/css" /> 
        <script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=true"></script> 
        <script type="text/javascript" src="resources/script/discotango.js"></script> 
        <script type="text/javascript">' . $coords2 . '</script> 
';

$this->data['onLoad'] = 'discotango.init();';

$this->includeAtTemplateBase('includes/header.php');
?>
        <form method="get" action="<?php echo $this->data['urlpattern']; ?>" id="disco_form">
        <input type="hidden" name="entityID" value="<?php echo htmlspecialchars($this->data['entityID']); ?>" />
        <input type="hidden" name="return" value="<?php echo htmlspecialchars($this->data['return']); ?>" />
        <input type="hidden" name="returnIDParam" value="<?php echo htmlspecialchars($this->data['returnIDParam']); ?>" />
		<select id="dropdownlist" name="idpentityid" style="display: none;">
		<?php
		foreach ($this->data['idplist'] AS $idpentry) {
			echo '<option value="'.htmlspecialchars($idpentry['entityid']).'"';
			if (isset($this->data['preferredidp']) && 
				$idpentry['entityid'] == $this->data['preferredidp']) 
				echo ' selected="selected"';
			    echo '>'.htmlspecialchars($this->t('idpname_' . $idpentry['entityid'])).'</option>';
		    }
		?>
		</select>
		<input type="submit" value="<?php echo $this->t('select'); ?>" style="display: none;"/>
		</form>

        <div>
            <div id="map_canvas" style="width: 350px; height: 400px; float: left;"></div>
            <div style="float: right; width: 350px;">
		        <p><?php echo $this->t('selectidp_full'); ?></p>
                <h2>Visible IdP's</h2>
                <div id="idp_list">
                </div>
            </div>
        </div>
		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
