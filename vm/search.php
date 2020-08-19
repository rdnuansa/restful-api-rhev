<?php
// required Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// include object file
include_once '../objects/vm.php';

// instantiate object VM
$vm = new Vm();

// Get input data from user
$arr_input = json_decode(file_get_contents("php://input"));
$vm->vmname = $arr_input->vmname;

// logging purpose
$user = $_SERVER['REMOTE_ADDR'] ." - " . $username;
$action = "GetConfig";
$host = "RHEV";

if (empty($arr_input)) {

    http_response_code(400);
    echo json_encode(array("response" => "400", "message" => "[Unable to search VM. Data is empty.]"), JSON_PRETTY_PRINT);

} else {
           
   // Get VM configuration 
	$vm->getId();
	$output = array (
		"vmname" => "$vm->vmname",
		"cpus" => "$vm->cpu",
		"memory" => "$vm->memory",
		"status" => "$vm->status"
	);
	   
   // check if VM found or not
   if (empty($vm->id)) {
   	
   	// send response not found
		http_response_code(404);
		echo json_encode(array("response" => "404", "message" => "[VM not found]"), JSON_PRETTY_PRINT);
		
   } else {
   	
   	// send response success
   	http_response_code(200);
     	echo json_encode(array("response" => "200", "vm" => $output), JSON_PRETTY_PRINT);
     	
    }
}
?>