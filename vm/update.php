<?php
// required Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
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
$cpu = $arr_input->cpu;
$memory = $arr_input->memory;

// get current VM configurations
$vm->getId();

if (empty($vm->id)) {
	
	// send response not found
	http_response_code(404);
	echo json_encode(array("response" => "404", "message" => "[VM not found]"), JSON_PRETTY_PRINT);
	
} elseif($vm->isUp()) {
	
	// send failed response, VM is running	
   http_response_code(406);
   echo json_encode(array("response" => "406", "message" => "[Unable to update VM! Poweroff the VM first.]"), JSON_PRETTY_PRINT);
	   	
} else {

	// Check CPU/memory input
	if (!empty($cpu) && !empty($memory)) {
		
		// update both cpu & memory
		$vm->setCpu($cpu);
		$vm->setMemory($memory);    
	    
	} elseif (!empty($cpu)) {
		
		// update cpu only
		$vm->setCpu($cpu);
	    
	} elseif (!empty($memory)) {
		
		// update memory only
		$vm->setMemory($memory);
	
	} else {
		
		$arr_input = null;
	
	}
	
	// logging parameters
	$user = $_SERVER['REMOTE_ADDR'];
	$action = "Update";
	$host = "RHEV";
	
	if (empty($arr_input)) {
				
	   http_response_code(400);
	   echo json_encode(array("response" => "400", "message" => "[Unable to update VM. Data is empty.]"), JSON_PRETTY_PRINT);
	
	} else {
		
		// update VM configuration
		$vm->updateVm();
		
		if($vm->response == 200) {
			
			// log activity
			$vm->log2file($user, $host, $action);

			// send response success
			http_response_code(200);		
			echo json_encode(array("response" => "$vm->response", "messages" => "[OK]" ), JSON_PRETTY_PRINT);
			
		} elseif($vm->response == 404) {
			
			// log activity
			$vm->log2file($user, $host, $action);

			// send response not found
			http_response_code($vm->response);		
			echo json_encode(array("response" => "$vm->response", "messages" => "[VM not found.]" ), JSON_PRETTY_PRINT);
			
		} else {
			
			// log activity
			$vm->log2file($user, $host, $action);
			
			// send failed response
			http_response_code($vm->response);
			echo json_encode(array("response" => "$vm->response", "messages" => "$vm->output"), JSON_PRETTY_PRINT);
		
		}
	}
}
?>