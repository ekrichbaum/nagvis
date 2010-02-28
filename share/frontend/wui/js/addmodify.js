/*****************************************************************************
 *
 * addmodify.js - Functions which are needed by addmodify page
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: lars@vertical-visions.de)
 *
 * License:
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 *****************************************************************************/
 
/**
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */

var bFormIsValid = true;

function printObjects(aObjects,oOpt) {
	var type = oOpt.type;
	var field = oOpt.field;
	var selected = oOpt.selected;
	
	var oField = document.getElementById(field);
	
	if(oField.nodeName == "SELECT") {
		// delete old options
		for(var i = oField.length; i >= 0; i--){
			oField.options[i] = null;
		}
		
		if(aObjects && aObjects.length > 0) {
			var bSelected = false;
			
			// fill with new options
			for(i = 0; i < aObjects.length; i++) {
				var oName = '';
				var bSelect = false;
				var bSelectDefault = false;
				
				if(type == "service") {
					oName = aObjects[i].service_description;
				} else {
					oName = aObjects[i].name;
				}
				
				if(selected != '' && oName == selected) {
					bSelectDefault = true;
					bSelect = true;
					bSelected = true;
				}
				
				oField.options[i] = new Option(oName, oName, bSelectDefault, bSelect);
			}
		}
		
		// Give the users the option give manual input
		oField.options[oField.options.length] = new Option(lang['manualInput'], lang['manualInput'], false, false);
	}
	
	// Fallback to input field when configured value could not be selected or
	// the list is empty
	if((selected != '' && !bSelected) || !aObjects || aObjects.length <= 0) {
		toggleFieldType(oField.name, oField.value)
	}
}

