<?
    // Klassendefinition
    class IPS2IPCam extends IPSModule 
    {
	public function Destroy() 
	{
		//Never delete this line!
		parent::Destroy();
		
	}
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
            	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyString("IPAddress", "127.0.0.1");
	    	$this->RegisterPropertyInteger("Port", 80);
		$this->RegisterPropertyString("User", "User");
	    	$this->RegisterPropertyString("Password", "Passwort");
		$this->RegisterPropertyInteger("ServerSocketPort", 0);
		
 	    	$this->RequireParent("{8062CF2B-600E-41D6-AD4B-1BA66C32D6ED}"); // Server Socket 
		
		 // Statusvariablen anlegen
		$this->RegisterVariableString("Stream", "Video-Stream", "~HTMLBox", 10);
		
        }
       	
	public function GetConfigurationForm() { 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Instanz ist fehlerhaft"); 
		
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "IPAddress", "caption" => "IP");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Port", "caption" => "Port:");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Zugriffsdaten IP Cam:");
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "User", "caption" => "User");
		$arrayElements[] = array("type" => "PasswordTextBox", "name" => "Password", "caption" => "Password");
		
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "ServerSocketPort", "caption" => "Port:");
 		
 		
		
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements)); 		 
 	} 
	
	// Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
                // Diese Zeile nicht löschen
                parent::ApplyChanges();
            
                $ParentID = $this->GetParentID();
		
		$this->RegisterMessage($ParentID, 10505); // Status hat sich geändert

		If ($ParentID > 0) {
			If (IPS_GetProperty($ParentID, 'Port') <> $this->ReadPropertyInteger("ServerSocketPort")) {
				IPS_SetProperty($ParentID, 'Port', $this->ReadPropertyInteger("ServerSocketPort"));
			}
			If (IPS_GetProperty($ParentID, 'Open') <> $this->ReadPropertyBoolean("Open")) {
				IPS_SetProperty($ParentID, 'Open', $this->ReadPropertyBoolean("Open"));
			}
			
			if(IPS_HasChanges($ParentID))
			{
				$Result = @IPS_ApplyChanges($ParentID);
				If ($Result) {
					$this->SendDebug("ApplyChanges", "Einrichtung des Server Socket erfolgreich", 0);
				}
				else {
					$this->SendDebug("ApplyChanges", "Einrichtung des Server Socket nicht erfolgreich!", 0);
				}
			}
		}
		
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
						
			If (($Result == true) AND ($this->ReadPropertyBoolean("Open") == true)) {
				$this->SetStreamData();
				$this->SetStatus(102);
			}
			else {
				
				$this->SetStatus(104);
			}
		}
		else {
			$this->SetStatus(104);
			
		}
	}
	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	
 	}
	// Beginn der Funktionen
	public function SetStreamData()
	{
		//<div align="center"><img src="http://jpaeper.dnsalias.com:8081/videostream.cgi?user=admin&pwd=Dennis1999" style="width: 100%; height: 100%;" >
		$String = '<div align="center"><img src="http://jpaeper.dnsalias.com:8081/videostream.cgi?user=admin&pwd=Dennis1999" style="width: 100%; height: 100%;" >';
		SetValueString($this->GetIDForIdent("Stream"), $String);
	}
	
	    
	private function GetParentID()
	{
		$ParentID = (IPS_GetInstance($this->InstanceID)['ConnectionID']);  
	return $ParentID;
	}
  	
  	private function GetParentStatus()
	{
		$Status = (IPS_GetInstance($this->GetParentID())['InstanceStatus']);  
	return $Status;
	}    
	    
	    
	private function HasActiveParent()
    	{
		$Instance = @IPS_GetInstance($this->InstanceID);
		if ($Instance['ConnectionID'] > 0)
		{
			$Parent = IPS_GetInstance($Instance['ConnectionID']);
			if ($Parent['InstanceStatus'] == 102)
			return true;
		}
        return false;
    	}  
}
?>
