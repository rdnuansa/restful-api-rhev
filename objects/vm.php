<?php
ini_set('display_errors', 1);
class Vm {
   
   // RHEV API parameters
   private $apiurl = "https://my-rhevm.server.com/api";
   private $username = "admin@internal";
   private $password = "mypassword";
	public $response;
	public $output;
  
	// object properties
	public $id;
   public $vmname;
   public $cpu;
   public $memory;
   public $status;

   // functions definition
   // get list of VMs:
   function listVm() {
   	$ch = curl_init();
   	curl_setopt($ch, CURLOPT_VERBOSE, true);
   	$verbose = fopen("dump", "w+");
   	curl_setopt($ch, CURLOPT_STDERR, $verbose);
   	
   	// retrieve page 1 of VM list
   	curl_setopt($ch, CURLOPT_URL, $this->apiurl . "/vms?search=DEV_CLUSTER%20page%201");
   	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
   	
   	curl_setopt($ch, CURLOPT_USERPWD, "$this->username" . ":" . "$this->password");
   	
   	$headers = array();
   	$headers[] = 'Accept: application/json';
   	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
   	
   	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
   	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		// dump result page 1   	
   	$page1 = curl_exec($ch);

		// retrieve page 2 of VM list
		curl_setopt($ch, CURLOPT_URL, $this->apiurl . "/vms?search=DEV_CLUSTER%20page%202");
		
		// dump result page 2
		$page2 = curl_exec($ch);
				
   	if(curl_errno($ch)) {
			$error = curl_error($ch);			
			curl_close($ch);   	
   		return 'Error - ' . $error;
   	} else {
   		curl_close($ch);
			
			// remove JSON parent key
			$json1 = json_decode($page1);
			$json2 = json_decode($page2);			
			$key = array_keys( get_object_vars($json1))[0];
			$json1 = json_encode($json1->$key);
			$json2 = json_encode($json2->$key);
			
			// merge JSON
			$data1 = json_decode($json1, true);
			$data2 = json_decode($json2, true);
			$arr_merge = array_merge_recursive($data1, $data2);
			
			$json_merge = json_encode($arr_merge, JSON_PRETTY_PRINT);
			
	    	return $json_merge;   	 	
    	}
    }
    
    // get VM info:
    function getVm() {
    	$ch = curl_init();
   	curl_setopt($ch, CURLOPT_VERBOSE, true);
   	$verbose = fopen("dump", "w+");
   	curl_setopt($ch, CURLOPT_STDERR, $verbose);
   	
   	// retrieve VM info
   	curl_setopt($ch, CURLOPT_URL, $this->apiurl . "/vms?search=" . $this->vmname);
   	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
   	
   	curl_setopt($ch, CURLOPT_USERPWD, "$this->username" . ":" . "$this->password");
   	
   	$headers = array();
   	$headers[] = 'Accept: application/json';
   	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
   	
   	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
   	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
   	
   	$result = curl_exec($ch);
   	if(curl_errno($ch)) {
			$error = curl_error($ch);			
			curl_close($ch);   	
   		return 'Error - ' . $error;
   	} else {
   		curl_close($ch);
   		return $result;
   	}   
	}
	
	// set VM ID:
	function getId() {
		// get VM info
   	$search = json_decode($this->getVm(),true);
   
   	// set object properties value	
   	if(!empty($search)) {
   		// get and set vm id 
   		foreach ($search['vms'] as $key => $value) {
				$this->id = $value['id'];
				$this->memory = $value['memory'];
				$this->status = $value['status']['state'];
				$this->cpu = ( $value['cpu']['topology']['sockets'] * $value['cpu']['topology']['cores'] );  	
   		}
   	}   	
	}
	
	// check if over-commit
	function isOvercommit() {
		// dump all VM data
		$vms = json_decode($this->listVm(), true);
				
		// get sum of all committed cpus
		$sumCpus = 0;
		foreach ($vms as $value) {
			if($value['status'] == "up") {
				$sumCpus += ( $value['cpu']['topology']['sockets'] * $value['cpu']['topology']['cores'] );
			}		
		}
		
		// get requested VM cpu
		foreach ($vms as $value) {
			if($value['name'] == "$this->vmname") {
				$reqCpu = ( $value['cpu']['topology']['sockets'] * $value['cpu']['topology']['cores'] );
			}
		}
		
		// calculate new over-commit ratio
		$newRatio = ($sumCpus + $reqCpu) * 100 / (28 *1.3);   // *Over-commit ratio 1:3
				
		// over-commit check
		if($newRatio < 100) {
			// new ratio is not exceed over-commit limit			
			return false;
		} else {
			// new ratio will exceed over-commit limit
			return true;
		}		
	}
	
	// check VM status/state
	function isUp() {
		
		// check VM state
		if($this->status != "down") {
			return true;
		} else {
			return false;
		}		
	}
	
