<?php
ini_set('display_errors', 1);
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// include object file
include_once '../objects/vm.php';

// instantiate object VM
$vm = new Vm();

$arr_vms = array();

// dump VM list
$result = json_decode($vm->listVm(), true);

foreach ($result as $value) {
	$vmname = $value['name'];
	$memory = $value['memory'];
	$status = $value['status']['state'];
	$cpu = ( $value['cpu']['topology']['sockets'] * $value['cpu']['topology']['cores'] );  	
	$arr_vms[] = array(
		"vmname" => $vmname,
		"cpu" => $cpu,		
		"memory" => $memory,
		"status" => $status
	);	
}

echo json_encode(array("vms" => $arr_vms), JSON_PRETTY_PRINT);

?>