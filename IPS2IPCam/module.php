<?
    // Klassendefinition
    class IPS2IPCam extends IPSModule 
    {
	// https://wiki.instar.de/Erweitert/CGI_Befehle/VGA_Serie_CGI_Befehle/
	
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->RegisterMessage(0, IPS_KERNELSTARTED);
		
            	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("Type", 0);
		$this->RegisterPropertyString("IPAddressInt", "127.0.0.1");
	    	$this->RegisterPropertyInteger("PortInt", 80);
		$this->RegisterPropertyString("IPAddressEx", "127.0.0.1");
	    	$this->RegisterPropertyInteger("PortEx", 80);
		$this->RegisterPropertyString("User", "User");
	    	$this->RegisterPropertyString("Password", "Passwort");
		$this->RegisterPropertyInteger("ServerSocketPort", 0);
		$this->RegisterPropertyBoolean("Movable", false);
		$this->RegisterPropertyInteger("CategoryForSnapshot", 0);
		$this->RegisterPropertyInteger("Timer_1", 60); // Zustandsdaten einlesen
		$this->RegisterTimer("Timer_1", 0, 'IPS2IPCam_GetState($_IPS["TARGET"]);');
		
 	    	$this->RequireParent("{8062CF2B-600E-41D6-AD4B-1BA66C32D6ED}"); // Server Socket 
		
		// Profil anlegen
		$this->RegisterProfileInteger("IPS2IPCam.Sensibility", "Motion", "", "", 0, 10, 1);
				
		$this->RegisterProfileInteger("IPS2IPCam.Move", "Move", "", "", 0, 4, 0);
		IPS_SetVariableProfileAssociation("IPS2IPCam.Move", 0, "hoch", "HollowArrowUp", -1);
		IPS_SetVariableProfileAssociation("IPS2IPCam.Move", 1, "runter", "HollowArrowDown", -1);
		IPS_SetVariableProfileAssociation("IPS2IPCam.Move", 2, "links", "HollowArrowLeft", -1);
		IPS_SetVariableProfileAssociation("IPS2IPCam.Move", 3, "rechts", "HollowArrowRight", -1);
		IPS_SetVariableProfileAssociation("IPS2IPCam.Move", 4, "zentrieren", "Move", -1);
		
		// Statusvariablen anlegen
		//$this->RegisterVariableString("StreamWebfront", "Video-Stream Webfront", "~HTMLBox", 10);
		
		//$this->RegisterVariableString("StreamMobile", "Video-Stream mobil", "~HTMLBox", 20);
		
		$this->RegisterVariableBoolean("MotionDetection", "Bewegungsmelder aktivieren", "~Switch", 30);
		$this->EnableAction("MotionDetection");
		
		$this->RegisterVariableInteger("MotionSensibility", "Bewegungsmelder Sensibilität", "IPS2IPCam.Sensibility", 40); // 0 - 10
		$this->EnableAction("MotionSensibility");
		
		$this->RegisterVariableBoolean("Notification", "Benachrichtigung", "~Switch", 50);
		$this->EnableAction("Notification");
		IPS_SetIcon($this->GetIDForIdent("Notification"), "Mail");
		
		$this->RegisterVariableBoolean("MotionDetect", "Bewegungsmelder Auslösung", "~Motion", 60);
		IPS_SetIcon($this->GetIDForIdent("MotionDetect"), "Motion");
		
		$this->RegisterVariableInteger("LastMotionDetect", "Letzte Auslösung", "~UnixTimestamp", 70);
		
		
        }
       	
	public function GetConfigurationForm() { 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Instanz ist fehlerhaft"); 
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "HTTP-Kommunikationfehler!");
		
		$arrayElements = array(); 
		$arrayElements[] = array("type" => "CheckBox", "name" => "Open", "caption" => "Aktiv"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "VGA", "value" => 0);
		$arrayOptions[] = array("label" => "720p", "value" => 1);
		$arrayOptions[] = array("label" => "1080p", "value" => 2);
		$arrayElements[] = array("type" => "Select", "name" => "Type", "caption" => "Kamera Typ", "options" => $arrayOptions );

		$arrayElements[] = array("type" => "Label", "caption" => "Zugriffsdaten IP Cam intern:");
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "IPAddressInt", "caption" => "IP");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "PortInt", "caption" => "Port:");
		$arrayElements[] = array("type" => "Label", "caption" => "Zugriffsdaten IP Cam extern:");
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "IPAddressEx", "caption" => "IP");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "PortEx", "caption" => "Port:");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "User", "caption" => "User");
		$arrayElements[] = array("type" => "PasswordTextBox", "name" => "Password", "caption" => "Password");
		$arrayElements[] = array("type" => "Label", "caption" => "Port auf dem Bewegungserkennungen gesendet werden:");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "ServerSocketPort", "caption" => "Port:");
		$arrayElements[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "CheckBox", "name" => "Movable", "caption" => "Steuerbar"); 
		$arrayElements[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "SelectCategory", "name" => "CategoryForSnapshot", "caption" => "Zielkategorie für Snapshots");
		$arrayElements[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "caption" => "Abfrage der Zustandsdaten in Sekunden (0 -> aus, 1 sek -> Minimum)");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Timer_1", "caption" => "Sekunden");
 		
 	return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements)); 		 
 	} 
	
	// Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
                // Diese Zeile nicht löschen
                parent::ApplyChanges();
            
                $ParentID = $this->GetParentID();
		
		$this->RegisterMessage($ParentID, 10505); // Status hat sich geändert

		If (($ParentID > 0) AND (IPS_GetKernelRunlevel() == KR_READY)) {
			//$this->RegisterMediaObject("Snapshot_".$this->InstanceID, "Snapshot_".$this->InstanceID, 1, $this->InstanceID, 1000, true, "Snapshot.jpg");
			
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
		
		If ($this->ReadPropertyBoolean("Movable") == true) {
			$this->RegisterVariableInteger("Move", "Steuerung", "IPS2IPCam.Move", 80); 
			$this->EnableAction("Move");
		}
					
		If ($this->ReadPropertyBoolean("Open") == true) {
			//$this->SetStreamData();
			$this->SetStatus(102);
			$this->GetState();
			$this->SetTimerInterval("Timer_1", ($this->ReadPropertyInteger("Timer_1") * 1000));
		}
		else {
			$this->SetTimerInterval("Timer_1", 0);
			$this->SetStatus(104);
		}
		
	}
	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	$this->SendDebug("ReceiveData", $JSONString, 0);
		if (isset($data->Buffer)) {
			$this->GetAlarmState();
		}		
 	}
	
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    	{
		switch ($Message) {
			case IPS_KERNELSTARTED:
				// IPS_KERNELSTARTED
				$this->ApplyChanges();
				break;
			
		}
    	}            
	    
	public function RequestAction($Ident, $Value) 
	{
  		
		switch($Ident) {
	        case "MotionDetection":
			SetValueBoolean($this->GetIDForIdent($Ident), $Value);
			$this->SetState();
	        break;
		case "MotionSensibility":
			SetValueInteger($this->GetIDForIdent($Ident), $Value);
			$this->SetState();
	        break;
		case "Notification":
			SetValueBoolean($this->GetIDForIdent($Ident), $Value);
			$this->SetState();
	        break;
		case "Move":
			SetValueInteger($this->GetIDForIdent($Ident), $Value);
			$this->Move($Value);
	        break;
	        default:
	            throw new Exception("Invalid Ident");
	    	}
	}
	    
	// Beginn der Funktionen
	public function GetSnapshot(string $Filename)
	{
		$Result = false;
		If ($this->ReadPropertyBoolean("Open") == true) {
			$IPAddress = $this->ReadPropertyString("IPAddressEx");
			$Port = $this->ReadPropertyInteger("PortEx");
			$User = $this->ReadPropertyString("User");
			$Password = $this->ReadPropertyString("Password");
			$Type = $this->ReadPropertyInteger("Type");
			$CategoryForSnapshot = $this->ReadPropertyInteger("CategoryForSnapshot");
			If ($Type == 0) {
				
			}
			elseif ($Type == 1) {
				$Content = file_get_contents("http://".$IPAddress.":".$Port."/tmpfs/snap.jpg?usr=".$User."&pwd=".$Password);
				$MediaID = IPS_CreateMedia(1); 
				$Result = $MediaID;
				IPS_SetMediaFile($MediaID, $Filename.".jpg", false);
				IPS_SetMediaContent($MediaID, base64_encode($Content));		
				IPS_SetParent($MediaID, $CategoryForSnapshot);
				IPS_SetName($MediaID, $Filename);
				//IPS_SetMediaContent($this->GetIDForIdent("Snapshot_".$this->InstanceID), base64_encode($Content));  //Bild Base64 codieren und ablegen
				//IPS_SendMediaEvent($this->GetIDForIdent("Snapshot_".$this->InstanceID)); //aktualisieren
			}
		}
	return $Result;
	} 
	    
	    
	    
	public function SetStreamData()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$IPAddress = $this->ReadPropertyString("IPAddressEx");
			$Port = $this->ReadPropertyInteger("PortEx");
			$User = $this->ReadPropertyString("User");
			$Password = $this->ReadPropertyString("Password");
			$Type = $this->ReadPropertyInteger("Type");
			$Channel = 11;
			If ($Type == 0) {
				$HTMLMobil = '<div align="center"><img src="http://'.$IPAddress.':'.$Port.'/videostream.cgi?user='.$User.'&pwd='.$Password.'" style="width: 960px; height: 720px;" >';
				$HTMLWebfront = '<div align="center"><img src="http://'.$IPAddress.':'.$Port.'/videostream.cgi?user='.$User.'&pwd='.$Password.'" style="width: 100%; height: 100%;" >';
			}
			elseif ($Type == 1) {
				//http://IP-Address:Port/mjpegstream.cgi?-chn=11&-usr=admin&-pwd=instar
				$HTMLMobil = '<div align="center"><img src="http://'.$IPAddress.':'.$Port.'/mjpegstream.cgi?-chn='.$Channel.'&-usr='.$User.'&-pwd='.$Password.'" style="width: 960px; height: 720px;" >';
				$HTMLWebfront = '<div align="center"><img src="http://'.$IPAddress.':'.$Port.'/mjpegstream.cgi?-chn='.$Channel.'&-usr='.$User.'&-pwd='.$Password.'" style="width: 100%; height: 100%;" >';

			}
			SetValueString($this->GetIDForIdent("StreamMobile"), $HTMLMobil);
			SetValueString($this->GetIDForIdent("StreamWebfront"), $HTMLWebfront);
		}
		else {
			SetValueString($this->GetIDForIdent("StreamMobile"), "");
			SetValueString($this->GetIDForIdent("StreamWebfront"), "");
		}
	}
	
	public function GetState()
	{
		$Result = false;
		If ($this->ReadPropertyBoolean("Open") == true) {
			$IPAddress = $this->ReadPropertyString("IPAddressInt");
			$Port = $this->ReadPropertyInteger("PortInt");
			$User = $this->ReadPropertyString("User");
			$Password = $this->ReadPropertyString("Password");
			$Type = $this->ReadPropertyInteger("Type");
			
			If ($Type == 0) {
				$Lines = array();
				$Lines = @file('http://'.$IPAddress.':'.$Port.'/get_params.cgi?user='.$User.'&pwd='.$Password);

				If ($Lines === false) {
					$this->SendDebug("GetState", "Es ist ein Fehler aufgetreten!", 0);
					$this->SetStatus(202);
					$Result = false;
				}
				else {
					for ($i = 0; $i <= (count($Lines) - 1); $i++) {
						$Parts = explode("=", $Lines[$i]);

						If ($Parts[0] == "var alarm_motion_sensitivity") {
							If (GetValueInteger($this->GetIDForIdent("MotionSensibility")) <> intval($Parts[1])) {
								SetValueInteger($this->GetIDForIdent("MotionSensibility"), 10 - intval($Parts[1]));
							}
						}
						If ($Parts[0] == "var alarm_motion_armed") {
							If (GetValueBoolean($this->GetIDForIdent("MotionDetection")) <> intval($Parts[1])) {
								SetValueBoolean($this->GetIDForIdent("MotionDetection"), intval($Parts[1]));
							}
						}
						If ($Parts[0] == "var alarm_mail") {
							If (GetValueBoolean($this->GetIDForIdent("Notification")) <> intval($Parts[1])) {
								SetValueBoolean($this->GetIDForIdent("Notification"), intval($Parts[1]));
							}
						}
					}
					$this->SetStatus(102);
					$Result = true;
				}
			}
		}
	Return $Result;
	}
	    
	public function GetAlarmState()
	{
		$Result = false;
		If ($this->ReadPropertyBoolean("Open") == true) {
			$IPAddress = $this->ReadPropertyString("IPAddressInt");
			$Port = $this->ReadPropertyInteger("PortInt");
			$User = $this->ReadPropertyString("User");
			$Password = $this->ReadPropertyString("Password");
			$Type = $this->ReadPropertyInteger("Type");
			
			If ($Type == 0) {
				$Lines = array();
				$Lines = @file('http://'.$IPAddress.':'.$Port.'/get_status.cgi?user='.$User.'&pwd='.$Password);

				If ($Lines === false) {
					$this->SendDebug("GetAlarmState", "Es ist ein Fehler aufgetreten!", 0);
					$this->SetStatus(202);
					$Result = false;
				}
				else {
					for ($i = 0; $i <= (count($Lines) - 1); $i++) {
						$Parts = explode("=", $Lines[$i]);

						If ($Parts[0] == "var alarm_status") {
							If (GetValueBoolean($this->GetIDForIdent("MotionDetect")) <> intval($Parts[1])) {
								SetValueBoolean($this->GetIDForIdent("MotionDetect"), intval($Parts[1]));
								SetValueInteger($this->GetIDForIdent("LastMotionDetect"),  time());
								If (GetValueBoolean($this->GetIDForIdent("MotionDetect")) == true) {
									IPS_Sleep(1000);
									SetValueBoolean($this->GetIDForIdent("MotionDetect"), false);
								}
							}
						}
					}
					$this->SetStatus(102);
					$Result = true;
				}
			}
			elseif ($Type == 1) {
				SetValueInteger($this->GetIDForIdent("LastMotionDetect"),  time());
			}
		}
	Return $Result;
	}
	
	public function SetState()
	{
		$Result = false;
		If ($this->ReadPropertyBoolean("Open") == true) {
			$IPAddress = $this->ReadPropertyString("IPAddressInt");
			$Port = $this->ReadPropertyInteger("PortInt");
			$User = $this->ReadPropertyString("User");
			$Password = $this->ReadPropertyString("Password");
			$Type = $this->ReadPropertyInteger("Type");
			
			If ($Type == 0) {
				$MotionSensibility = 10 - GetValueInteger($this->GetIDForIdent("MotionSensibility"));
				$MotionDetection = intval(GetValueBoolean($this->GetIDForIdent("MotionDetection")));
				$Notification = intval(GetValueBoolean($this->GetIDForIdent("Notification")));

				$Result = file_get_contents('http://'.$IPAddress.':'.$Port.'/set_alarm.cgi?motion_armed='.$MotionDetection.'&mail='.$Notification.'&motion_sensitivity='.$MotionSensibility.'&motion_compensation=1&user='.$User.'&pwd='.$Password);
				If ($Result === false) {
					$this->SendDebug("SetState", "Es ist ein Fehler aufgetreten!", 0);
					$this->SetStatus(202);
					$Result = false;
				}
				else {
					$this->SetStatus(102);
					$Result = true;
					$this->GetState();
				}
			}
		}
	Return $Result;
	}
	    
	public function Move(int $Direction)
	{
		$Result = false;
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ReadPropertyBoolean("Movable") == true)) {
			$this->SendDebug("Move", "Richtung: ".$Direction, 0);
			$IPAddress = $this->ReadPropertyString("IPAddressInt");
			$Port = $this->ReadPropertyInteger("PortInt");
			$User = $this->ReadPropertyString("User");
			$Password = $this->ReadPropertyString("Password");
			
			// 0=hoch, 1=runter, 2=links, 3=rechts, 4=zentral
			$DirectionArray = array(0, 1=>2, 2=>4, 3=>6, 4=>31);
			
			$Result = file_get_contents('http://'.$IPAddress.':'.$Port.'/decoder_control.cgi?command='.$DirectionArray[$Direction].'&onestep=1&user='.$User.'&pwd='.$Password);
			If ($Result === false) {
				$this->SendDebug("Move", "Es ist ein Fehler aufgetreten!", 0);
				$this->SetStatus(202);
				$Result = false;
			}
			else {
				$this->SetStatus(102);
				$Result = true;
			}
		}
	Return $Result;
	}
	    
	private function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize)
	{
	        if (!IPS_VariableProfileExists($Name))
	        {
	            IPS_CreateVariableProfile($Name, 1);
	        }
	        else
	        {
	            $profile = IPS_GetVariableProfile($Name);
	            if ($profile['ProfileType'] != 1)
	                throw new Exception("Variable profile type does not match for profile " . $Name);
	        }
	        IPS_SetVariableProfileIcon($Name, $Icon);
	        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
	        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);        
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
	
	private function RegisterMediaObject($Name, $Ident, $Typ, $Parent, $Position, $Cached, $Filename)
	{
		$MediaID = @$this->GetIDForIdent($Ident);
		if($MediaID === false) {
		    	$MediaID = 0;
		}
		
		if ($MediaID == 0) {
			 // Image im MedienPool anlegen
			$MediaID = IPS_CreateMedia($Typ); 
			// Medienobjekt einsortieren unter Kategorie $catid
			IPS_SetParent($MediaID, $Parent);
			IPS_SetIdent($MediaID, $Ident);
			IPS_SetName($MediaID, $Name);
			IPS_SetPosition($MediaID, $Position);
                    	IPS_SetMediaCached($MediaID, $Cached);
			$ImageFile = IPS_GetKernelDir()."media".DIRECTORY_SEPARATOR.$Filename;  // Image-Datei
			IPS_SetMediaFile($MediaID, $ImageFile, false);    // Image im MedienPool mit Image-Datei verbinden
		}  
	}     
	    
	protected function HasActiveParent()
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
