<!DOCTYPE html>
<html>
    <head>
        <title>moth.js - Demo</title>
        <link rel="stylesheet" type="text/css" href="css/reset.css"/>
        <link rel="stylesheet" type="text/css" href="css/style.css"/>
        <script type="text/javascript" src="js/d3.v2.min.js"></script>
        <script type="text/javascript" src="js/moth.js"></script>
        <script type="text/javascript">
            // Config
            var mothconfig = {
                boxW : 450
            };

            // Test data
            var lElements = [
                {
                    'id': 'id1',
                    'name': 'Entry 1'
                },
                {
                    'id': 'id2',
                    'name': 'Entry 2'
                },
                {
                    'id': 'id3',
                    'name': 'Entry 3'
                },
                {
                    'id': 'id4',
                    'name': 'Entry 4'
                }
            ];

            var rElements = [
                {
                    'id': 'id5',
                    'name': 'Entry 5'
                },
                {
                    'id': 'id6',
                    'name': 'Entry 6'
                },
                {
                    'id': 'id7',
                    'name': 'Entry 7'
                }
            ];
        </script>
    </head>
    <body onLoad="moth.draw('moth', lElements, rElements, mothconfig)">
        <div id="moth"></div>
    </body>
</html>
