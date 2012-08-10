<?php
echo $trans->t('HEADLINE');
echo $trans->t('INFOTEXT');

echo '<div id="leftcol">';
echo $trans->t('SUBHEADLINE_CONSENT');
echo $trans->t('CONSENT_INFOTEXT');
echo '<input type="text" id="consent_search" placeholder="Search" style="width: 89%; background-image: url(http://storage.conduit.com/images/searchengines/search_icon.gif);
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

echo '<div id="rightcol">';
echo $trans->t('SUBHEADLINE_NOCONSENT');
echo $trans->t('NOCONSENT_INFOTEXT');
echo '<input type="text" id="noconsent_search" placeholder="Search" style="width: 89%; background-image: url(http://storage.conduit.com/images/searchengines/search_icon.gif);
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