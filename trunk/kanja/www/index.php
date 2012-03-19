<?php
//TODO
//Dont refresh inactive tabs.
//Memory leak, hvad skal der ske?

include('../lib/sporto.php');

session_start();

//Disable sporto////////////////
//$_SESSION['SAML'] = array(
//	'eduPersonPrincipalName' => array('jj@testidp.wayf.dk'),
//	'organizationName' => array('Institution'),
//	'eduPersonPrimaryAffiliation' => array('student')
//);
///////////////////////////////

if(!isset($_SESSION['SAML'])) {
  $config = new config();
  $_SESSION['SAML'] = sporto($config);
}

$SAML = $_SESSION['SAML'];
$uid = $SAML['eduPersonPrincipalName'][0];
$iid = $SAML['schacHomeOrganization'][0];
$role = $SAML['eduPersonPrimaryAffiliation'][0];

$dbhandle = sqlite_open('../db/links.db', 0666, $error);

if (!$dbhandle) die ($error);

//Sanitize, use PDO prepared statements.
$query = "SELECT * FROM Tabs WHERE tabid IN (SELECT tabid FROM Access where (iid = '$iid' OR iid IS NULL) AND (role = '$role' OR role IS NULL) AND (uid = '$uid' OR uid IS NULL));";
$query = "SELECT * FROM Tabs;";

$result = sqlite_query($dbhandle, $query);
if (!$result) die("Cannot execute query.");

$data = sqlite_fetch_all($result, SQLITE_BOTH);

$tapas = array();

