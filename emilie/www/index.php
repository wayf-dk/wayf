<?php
include '_init.php';

// Get order field
$allowed_order = array('name_en', 'name_da');
$order = "name_en";
if (isset($_GET['o'])) {
    if (in_array($_GET['o'], $allowed_order)) {
        $order = $_GET['o'];
    }
}

// Get operating mode SP og IdP
$rElmData = array(array('id' => 'newidp', 'name' => 'New Institution'));
$type = 'sp';
if (isset($_GET['t'])) {
    switch ($_GET['t']) {
    case 'sp':
        $type = 'idp';
        $rElmData = array(array('id' => 'newidp', 'name' => 'New Service'));
        break;
    case 'idp':
    default:
        $type = 'sp';
        $rElmData = array(array('id' => 'newidp', 'name' => 'New Institution'));
    }
}

// Get data from database
try {
    $db = new \WAYF\DB($emilie_config['database']['dsn'], $emilie_config['database']['user'], $emilie_config['database']['pass']);

    $query = "SELECT * FROM `" . $emilie_config['database']['table'] . "` WHERE `sporidp` = :type ORDER BY `" . $order . "`;";
    $res = $db->fetch_all($query, array(':type' => $type));

    $data = array();
    foreach ($res AS $key => $val) {
        if ($type == 'sp') {
            $data['left' . $val->id] = $val;
        } else {
            $data['right' . $val->id] = $val;
        }
    }
    foreach ($res AS $key => $val) {
        $val->name = $val->{$order} . " [" . $val->integration_costs . "]";
    }
} catch (\PDOException $e) {
    // Something went horrible wrong
    die('Something went wrong. Try again or contact system administrator');
}
?>
<!DOCTYPE html>
<html>
    <head>
        <?php
            if ($type == 'sp') {
                echo "<title>EMILIE by WAYF</title>";
            } else {
                echo "<title>EILIME by WAYF</title>";
            }
        ?>
        <meta http-equiv="X-UA-Compatible" content="IE=9"/>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
        <meta charset="utf-8" />
        <link rel="stylesheet" type="text/css" href="css/style.css"/>
        <script type="text/javascript" src="js/d3.v2.min.js"></script>
        <script type="text/javascript" src="js/moth.js"></script>
        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
        <script type="text/javascript">
            // Config
            var mothconfig = {
                boxW : 450,
                wayfY: 5
            };

            var calcCost = function() {
                // Get all checked
                var s = d3.select('#moth').selectAll(".checked");
                var len = s[0].length;
                var cost = 0;
                // calc cose
                for (var i = 0;  i < len; i++) {
                    if (data.hasOwnProperty(s[0][i].parentNode.parentNode.id)) {
                        cost += parseInt(data[s[0][i].parentNode.parentNode.id].integration_costs);
                    }
                }

                // Insert cost
                $('#p2pcost').html(cost);
                $("#benefit").html(cost - $('#integrationcost').val());
            };

            var data = <?php echo json_encode($data) ?>;

            <?php
            if ($type == 'sp') {
                echo "var lElements = " . json_encode($res) . ";\n";
                echo "var rElements = " . json_encode($rElmData) . ";\n";
                echo "mothconfig.leftY = 1;\n";
                echo "mothconfig.rightY = 5;\n";
                echo "mothconfig.checkboxLeft = true;\n";
                echo "mothconfig.checkboxRight = false;\n";
                echo "mothconfig.checkboxLeftFunction = function () {
                    // Set new color on click
                    var tmp = d3.select(this.parentNode);
                    tmp.classed('mark', !tmp.classed('mark'));

                    // Calc new cost
                    calcCost();
                };\n";
            } else {
                echo "var rElements = " . json_encode($res) . ";\n";
                echo "var lElements = " . json_encode($rElmData) . ";\n";
                echo "mothconfig.leftY = 5;\n";
                echo "mothconfig.rightY = 1;\n";
                echo "mothconfig.checkboxLeft = false;\n";
                echo "mothconfig.checkboxRight = true;\n";
                echo "mothconfig.checkboxRightFunction = function () {
                    // Set new color on click
                    var tmp = d3.select(this.parentNode);
                    tmp.classed('mark', !tmp.classed('mark'));

                    // Calc new cost
                    calcCost();
                };\n";
            }
            ?>

            $(document).ready(function () {
                // Draw moth diagram
                moth.draw('moth', lElements, rElements, mothconfig);
                // Attach event to integration cost field
                $('#integrationcost').keyup(function() {
                    calcCost();
                });
                // Calc initial cost
                calcCost();
            });
        </script>
    </head>
    <body>
        <?php
            if ($type == 'sp') {
                echo "<h1>EMILIE - Entity Manager Indicating Level of Incredible Economy</h1>";
            } else {
                echo "<h1>EILIME - Economy Incredible of Level Indicating Manager Entity";
            }
        ?>
        <form onSubmit="return false;">
            <table class=benefit>
                <tr>
                    <?php
                        if ($type == 'sp') {
                            echo "<td>IdP integration costs:</td>";
                        } else {
                            echo "<td>SP integration costs:</td>";
                        }
                    ?>
                    <td>
                        <input type="text" id="integrationcost" on  />
                    </td>
                </tr>
                <tr>
                    <td>Peer to peer integration costs:</td>
                    <td class=r id="p2pcost"></td>
                </tr>
                <tr>
                    <td>Benefit:</td>
                    <td class="r" id="benefit"></td>
                </tr>
            </table>
        </form>
        <div id="moth"></div>
    </body>
</html>
