<?php
include "../lib/WAYF/Gearman.php";

$c = new \WAYF\Gearman('127.0.0.1:4730');
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