foreach($data as $row)
{
	$tapas[$row[0]] = array ('url' => $row[2], 'name' => $row[1], 'open' => FALSE);
} 
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>KANJA - By WAYF</title>
        <meta charset="utf-8" />
        <meta name="application-name" content="KANJA" />
        <meta name="robots" content="none" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
        <style type="text/css">
            body { font-family:Lucida Sans, Lucida Sans Unicode, Arial, Sans-Serif; font-size:13px; margin:0px auto;}
            #tabs { margin:0; padding:0 10px; list-style:none; overflow:hidden;}
            #tabs li { float:left; display:block; padding:5px; background-color:#bbb; margin-right:5px; border-radius: 2px 2px 0 0; -moz-border-radius: 2px 2px 0 0; -webkit-border-radius: 2px 2px 0 0;}
            #tabs li a { color:#fff; text-decoration:none;}
            #tabs li.current { background-color:#e1e1e1;}
            #tabs li.current a { color:#000; text-decoration:none;}
            #tabs li a.remove { color:#f00; margin-left:10px;}
            #content { background-color:#e1e1e1; padding: 10px 10px 10px 10px; border-radius:2px; -moz-border-radius:2px; -webkit-border-radius:2px;}
            #content p { margin: 0; padding:20px 20px 100px 20px;}
            #main { margin: 20px 10px 0px 10px; overflow:hidden;background-color:#F6F6F6; border-radius:2px 2px 0px 0px; -moz-border-radius:2px 2px 0px 0px; -webkit-border-radius:2px 2px 0px 0px;}
            #wrapper, #doclist { margin:5px 5px 0px 5px;}
            #doclist { border-right:solid 1px #dcdcdc;}
            #doclist ul { margin:0; list-style:none;}
            #doclist li { margin:10px 0; padding:0;}
            #documents { margin:0; padding:0;}
            #header { background-color:#F6F6F6; margin:0px auto; margin-top:20px; border-radius:2px 2px 0px 0px; -moz-border-radius:2px 2px 0px 0px;  -webkit-border-radius:2px 2px 0px 0px; padding:30px; position:relative;}
            #header h2 {font-size:16px; font-weight:normal; margin:0px; padding:0px;}
            #kanja_content {padding: 2em; }
            iframe { position: absolute; border: 0; width: 100%; height: 1960px;}
        </style>
        <script type="text/javascript" src="js/jquery-1.5.1.min.js" ></script>
        <script type="text/javascript">
		var tapas = JSON.parse('<?echo json_encode($tapas);?>');

        $(document).ready(function() {
            $('#tabs a.tab').live('click', function() {
                // Get the tab name
                var contentname = $(this).attr("id") + "_content";

                // hide all other tabs
                $("#content div").hide();
                $("#tabs li").removeClass("current");

                // show current tab
                $("#" + contentname).show();
				$("#" + contentname + " > div").show();
                $(this).parent().addClass("current");
            });

            $('#tabs a.remove').live('click', function() {
                // Get the tab name
                var tabid = $(this).parent().find(".tab").attr("id");
				
                // remove tab and related content
                var contentname = tabid + "_content";
                $("#" + contentname).remove();
                $(this).parent().remove();

                // if there is no current tab and if there are still tabs left, show the first one
                if ($("#tabs li.current").length == 0 && $("#tabs li").length > 0) {

                    // find the first tab    
                    var firsttab = $("#tabs li:first-child");
                    firsttab.addClass("current");

                    // get its link name and show related content
                    var firsttabid = $(firsttab).find("a.tab").attr("id");
                    $("#" + firsttabid + "_content").show();
                }
				removeTab(tabid);
            });
			loadTabs();
			timer();
        });

        var t;

	function loadTabs() {
		var i, x, cookies, cookieTapas, tab, id;
		cookies = document.cookie.split(";");
		cookieTapas = new Array();
		for (i = 0; i < cookies.length; i++)
		{
		  x = cookies[i].substr(0, cookies[i].indexOf("="));
		  x = x.replace(/^\s+|\s+$/g, "");
		  if (x=='tapas') {
			x = cookies[i].substr(cookies[i].indexOf("=") + 1);
			cookieTapas = JSON.parse(x);
		  }
		}
		for (i = 0; i < cookieTapas.length; i++) {
			tab = cookieTapas[i];
			id = tab[0];
			if(tapas.hasOwnProperty(id)) {
				addTab(id, false);
				tapas[id].refresh.checked = tab[1];
				tapas[id].seconds.value = tab[2];
			}
		}
	}
	
	function removeTab(id) {
		tapas[id].open = false;
		updateCookie();
	}
	
	function updateCookie()
	{
	  var ids, tab;
	  ids = Array();
	  for(id in tapas) {
	    if(tapas[id].open) {
	      tab = [id, tapas[id].refresh.checked, tapas[id].seconds.value];
	      ids[ids.length] = tab;
	    }
	  }
	  var exdate=new Date();
	  exdate.setFullYear(2037);
	  document.cookie = "tapas = " + JSON.stringify(ids) + "; expires=" +exdate.toUTCString();
	}

	function timer() {
		t = setTimeout("timer()", 1000);
		refreshAll();
	}

	function refreshAll() {
		var tab;
	    for (id in tapas) {
	      tab = tapas[id];
	      if(tab.open && tab.refresh.checked) {
		tab.timer = tab.timer - 1;
		if(tab.timer <= 0) {
		  refresh(id);
		  tab.timer = parseInt(tab.seconds.value);
		}
	      }
	    }
	}
	
	function undock(id) {
		var w = window.open(tapas[id].url);
	}
	
	function refresh(id) {
		var x, tab;
		try {
			tab = tapas[id];
			if(tab.refreshing)
				return;
			tab.refreshing = true;
			tab.iback.contentWindow.location = tab.url;
		}
		catch(err) {
			alert(err);
		}
	}
	
	function swapIFrames(tab) {
	  if(tab.refreshing) {
		var x;
		//Swap front and back iframe
		x = tab.ifront;
		tab.ifront = tab.iback;
		tab.iback = x;
		tab.ifront.style.zIndex = 1;
		tab.iback.style.zIndex = -1;
	  }
	}

	function addTab(id, update) {
	
		var contentname, tab;
		contentname = id + "_content";
		tab = tapas[id];
		// If tab already exist in the list, return
		if ($("#"+id).length != 0) {
			// Get the tab name
			

			// hide all other tabs
			$("#content div").hide();
			$("#tabs li").removeClass("current");

			// show current tab
			$("#" + contentname).show();
			$("#" + contentname + " > div").show();
			$("#" + id).parent().addClass("current");
			//$(link).parent().addClass("current");
			
			return;
		}
		
		// hide other tabs
		$("#tabs li").removeClass("current");
		$("#content div").hide();
		
		// add new tab and related content
		$("#tabs").append("<li class='current'><a class='tab' id='" +
			id + "' href='#'>"  + tab.name +
			"</a><a href='#' class='remove'>x</a></li>");

		$("#content").append('<div style="width: 100%; height: 2000px;" id=' + contentname + "></div>");
		var div = document.getElementById(contentname);

		
		////// Div with refresh and open window buttons
		var divdiv = document.createElement('div');
		div.appendChild(divdiv);
		
		var x = document.createElement('input');
		x.setAttribute('type', 'checkbox');
		x.setAttribute('onchange', 'updateCookie();');
		
		divdiv.appendChild(x);
		divdiv.appendChild(document.createTextNode("Auto refresh every "));
		tab.refresh = x

		x = document.createElement('input');
		x.setAttribute('value', '10');
		x.setAttribute('style', 'width:24px;');
		x.setAttribute('max', 'width:24px;');
		x.setAttribute('onchange', 'updateCookie(); tapas[\''+id+'\'].timer = 0;');
		divdiv.appendChild(x);
		divdiv.appendChild(document.createTextNode(" seconds."));
		tab.seconds = x;
		
		x = document.createElement('button');
		x.setAttribute('onClick', 'refresh(\''+id+'\')');
		x.appendChild(document.createTextNode("Refresh now"));
		divdiv.appendChild(x);
		
		x = document.createElement('button');
		x.setAttribute('onClick', 'undock(\''+id+'\')');
		x.appendChild(document.createTextNode("Open window"));
		divdiv.appendChild(x);
		////////
		
		//////// Div with iframes
		divdiv = document.createElement('div');
		divdiv.setAttribute('id','iframediv');
		width = $('#'+id+'_content').width() - 9;
		divdiv.setAttribute('style', "position: relative; border: 0; width: 100%; height: 100%;");
		div.appendChild(divdiv);
		
		//Front iframe
		x = document.createElement('iframe');
		x.setAttribute('style', "z-index:1;");
		x.setAttribute('src', tab.url);
		divdiv.appendChild(x);
		x.onload = function() { swapIFrames(tab); tab.refreshing = false; };
		tab.ifront = x;
		
		//Div concealing back iframe
		x = document.createElement('div');
		x.setAttribute('style', "background-color:#e1e1e1; position: absolute; border: 0; width: 100%; height: 1960; z-index:0;");
		divdiv.appendChild(x);
		
		//Back iframe
		x = document.createElement('iframe');
		x.setAttribute('style', " z-index:-1;");
		divdiv.appendChild(x);
		x.onload = function() { swapIFrames(tab); tab.refreshing = false; };
		tab.iback = x;
		
		$("#" + id).show();
		
		tab.refreshing = false;
		tab.open = true;
		tab.timer = 0;
		if(update) {
			updateCookie();
		}
		// set the newly added tab as current
	}
    </script>
</head>
<body>
	<div id="main">
		<div id="wrapper">
			<ul id="tabs">
				<li class="current">
					<a href="#" class="tab" id=kanja>
						Kanja Dashboard</a>
				</li>
			</ul>
			<div id="content">
				<div id="kanja_content" style="height: 2000px;">
				<ul id="documents">
				  <?foreach($tapas as $tabid => $tab) {?>
				    <li>
				      <a href="#" onClick="addTab('<?echo $tabid?>', true);" title="<?echo $tab['url']?>"> <?echo $tab['name']?> </a>
				    </li>
				  <?}?>
				</ul>
				</div>
			</div>
		</div>
	</div>
	<?/*
	<div>
		<p> SAML Session </p>
		<pre>
		<?print_r($query);?>

		<?print_r($SAML);?>
		</pre>
	</div>
	*/?>
</body>
</html>
