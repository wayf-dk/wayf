<?php
include "../lib/gearman.lib.php";
$w = new \dk\wayf\gearman();

$w->set_client_id('AntonBanton');
print_r($w->can_do('reverse'));

$data = 'data';
$warning = 'warning';
$exception = 'exception';
$complete = 'complete';

do {
	while(!($w->pre_sleep(1000))) {
		print_r("returned from sleep ...\n");
	};
	$job = $w->grab_job();
	print_r($job);
	$jh = $job[$w::jobhandle];
	$w->work_data($jh, $data);
	$w->work_warning($jh, $warning);
	$w->work_exception($jh, $exception);
	$w->work_status($jh, 3, 4);
#	$w->work_fail($jh);
	$w->work_complete($jh, $complete);
	continue;
} while(1);
?>