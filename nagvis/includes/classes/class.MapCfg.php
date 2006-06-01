<?php
##########################################################################
##     	                           NagVis                               ##
##               *** Klasse zum verarbeiten der Config ***              ##
##                               Lizenz GPL                             ##
##########################################################################

/**
* This Class handles the NagVis configuration file
*/
class MapCfg {
	var $MAINCFG;
	
	var $name;
	var $image;
	var $mapConfig;
	
	/**
	* Constructor
	*
	* @param config $MAINCFG
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function MapCfg(&$MAINCFG,$name='') {
		$this->MAINCFG = &$MAINCFG;
		
		//if no map was given with parameter, search for a map
		if($name == '') {
			// only try to get a map, if we are not in wui
			if($this->MAINCFG->getRuntimeValue('wui') == 1) {
				$this->name = '';
			} else {
				$this->name = $this->getMap();
			}
		} else {
			//check the $name string for security reasons (its the ONLY value we
			//get directly from external...)
			//Allow ONLY Characters and Numbers inside the Name of a Map
			$this->name = preg_replace("/[^a-zA-Z0-9]/","",$name);
		}
	}
	
	/**
	* Reads which map we should display, primary use
	* the map defined in the url, if there is no map
	* in url, use first entry of "maps" defined in 
	* the NagVis main config
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function getMap() {
		$arr = explode(',',$this->MAINCFG->getValue('global', 'maps'));
	   	return $arr[0];
	}
	
	/**
	* Reads which map image we should use
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function getImage() {
		return $this->image = $this->getValue('global', 0, 'map_image');
	}
	
	/**
	* Deletes the map image
	*
	* @param string $printErr
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function deleteImage($printErr) {
		if($this->checkMapImageWriteable($printErr)) {
			if(unlink($this->MAINCFG->getValue('paths', 'map').$this->image)) {
				return TRUE;
			} else {
				//FIXME: Errorhandling?
				return FALSE;
			}
		}
	}
	
	/**
	* Creates a new configfile
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function createMapConfig() {
		// does file exists?
		if(!$this->checkMapConfigReadable(0)) {
			if($this->MAINCFG->checkMapCfgFolderWriteable(1)) {
				// create empty file
				$fp = fopen($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.".cfg", "w");
				fclose($fp); 
				// set permissions
	  			chmod($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.".cfg",0666);
	  			
  				return TRUE;
  			} else {
  				return FALSE;
  			}
		} else {
			// file exists & is readable
			return FALSE;
		}
	}
	
	/**
	* Deletes the map configfile
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function deleteMapConfig() {
		// is file writeable?
		if($this->checkMapConfigWriteable(0)) {
			if(unlink($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg')) {
				return TRUE;
			} else {
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	* Reads the map config file (copied from readFile->readNagVisCfg())
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function readMapConfig() {
		if($this->name != '') {
			if($this->checkMapConfigReadable(1)) {
				$this->mapConfig = Array();
				
				// read file in array
				$file = file($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.".cfg");
				
				$type = array("global","host","service","hostgroup","servicegroup","map","textbox");
				$createArray = array("allowed_user","allowed_for_config");
				$l = 0;
				$a = 0;
						
				while (isset($file[$l]) && $file[$l] != "") {
					if(!ereg("^#",$file[$l]) && !ereg("^;",$file[$l])) {
						$defineCln = explode("{", $file[$l]);
						$define = explode(" ",$defineCln[0]);
						if (isset($define[1]) && in_array(trim($define[1]),$type)) {
							$l++;
							$nrOfType = count($this->mapConfig[$define[1]]);
							$this->mapConfig[$define[1]][$nrOfType]['type'] = $define[1];
							while (trim($file[$l]) != "}") {
								$entry = explode("=",$file[$l], 2);
								
								if(isset($entry[1])) {
									if(in_array(trim($entry[0]),$createArray)) {
										$this->mapConfig[$define[1]][$nrOfType][trim($entry[0])] = explode(",",str_replace(' ','',trim($entry[1])));
									} else {
										$this->mapConfig[$define[1]][$nrOfType][trim($entry[0])] = trim($entry[1]);
									}
								}
								$l++;	
							}
						}
					}
					$l++;
				}
				
				if($this->checkMapConfigIsValid(1)) {
					$this->getImage();
					return TRUE;
				} else {
					return FALSE;
				}
			} else {
				return FALSE;	
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	* Writes the config file completly from array $mapConfig
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function writeConfig() {
		//FIXME
	}
	
	/**
	* Writes the element from array to the config file
	*
	* @param string  $type
	* @param integer $id
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function writeElement($type,$id) {
		if($this->checkMapConfigReadable(1) && $this->checkMapConfigWriteable(1)) {
			// read file in array
			$file = file($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.".cfg");
			
			// number of lines in the file
			$l = 0;
			// number of elements of the given type
			$a = 0;
			// done?!
			$done = FALSE;
			while(isset($file[$l]) && $file[$l] != "" && $done == FALSE) {
				// ignore comments
				if(!ereg("^#",$file[$l]) && !ereg("^;",$file[$l])) {
					$defineCln = explode("{", $file[$l]);
					$define = explode(" ",$defineCln[0]);
					// select only elements of the given type
					if(isset($define[1]) && trim($define[1]) == $type) {
						// check if element exists
						if($a == $id) {
							// check if element is an array...
							if(is_array($this->mapConfig[$type][$a])) {
								// ...array: update!
								
								// choose first parameter line
								$l++;
								
								// loop parameters from array
								foreach($this->mapConfig[$type][$id] AS $key => $val) {
									// if key is not type
									if($key != 'type') {
										$cfgLines = 0;
										$cfgLine = '';
										$cfgLineNr = 0;
										// Parameter aus Datei durchlaufen
										while(trim($file[($l+$cfgLines)]) != '}') {
											$entry = explode("=",$file[$l+$cfgLines], 2);
											if($key == trim($entry[0])) {
												$cfgLineNr = $l+$cfgLines;
												if(is_array($val)) {
													$val = implode(",",$val);
												}
												$cfgLine = $key."=".$val."\n";
											}
											$cfgLines++;	
										}
										
										// Wenn der Parameter gefunden wurde...
										if($cfgLineNr != '') {
											// ersetzen
											$file[$cfgLineNr] = $cfgLine;
										} else {
											if(is_array($val)) {
												$val = implode(",",$val);
											}
											// neue Zeile am Ende der Defnition hinzufügen
											$neu = $key."=".$val."\n";
											for($i = $l; $i < count($file);$i++) {
												$tmp = $file[$i];
												$file[$i] = $neu;
												$neu = $tmp;
											}
											$file[count($file)] = $neu;
										}
										$l++;
									}
								}
							} else {
								// ...no array: delete!
								$cfgLines = 0;
								while(trim($file[($l+$cfgLines)]) != '}') {
									$cfgLines++;
								}
								$cfgLines++;
								
								for($i = $l; $i <= $l+$cfgLines;$i++) {
									unset($file[$i]);	
								}
							}
							
							$done = TRUE;
						}
						$a++;
					}
				}
				$l++;	
			}
			
			// reached end of file - couldn't find that element, create a new one...
			if($done == FALSE) {
				if($file[count($file)-1] != "\n") {
					$file[] = "\n";
				}
				$file[] = "define ".$type." {\n";
				foreach($this->mapConfig[$type][$id] AS $key => $val) {
					$file[] = $key."=".$val."\n";
				}
				$file[] = "}\n";
				$file[] = "\n";
			}
			
			// open file for writing and replace it
		 	$fp = fopen($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.".cfg","w");
		 	fwrite($fp,implode("",$file));
		 	fclose($fp);
		 	
			return TRUE;
		} else {
			return FALSE;
		} 
	}
	
	/**
	* Checks for readable map image file
	*
	* @param string $printErr
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function checkMapImageReadable($printErr) {
		if($this->image != '') {
			if(file_exists($this->MAINCFG->getValue('paths', 'map').$this->image) && is_readable($this->MAINCFG->getValue('paths', 'map').$this->image)) {
				return TRUE;
			} else {
				if($printErr == 1) {
					$FRONTEND = new frontend($this->MAINCFG,$this->MAPCFG);
					$FRONTEND->openSite();
					$FRONTEND->messageBox("20", "IMGPATH~".$this->MAINCFG->getValue('paths', 'map').$this->image);
					$FRONTEND->closeSite();
					$FRONTEND->printSite();
				}
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	* Checks for writeable map image file
	*
	* @param string $printErr
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function checkMapImageWriteable($printErr) {
		if($this->image != '') {
			//FIXME: is_writable doesn't check write permissions
			if(file_exists($this->MAINCFG->getValue('paths', 'map').$this->image) /*&& is_writable($this->MAINCFG->getValue('paths', 'map').$this->image)*/) {
				return TRUE;
			} else {
				if($printErr == 1) {
					$FRONTEND = new frontend($this->MAINCFG,$this->MAPCFG);
					$FRONTEND->openSite();
					$FRONTEND->messageBox("21", "IMGPATH~".$this->MAINCFG->getValue('paths', 'map').$this->image);
					$FRONTEND->closeSite();
					$FRONTEND->printSite();
				}
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	* Checks for readable config file
	*
	* @param string $printErr
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function checkMapConfigReadable($printErr) {
		if($this->name != '') {
			if(file_exists($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.".cfg") && is_readable($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.".cfg")) {
				return TRUE;
			} else {
				if($printErr == 1) {
					$FRONTEND = new frontend($this->MAINCFG,$this->MAPCFG);
					$FRONTEND->openSite();
					$FRONTEND->messageBox("16", "MAP~".$this->MAINCFG->getValue('paths', 'mapcfg').$this->name.".cfg");
					$FRONTEND->closeSite();
					$FRONTEND->printSite();
				}
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	* Checks for writeable config file
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function checkMapConfigWriteable($printErr) {
		if(file_exists($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.".cfg") && is_writeable($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.".cfg")) {
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new frontend($this->MAINCFG,$this->MAPCFG);
				$FRONTEND->openSite();
				$FRONTEND->messageBox("17", "MAP~".$this->MAINCFG->getValue('paths', 'mapcfg').$this->name.".cfg");
				$FRONTEND->closeSite();
				$FRONTEND->printSite();
			}
			return FALSE;
		}
	}
	
	function checkMapConfigIsValid($printErr) {
		$validConfig = Array(
			'global' => Array('type','allowed_for_config','allowed_user','iconset','map_image'),
			'host' => Array('type','host_name','x','y','recognize_services','iconset','line_type','url'),
			'hostgroup' => Array('type','hostgroup_name','x','y','recognize_services','iconset','line_type','url'),
			'service' => Array('type','host_name','service_description','x','y','line_type','url','iconset'),
			'servicegroup' => Array('type','servicegroup_name','x','y','iconset','line_type','url'),
			'map' => Array('type','map_name','x','y','iconset','url'),
			'textbox' => Array('type','text','x','y','w'));
		foreach($this->mapConfig AS $type => $elements) {
			if(array_key_exists($type,$validConfig)) {
				// loop elemtents of type
				foreach($elements AS $id => $element) {
					// loop atributes of element
					foreach($element AS $key => $val) {
						// check for valid atributes - TODO: check valid values
						if(!in_array($key,$validConfig[$type])) {
							// unknown atribute
							if($printErr == 1) {
								$FRONTEND = new frontend($this->MAINCFG,$this->MAPCFG);
								$FRONTEND->openSite();
								$FRONTEND->messageBox("23", "ATTRIBUTE~".$key);
								//TYPE=$id should also be returned but our current MessageBox supports only one var
								$FRONTEND->closeSite();
								$FRONTEND->printSite();
							}
							return FALSE;
						}
					}
				}	
			} else {
				// unknown type
				if($printErr == 1) {
					$FRONTEND = new frontend($this->MAINCFG,$this->MAPCFG);
					$FRONTEND->openSite();
					$FRONTEND->messageBox("22", "TYPE~".$type);
					$FRONTEND->closeSite();
					$FRONTEND->printSite();
				}
				return FALSE;
			}
		}
		return TRUE;
	}
	
	/**
	* Gets all definitions of type $type
	*
	* @param string $type
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function getDefinitions($type) {
		if(count($this->mapConfig[$type]) > 0) {
			return $this->mapConfig[$type];
		} else {
			return Array();
		}
	}
	
	/**
	* Deletes an element of the specified type to the config array
	*
	* @param string $type
	* @param array  $id
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function deleteElement($type,$id) {
		$this->mapConfig[$type][$id] = '';
	}
	
	/**
	* Adds an element of the specified type to the config array
	*
	* @param string $type
	* @param array  $properties
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function addElement($type,$properties) {
		//$elementId = (count($this->getDefinitions($type))+1);
		$this->mapConfig[$type][] = $properties;
		
		return count($this->mapConfig[$type])-1;
	}
	
	/**
	* Sets a config value in the array
	*
	* @param string $type
	* @param string $id
	* @param string $key
	* @param string $value
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function setValue($type, $id, $key, $value) {
       $this->mapConfig[$type][$id][$key] = $value;
       return TRUE;
	}
	
	/**
	* Gets a config value from the array
	*
	* @param string $type
	* @param string $id
	* @param string $key
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function getValue($type, $id, $key) {
		return $this->mapConfig[$type][$id][$key];
	}
	
	/**
	* Getter for $name
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function getName() {
		return $this->name;	
	}
	
}
?>
