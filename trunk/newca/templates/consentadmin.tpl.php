<?php
// Session information - NOT shown to user
echo '<div style="display: none;">';
echo '<p>Authentication time: ' . date('c', $_SESSION['SAML']['AuthTime']) . '</p>';
echo '</div>';

// Page head
echo $trans->t('INFOTEXT');

// Consent table
echo '<div id="leftcol">';
echo $trans->t('SUBHEADLINE_CONSENT');
echo $trans->t('CONSENT_INFOTEXT');
echo '<input type="text" id="consent_search" placeholder="Search" style="width: 89%; background-image: url(/images/search.gif);
      background-position: right center;
      background-repeat: no-repeat;"/><br /><br />';
echo '<table id="consenttable" class="consenttable">';
if (empty($consentservice)) {
    echo '<tr><td style="text-decoration: none; cursor: default;"></td></tr>';
}
foreach ($consentservice AS $entityid => $data) {
    echo '<tr id="' . $data['serviceid'] . '">';
    echo '<td class="service" id="' . $entityid . '">' . htmlspecialchars($data['name']) . "</td>";
    echo "</tr>";
}
echo '</table>';
echo '</div>';

// No consent table
echo '<div id="rightcol">';
echo $trans->t('SUBHEADLINE_NOCONSENT');
echo $trans->t('NOCONSENT_INFOTEXT');
echo '<input type="text" id="noconsent_search" placeholder="Search" style="width: 89%; background-image: url(/images/search.gif);
      background-position: right center;
      background-repeat: no-repeat;"/><br /><br />';
echo '<table id="noconsenttable" class="consenttable">';
foreach ($noconsentservice AS $entityid => $data) {
    echo '<tr id="' . $data['serviceid'] . '">';
    echo '<td class="service" id="' . $entityid . '">' . htmlspecialchars($data['name']) . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";
