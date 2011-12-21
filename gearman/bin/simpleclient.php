<?php
include "../lib/gearman.lib.php";

$c = new \dk\wayf\gearman('127.0.0.1:4730');
$data = '3 hej';
$c->option_req('Exceptions');

while(1) {
	$jh = $c->submit_job('reverse', '123', $data);
	print_r("$jh\n");
	print_r($c->response(-1)); print "\n";
	print_r($c->response(-1)); print "\n";
	print_r($c->response(-1)); print "\n";
	print_r($c->response(-1)); print "\n";
	print_r($c->response(-1)); print "\n";
	sleep(3);
}
?>