// function that checks the object is valid : all the properties marked with a * (required) have a value
// if the object is valid it writes the list of its properties/values in an invisible field, which will be passed when the form is submitted
function validateMapCfgForm() {
	var object_name = '';
	var line_type = '';
	var iconset = '';
	var x = '';
	var y = '';
	
	// Terminate fast when validateMapConfigFieldValue marked the form contents
	// as invalid
	if(bFormIsValid === false) {
		return false;
	}
	
	for(var i=0, len = document.addmodify.elements.length; i < len; i++) {
		if(document.addmodify.elements[i].type != 'submit' && document.addmodify.elements[i].type != 'hidden') {
			// Filter helper fields
			if(document.addmodify.elements[i].name.charAt(0) !== '_') {
				var sName = document.addmodify.elements[i].name;
				var sType = document.addmodify.type.value;
				
				var oField = document.getElementById(sName);
				var oFieldDefault = document.getElementById('_'+sName);
				if(oField && oFieldDefault) {
					if(oField.value === oFieldDefault.value && validMapConfig[sType][sName]['must'] != '1') {
						// Skip option only when the field and default value are equal
						continue;
					}
				}
				
				oFieldDefault = null;
				oField = null;
				
				if(sName.substring(sName-6, sName.length) == '_name') {
					object_name = document.addmodify.elements[i].value;
				}
				if(sName == 'iconset') {
					iconset = document.addmodify.elements[i].value;
				}
				if(sName == 'x') {
					x = document.addmodify.elements[i].value;
				}
				if(sName == 'y') {
					y = document.addmodify.elements[i].value;
				}
				
				if(document.addmodify.elements[i].value != '') {
					// Print a note to the user: This map object will display the summary state of the current map
					if(sType == "map" && sName == "map_name" && document.addmodify.elements[i].value == document.addmodify.map.value) {
						alert(printLang(lang['mapObjectWillShowSummaryState'],''));
					}
				} else {
					if(validMapConfig[sType][sName]['must'] == '1') {
						alert(printLang(lang['mustValueNotSet'],'ATTRIBUTE~'+sName+',TYPE~'+sType+',MAPNAME~'+document.addmodify.map.value));
						document.addmodify.elements[i].focus();
						
						return false;
					}
				}
			}
		}
	}
	
	// we make some post tests (concerning the line_type and iconset values)
	if((document.addmodify.view_type && document.addmodify.view_type.value == 'line') || document.addmodify.type == 'line') {
		// we verify that the current line_type is valid
		var valid_list = new Array("10","11","12","13","14");
		for(var j = 0, len = valid_list.length; valid_list[j] != document.addmodify.line_type.value && j < len; j++) {
			if(j==valid_list.length) {
				alert(printLang(lang['chosenLineTypeNotValid'],''));
				return false;
			}
		}
		
		// we verify we don't have both iconset and line_type defined
		if(document.addmodify.iconset && document.addmodify.iconset.value != '') {
			alert(printLang(lang['onlyLineOrIcon'],''));
			return false;
		}
		
		// we verify we have 2 x coordinates and 2 y coordinates
		if(document.addmodify.x && document.addmodify.x.value.split(",").length != 2) {
			alert(printLang(lang['not2coordsX'],'COORD~X'));
			return false;
		}
		
		if(document.addmodify.y && document.addmodify.y.value.split(",").length != 2) {
			alert(printLang(lang['not2coordsY'],'COORD~Y'));
			return false;
		}
		
		if(document.addmodify.line_type && document.addmodify.line_type.value == '') {
			alert(printLang(lang["lineTypeNotSet"], ''));
			document.addmodify.line_type.focus();
			
			return false;
		}
	}
	
	if(document.addmodify.x && document.addmodify.x.value.split(",").length > 1) {
		if(document.addmodify.x.value.split(",").length != 2) {
			alert(printLang(lang["only1or2coordsX"],'COORD~X'));
			return false;
		} else {
			if(document.addmodify.type != 'line' && document.addmodify.view_type && document.addmodify.view_type.value != 'line') {
				alert(printLang(lang["viewTypeWrong"],'COORD~X'));
				return false;
			}
			
			if(document.addmodify.line_type.value == '') {
				alert(printLang(lang["lineTypeNotSet"], ''));
				return false;
			}
		}
	}
	
	if(document.addmodify.y && document.addmodify.y.value.split(",").length > 1) {
		if(document.addmodify.y.value.split(",").length != 2) {
			alert(printLang(lang["only1or2coordsY"],'COORD~Y'));
			return false;
		} else {
			if(document.addmodify.type != 'line' && document.addmodify.view_type && document.addmodify.view_type.value != 'line') {
				alert(printLang(lang["viewTypeWrong"],'COORD~Y'));
				return false;
			}
			
			if(document.addmodify.line_type.value == '') {
				alert(printLang(lang["lineTypeNotSet"], ''));
				return false;
			}
		}
	}
	
	return true;
}

/**
 * validateMapConfigFieldValue(oField)
 *
 * This function checks a config field value for valid format. The check is done
 * by the match regex from validMapConfig array.
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function validateMapConfigFieldValue(oField) {
	var sName;
	var bInputHelper = false;
	var bChanged;
	
	if(oField.name.indexOf('_inp_') !== -1) {
		sName = oField.name.replace('_inp_', '');
		bInputHelper = true;
	} else {
		sName = oField.name;
	}
	// Check if "manual input" was selected in this field. If so: change the field
	// type from select to input
	bChanged = toggleFieldType(oField.name, oField.value);
	
	// Toggle the value of the field. If empty or just switched the function will
	// try to display the default value
	toggleDefaultOption(sName, bChanged);
	
	// Check if some fields depend on this. If so: Add a javacript 
	// event handler function to toggle these fields
	toggleDependingFields("addmodify", sName, oField.value);
	
	// Only validate when field type not changed
	if(!bChanged) {
		bFormIsValid = validateValue(sName, oField.value, validMapConfig[document.addmodify.type.value][sName].match);
		
		return bFormIsValid;
	} else {
		return false;
	}
}
