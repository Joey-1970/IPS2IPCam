<?
    // Klassendefinition
    class IPS2IPCam extends IPSModule 
    {
	public function Destroy() 
	{
		//Never delete this line!
		parent::Destroy();
		$this->SetTimerInterval("Timer_1", 0);
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
		$this->RegisterPropertyBoolean("Movable", false);
		$this->RegisterPropertyInteger("Timer_1", 60); // Zustandsdaten einlesen
		$this->RegisterTimer("Timer_1", 0, 'IPS2IPCam_GetState($_IPS["TARGET"]);');
		
 	    	$this->RequireParent("{8062CF2B-600E-41D6-AD4B-1BA66C32D6ED}"); // Server Socket 
		
		 // Statusvariablen anlegen
		$this->RegisterVariableString("StreamWebfront", "Video-Stream Webfront", "~HTMLBox", 10);
		
		$this->RegisterVariableString("StreamMobile", "Video-Stream mobil", "~HTMLBox", 20);
		
		$this->RegisterVariableBoolean("MotionDetection", "Bewegungsmelder aktivieren", "~Switch", 30);
		
		$this->RegisterVariableInteger("MotionSensibility", "Bewegungsmelder Sensibilität", "", 40); // 0 - 10
		$this->EnableAction("MotionSensibility");
		
		$this->RegisterVariableBoolean("Notification", "Benachrichtigung", "~Switch", 50);
		$this->EnableAction("Notification");
		
		$this->RegisterVariableBoolean("MotionDetect", "Bewegungsmelder Auslösung", "~Switch", 60);
        }
       	
	public function GetConfigurationForm() { 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Instanz ist fehlerhaft"); 
		
		$arrayElements = array(); 
		$arrayElements[] = array("type" => "CheckBox", "name" => "Open", "caption" => "Aktiv"); 
		$arrayElements[] = array("type" => "Label", "label" => "Zugriffsdaten IP Cam:");
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "IPAddress", "caption" => "IP");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Port", "caption" => "Port:");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "User", "caption" => "User");
		$arrayElements[] = array("type" => "PasswordTextBox", "name" => "Password", "caption" => "Password");
		$arrayElements[] = array("type" => "Label", "label" => "Port auf dem Bewegungserkennungen gesendet werden:");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "ServerSocketPort", "caption" => "Port:");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "CheckBox", "name" => "Movable", "caption" => "Steuerbar"); 
		$arrayElements[] = array("type" => "Label", "label" => "Abfrage der Zustandsdaten in Sekunden (0 -> aus, 1 sek -> Minimum)");
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
						
			If ($this->ReadPropertyBoolean("Open") == true) {
				$this->SetStreamData();
				$this->SetStatus(102);
				$this->GetState();
				$this->SetTimerInterval("Timer_1", ($this->ReadPropertyInteger("Timer_1") * 1000));
			}
			else {
				$this->SetTimerInterval("Timer_1", 0);
				$this->SetStatus(104);
			}
		}
		else {
			$this->SetStatus(104);
			$this->SetTimerInterval("Timer_1", 0);
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
		If ($this->ReadPropertyBoolean("Open") == true) {
			//Webfront: <div align="center"><img src="http://jpaeper.dnsalias.com:8081/videostream.cgi?user=admin&pwd=Dennis1999" style="width: 100%; height: 100%;" >
			//iPhone: <div align="center"><img src="http://jpaeper.dnsalias.com:8080/videostream.cgi?user=admin&pwd=Dennis1999" style="width: 960px; height: 720px;" >
			$IPAddress = $this->ReadPropertyString("IPAddress");
			$Port = $this->ReadPropertyInteger("Port");
			$User = $this->ReadPropertyString("User");
			$Password = $this->ReadPropertyString("Password");

			$String = '<div align="center"><img src="http://'.$IPAddress.':'.$Port.'/videostream.cgi?user='.$User.'&pwd='.$Password.'" style="width: 960px; height: 720px;" >';
			//$String = '<div align="center"><img src="http://jpaeper.dnsalias.com:8081/videostream.cgi?user=admin&pwd=Dennis1999" style="width: 100%; height: 100%;" >';
			SetValueString($this->GetIDForIdent("StreamMobile"), $String);
		}
	}
	
	public function GetState()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$IPAddress = $this->ReadPropertyString("IPAddress");
			$Port = $this->ReadPropertyInteger("Port");
			$User = $this->ReadPropertyString("User");
			$Password = $this->ReadPropertyString("Password");

			$Lines = array();
			$lines = file('http://'.$IPAddress.':'.$Port.'/get_params.cgi?user='.$User.'&pwd='.$Password);

			for ($i = 0; $i <= (count($lines) - 1); $i++) {
				$teile = explode("=", $lines[$i]);

				If ($teile[0] == "var alarm_motion_sensitivity") {
					If (GetValueInteger($this->GetIDForIdent("MotionSensibility")) <> intval($teile[1])) {
						SetValueInteger($this->GetIDForIdent("MotionSensibility"), intval($teile[1]));
					}
				}
				If ($teile[0] == "var alarm_motion_armed") {
					If (GetValueBoolean($this->GetIDForIdent("MotionDetection")) <> intval($teile[1])) {
						SetValueBoolean($this->GetIDForIdent("MotionDetection"), intval($teile[1]));
					}
				}
				If ($teile[0] == "var alarm_mail") {
					If (GetValueBoolean($this->GetIDForIdent("Notification")) <> intval($teile[1])) {
						SetValueBoolean($this->GetIDForIdent("Notification"), intval($teile[1]));
					}
				}
			}
		}
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
	    
