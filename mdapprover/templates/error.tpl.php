<?php
echo "<h1>Error</h1>";
if (isset($errortitle) ) {
    echo '<h2>' . $errortitle . '</h2>';
}
if (isset($errormsg) ) {
    echo '<p>' . $errormsg . '</p>';
} else {
    echo '<p>An error occured. Please contact the system administrator.</p>';
}