	// start a VM:
	function startVm() {
		// set VM id
		$this->getId();
		
		// curl option
		$ch = curl_init();
   	curl_setopt($ch, CURLOPT_VERBOSE, true);
   	$verbose = fopen("dump", "w+");
   	curl_setopt($ch, CURLOPT_STDERR, $verbose);
   	
   	// retrieve VM info
   	curl_setopt($ch, CURLOPT_URL, "$this->apiurl" . "/vms/" . "$this->id" . "/start");
   	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
   	
   	curl_setopt($ch, CURLOPT_USERPWD, "$this->username" . ":" . "$this->password");
   	
   	$headers = array();
   	$headers[] = 'Accept: application/json';
   	$headers[] = 'Content-Type: application/json';
   	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
   	curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, '{ }');   	
   	
   	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
   	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
   	
   	// execute curl
   	$result = curl_exec($ch);

		// parse response code and output   	
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch)	;

		// set parameters
		if($httpcode == 200) {
			$this->output = "OK";
		} else {
			$arr = json_decode($result, true);
			$this->output = $arr['fault']['detail'];
		}			   		   	
		$this->response = $httpcode;

	}
	
	// stop a VM:
	function stopVm() {
		// set VM id
		$this->getId();
		
		// curl option
		$ch = curl_init();
   	curl_setopt($ch, CURLOPT_VERBOSE, true);
   	$verbose = fopen("dump", "w+");
   	curl_setopt($ch, CURLOPT_STDERR, $verbose);
   	
   	// retrieve VM info
   	curl_setopt($ch, CURLOPT_URL, "$this->apiurl" . "/vms/" . "$this->id" . "/stop");
   	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
   	
   	curl_setopt($ch, CURLOPT_USERPWD, "$this->username" . ":" . "$this->password");
   	
   	$headers = array();
   	$headers[] = 'Accept: application/json';
   	$headers[] = 'Content-Type: application/json';
   	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
   	curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, '{ }');   	
   	
   	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
   	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
   	
   	// execute curl
   	$result = curl_exec($ch);

		// parse response code and output   	
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		// set parameters
		if($httpcode == 200) {
			$this->output = "OK";
		} else {
			$arr = json_decode($result, true);
			$this->output = $arr['fault']['detail'];	
		}			   		   	
		$this->response = $httpcode;

	}
	
	// shutdown a VM
	function shutdownVm() {
		// set VM id
		$this->getId();
		
		// curl option
		$ch = curl_init();
   	curl_setopt($ch, CURLOPT_VERBOSE, true);
   	$verbose = fopen("dump", "w+");
   	curl_setopt($ch, CURLOPT_STDERR, $verbose);
   	
   	// retrieve VM info
   	curl_setopt($ch, CURLOPT_URL, "$this->apiurl" . "/vms/" . "$this->id" . "/shutdown");
   	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
   	
   	curl_setopt($ch, CURLOPT_USERPWD, "$this->username" . ":" . "$this->password");
   	
   	$headers = array();
   	$headers[] = 'Accept: application/json';
   	$headers[] = 'Content-Type: application/json';
   	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
   	curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, '{ }');   	
   	
   	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
   	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
   	
   	// execute curl
   	$result = curl_exec($ch);

		// parse response code and output   	
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		// set parameters
		if($httpcode == 200) {
			$this->output = "OK";
		} else {
			$arr = json_decode($result, true);
			$this->output = $arr['fault']['detail'];	
		}			   		   	
		$this->response = $httpcode;

	}
	
	// set cpu
	function setCpu($cpu) {
		$this->cpu = $cpu;
	}
	
	// set memory
	function setMemory($memory) {
		$this->memory = $memory;
	}
	
	// update VM configuration (CPU and/or Memory)
	function updateVm() {
		
		// curl option
		$ch = curl_init();
   	curl_setopt($ch, CURLOPT_VERBOSE, true);
   	$verbose = fopen("dump", "w+");
   	curl_setopt($ch, CURLOPT_STDERR, $verbose);
   	
   	// retrieve VM info
   	curl_setopt($ch, CURLOPT_URL, "$this->apiurl" . "/vms/" . "$this->id");
   	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
   	
   	curl_setopt($ch, CURLOPT_USERPWD, "$this->username" . ":" . "$this->password");
   	
   	$headers = array();
   	$headers[] = 'Accept: application/xml';
   	$headers[] = 'Content-Type: application/xml';
   	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
   	curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, '<vm><cpu><topology sockets="' . $this->cpu . '" cores="1"/></cpu><memory>' . $this->memory . '</memory></vm>');   	
   	
   	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
   	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
   	
   	// execute curl
   	$result = curl_exec($ch);

		// parse response code and output   	
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);
		
		// set parameters
		if($httpcode == 200) {
			$this->output = $result;
		} else {
			$arr = json_decode($result, true);
			$this->output = $arr['fault']['detail'];	
		}			   		   	
		$this->response = $httpcode;

	}
	
	// logging function
	function log2file($origin, $host, $action) {
		$log = "[" . date("j/M/Y:H:i:s T") . "] Origin: " . $origin . " - Host: " . $host . " - Action: " . $action . " - Target VM: " . $this->vmname . " - Status: " . $this->response .PHP_EOL;
		file_put_contents('../log/api_access.log', $log, FILE_APPEND);
	}
}
?>