/*


//*************************************************************************************************************
// Diese Funktion prüft ob der Bewegungsmelder ausgelöst wurde
function IP_Cam_BewegungsmelderAusloesung($ip, $user, $passwort, $port)
{
// Befehlsaufruf erstellen http://192.168.178.11:80/get_status.cgi?.user=admin&pwd=Dennis1999
$Befehl = "http://".$ip.":".$port."/get_status.cgi?"."user=".$user."&pwd=".$passwort;
// Kamera Auslesen
$handle = fopen($Befehl,"r"); // String öffnen
$Status = "";
$Status = stream_get_contents($handle); // String einlesen
//echo $Status;
$Alarmstatus = array();
$Alarmstatus = explode(";",$Status);

if (intval(substr($Alarmstatus[6], -1, 1)) == 1)
  {
  $BewegungsmelderAusloesung = true;
  }
  else
  {
  $BewegungsmelderAusloesung = false;
  }

return $BewegungsmelderAusloesung;
}

//*************************************************************************************************************
// Diese Funktion setzt verschiedene Parameter der IP-Cam
function IP_Cam_Parameter($ip, $user, $passwort, $port, $BewegungsmelderSensibilitaet, $BewegungsmelderStatus, $MailversandStatus)
{
$BewegungsmelderSensibilitaet = 10 - $BewegungsmelderSensibilitaet;
$BewegungsmelderStatusInt = (int)$BewegungsmelderStatus;
$MailversandStatusInt = (int)$MailversandStatus;

file_get_contents("http://$ip:$port/set_alarm.cgi?motion_armed=$BewegungsmelderStatusInt&mail=$MailversandStatusInt&motion_sensitivity=$BewegungsmelderSensibilitaet&motion_compensation=1&user=$user&pwd=$passwort");

return;
}

//*************************************************************************************************************
// Diese Funktion steuert die Bewegung der IP-Cam
// 0=hoch, 1=runter, 2=links, 3=rechts, 4=zentral
function IP_Cam_Steuerung($ip, $user, $passwort, $port, $Bewegung)
{
$Bewegungsarray = array(0, 1=>2, 2=>4, 3=>6, 4=>31);
file_get_contents("http://$ip:$port/decoder_control.cgi?command=$Bewegungsarray[$Bewegung]&onestep=1&user=$user&pwd=$passwort");
return;
}   
*/

}
?>
