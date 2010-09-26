<?php
/*******************************************************************************
 *
 * CoreMySQLHandler.php - Class to handle MySQL databases
 *
 * Copyright (c) 2004-2010 NagVis Project (Contact: info@nagvis.org)
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
 ******************************************************************************/

/**
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
class CoreMySQLHandler {
	private $DB = null;
	private $file = null;
	
	public function __construct() {}
	
	// First check if the php installation supports MySQL and then try to connect
	public function open($host, $port, $db, $user, $pw) {
		if($this->checkMySQLSupport())
			if($this->connectDB($host, $port, $db, $user, $pw))
				return true;
		return false;
	}
	
	/**
	 * PRIVATE Method connectDB
	 *
	 * Connects to DB
	 *
	 * @return	Boolean
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function connectDB($host, $port, $db, $user, $pw) {
		// don't want to see mysql errors from connecting - only want our error messages
		$oldLevel = error_reporting(0);
		
		$this->DB = mysql_connect($host.':'.$port, $user, $pw);
		
		if(!$this->DB) {
			new GlobalMessage('ERROR', GlobalCore::getInstance()->getLang()->getText('errorConnectingMySQL',
                                       Array('BACKENDID' => 'MySQLHandler','MYSQLERR' => mysql_error())));
			return false;
		}
		
		$returnCode = mysql_select_db($db, $this->DB);
		
		// set the old level of reporting back
		error_reporting($oldLevel);
		
		if(!$returnCode){
			new GlobalMessage('ERROR', GlobalCore::getInstance()->getLang()->getText('errorSelectingDb',
                         Array('BACKENDID' => 'MySQLHandler', 'MYSQLERR' => mysql_error($this->CONN))));
			return false;
		} else {
			return true;
		}
	}
	
	public function tableExist($table) {
		return mysql_num_rows($this->query('SHOW TABLES LIKE \''.$table.'\'')) > 0;
	}
	
	public function query($query) {
		$HANDLE = mysql_query($query, $this->DB) or die(mysql_error());
		return $HANDLE;
	}
	
	public function count($query) {
	  return mysql_num_rows($this->query($query));
	}
	
	public function fetchAssoc($RES) {
		return mysql_fetch_assoc($RES);
	}
	
	public function close() {
		$this->DB = null;
	}
	
	public function escape($s) {
		return "'".mysql_real_escape_string($s)."'";
	}
	
	private function checkMySQLSupport($printErr = 1) {
		if(!extension_loaded('mysql')) {
			if($printErr === 1) {
				new GlobalMessage('ERROR', GlobalCore::getInstance()->getLang()->getText('Your PHP installation does not support mysql. Please check if you installed the PHP module.'));
			}
			return false;
		} else {
			return true;
		}
	}

	public function deletePermissions($mod, $name) {
		// Only create when not existing
		if($this->count('SELECT `mod` FROM perms WHERE `mod`='.$this->escape($mod).' AND `act`=\'view\' AND obj='.$this->escape($name)) > 0) {
			if(DEBUG&&DEBUGLEVEL&2) debug('MySQLHandler: delete permissions for '.$mod.' '.$name);
			$this->query('DELETE FROM perms WHERE `mod`='.$this->escape($mod).' AND obj='.$this->escape($name).'');
			$this->query('DELETE FROM roles2perms WHERE permId=(SELECT permId FROM perms WHERE `mod`='.$this->escape($mod).' AND obj='.$this->escape($name).')');
		} else {
			if(DEBUG&&DEBUGLEVEL&2) debug('MySQLHandler: won\'t delete '.$mod.' permissions '.$name);
		}
	}
	
	public function createMapPermissions($name) {
		// Only create when not existing
		if($this->count('SELECT `mod` FROM perms WHERE `mod`=\'Map\' AND `act`=\'view\' AND obj='.$this->escape($name)) <= 0) {
			if(DEBUG&&DEBUGLEVEL&2) debug('MySQLHandler: create permissions for map '.$name);
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'view\', '.$this->escape($name).')');
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'getMapProperties\', '.$this->escape($name).')');
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'getMapObjects\', '.$this->escape($name).')');
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'getObjectStates\', '.$this->escape($name).')');
			
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'edit\', '.$this->escape($name).')');
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'delete\', '.$this->escape($name).')');
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'doEdit\', '.$this->escape($name).')');
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'doDelete\', '.$this->escape($name).')');
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'doRename\', '.$this->escape($name).')');
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'modifyObject\', '.$this->escape($name).')');
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'createObject\', '.$this->escape($name).')');
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'deleteObject\', '.$this->escape($name).')');
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'addModify\', '.$this->escape($name).')');
		} else {
			if(DEBUG&&DEBUGLEVEL&2) debug('MySQLHandler: won\'t create permissions for map '.$name);
		}
		
		return true;
	}
	
	public function createAutoMapPermissions($name) {
		// Only create when not existing
		if($this->count('SELECT `mod` FROM perms WHERE `mod`=\'AutoMap\' AND `act`=\'view\' AND obj='.$this->escape($name)) <= 0) {
			if(DEBUG&&DEBUGLEVEL&2) debug('MySQLHandler: create permissions for automap '.$name);
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'AutoMap\', \'view\', '.$this->escape($name).')');
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'AutoMap\', \'getAutomapProperties\', '.$this->escape($name).')');
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'AutoMap\', \'getAutomapObjects\', '.$this->escape($name).')');
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'AutoMap\', \'getObjectStates\', '.$this->escape($name).')');
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'AutoMap\', \'parseAutomap\', '.$this->escape($name).')');
			
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'AutoMap\', \'edit\', '.$this->escape($name).')');
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'AutoMap\', \'delete\', '.$this->escape($name).')');
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'AutoMap\', \'doEdit\', '.$this->escape($name).')');
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'AutoMap\', \'doDelete\', '.$this->escape($name).')');
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'AutoMap\', \'doRename\', '.$this->escape($name).')');
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'AutoMap\', \'modifyObject\', '.$this->escape($name).')');
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'AutoMap\', \'createObject\', '.$this->escape($name).')');
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'AutoMap\', \'deleteObject\', '.$this->escape($name).')');
		} else {
			if(DEBUG&&DEBUGLEVEL&2) debug('MySQLHandler: won\'t create permissions for automap '.$name);
		}
		
		return true;
	}
	
	public function createRotationPermissions($name) {
		// Only create when not existing
		if($this->count('SELECT `mod` FROM perms WHERE `mod`=\'Rotation\' AND `act`=\'view\' AND obj='.$this->escape($name)) <= 0) {
			if(DEBUG&&DEBUGLEVEL&2) debug('MySQLHandler: create permissions for rotation '.$name);
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Rotation\', \'view\', '.$this->escape($name).')');
		} else {
			if(DEBUG&&DEBUGLEVEL&2) debug('MySQLHandler: won\'t create permissions for rotation '.$name);
		}
		
		return true;
	}

	private function addRolePerm($roleId, $mod, $act, $obj) {
		$this->query('INSERT INTO roles2perms (roleId, permId) VALUES ('.$roleId.', (SELECT permId FROM perms WHERE `mod`=\''.$mod.'\' AND `act`=\''.$act.'\' AND obj=\''.$obj.'\'))');
	}

	public function updateDb() {
		if(!$this->tableExist('version')) {
			// Perform pre b4 updates
			$this->updateDb15b4();
		}
	}

	private function updateDb15b4() {
		if(DEBUG&&DEBUGLEVEL&2) debug('MySQLHandler: Performing update to 1.5b4 scheme');
		
		$this->createVersionTable();

		// Add addModify permission
		$RES = $this->query('SELECT obj FROM perms WHERE `mod`=\'Map\' AND `act`=\'view\'');
		while($data = $this->fetchAssoc($RES)) {
			if(DEBUG&&DEBUGLEVEL&2) debug('MySQLHandler: Adding new addModify perms for map '.$data['obj']);
			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'addModify\', '.$this->escape($data['obj']).')');
		}

		// Assign the addModify permission to the managers
		$RES = $this->query('SELECT roleId FROM roles WHERE name=\'Managers\'');
		while($data = $this->fetchAssoc($RES)) {
			if(DEBUG&&DEBUGLEVEL&2) debug('MySQLHandler: Assigning addModify perms to Managers role');
			$this->addRolePerm($data['roleId'], 'Map', 'addModify', '*');
		}
	}

	private function createVersionTable() {
		$this->query('CREATE TABLE version (version VARCHAR(100), PRIMARY KEY(version))');
		$this->query('INSERT INTO version (version) VALUES (\''.CONST_VERSION.'\')');
	}
	
	public function createInitialDb() {
		$this->query('CREATE TABLE users (userId INTEGER AUTO_INCREMENT, name VARCHAR(100), password VARCHAR(40), PRIMARY KEY(userId), UNIQUE(name))');
		$this->query('CREATE TABLE roles (roleId INTEGER AUTO_INCREMENT, name VARCHAR(100), PRIMARY KEY(roleId), UNIQUE(name))');
		$this->query('CREATE TABLE perms (`permId` INTEGER AUTO_INCREMENT, `mod` VARCHAR(100), `act` VARCHAR(100), `obj` VARCHAR(100), PRIMARY KEY(`permId`), UNIQUE(`mod`, `act`, `obj`))');
		$this->query('CREATE TABLE users2roles (userId INTEGER, roleId INTEGER, PRIMARY KEY(userId, roleId))');
		$this->query('CREATE TABLE roles2perms (roleId INTEGER, permId INTEGER, PRIMARY KEY(roleId, permId))');

		$this->createVersionTable();
		
		$this->query('INSERT INTO users (userId, name, password) VALUES (1, \'nagiosadmin\', \'7f09c620da83db16ef9b69abfb8edd6b849d2d2b\')');
		$this->query('INSERT INTO users (userId, name, password) VALUES (2, \'guest\', \'7f09c620da83db16ef9b69abfb8edd6b849d2d2b\')');
		$this->query('INSERT INTO roles (roleId, name) VALUES (1, \'Administrators\')');
		$this->query('INSERT INTO roles (roleId, name) VALUES (2, \'Users (read-only)\')');
		$this->query('INSERT INTO roles (roleId, name) VALUES (3, \'Guests\')');
		$this->query('INSERT INTO roles (roleId, name) VALUES (4, \'Managers\')');
		
		// Access controll: Full access to everything
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'*\', \'*\', \'*\')');
		
		// Access controll: Overview module levels
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Overview\', \'view\', \'*\')');
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Overview\', \'getOverviewRotations\', \'*\')');
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Overview\', \'getOverviewProperties\', \'*\')');
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Overview\', \'getOverviewMaps\', \'*\')');
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Overview\', \'getOverviewAutomaps\', \'*\')');
		
		// Access controll: Access to all General actions
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'General\', \'*\', \'*\')');
		
		// Access controll: Map module levels for map "demo"
		$this->createMapPermissions('demo');
		
		// Access controll: Map module levels for map "demo2"
		$this->createMapPermissions('demo2');
		
		// Access controll: Map module levels for map "demo-map"
		$this->createMapPermissions('demo-map');
		
		// Access controll: Map module levels for map "demo-server"
		$this->createMapPermissions('demo-server');
		
		// Access controll: Rotation module levels for rotation "demo"
		$this->createRotationPermissions('demo');
		
		// Access controll: Automap module levels for automap "__automap"
		$this->createAutoMapPermissions('__automap');
		
		// Access controll: Change own password
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'ChangePassword\', \'view\', \'*\')');
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'ChangePassword\', \'change\', \'*\')');
	
		// Access controll: Search objects on maps
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Search\', \'view\', \'*\')');
		
		// Access controll: Authentication: Logout
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Auth\', \'logout\', \'*\')');
		
		// Access controll: Summary permissions for viewing/editing/deleting all maps
		$this->createMapPermissions('*');
		
		// Access controll: Summary permissions for viewing/editing/deleting all automaps
		$this->createAutoMapPermissions('*');
		
		// Access controll: Rotation module levels for viewing all rotations
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Rotation\', \'view\', \'*\')');
		
		// Access controll: Manage users
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'UserMgmt\', \'manage\', \'*\')');
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'UserMgmt\', \'view\', \'*\')');
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'UserMgmt\', \'getUserRoles\', \'*\')');
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'UserMgmt\', \'getAllRoles\', \'*\')');
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'UserMgmt\', \'doAdd\', \'*\')');
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'UserMgmt\', \'doEdit\', \'*\')');
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'UserMgmt\', \'doDelete\', \'*\')');
		
		// Access controll: Manage roles
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'RoleMgmt\', \'manage\', \'*\')');
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'RoleMgmt\', \'view\', \'*\')');
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'RoleMgmt\', \'getRolePerms\', \'*\')');
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'RoleMgmt\', \'doAdd\', \'*\')');
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'RoleMgmt\', \'doEdit\', \'*\')');
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'RoleMgmt\', \'doDelete\', \'*\')');
		
		// Access controll: Edit/Delete maps and automaps
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'add\', \'*\')');
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'doAdd\', \'*\')');
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'AutoMap\', \'add\', \'*\')');
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'AutoMap\', \'doAdd\', \'*\')');
		
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'MainCfg\', \'edit\', \'*\')');
		$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'MainCfg\', \'doEdit\', \'*\')');
		
		/*
		 * Administrators handling
		 */
		
		$data = $this->fetchAssoc($this->query('SELECT roleId FROM roles WHERE name=\'Administrators\''));
		 
		// Role assignment: nagiosadmin => Administrators
		$this->query('INSERT INTO users2roles (userId, roleId) VALUES (1, '.$data['roleId'].')');
		
		// Access assignment: Administrators => * * *
		$this->addRolePerm($data['roleId'], '*', '*', '*');
		
		/*
		 * Managers handling
		 */
		
		$data = $this->fetchAssoc($this->query('SELECT roleId FROM roles WHERE name=\'Managers\''));
		
		// Permit all actions in General module
		$this->addRolePerm($data['roleId'], 'General', '*', '*');
		
		// Access assignment: Managers => Allowed to edit/delete all maps
		$this->addRolePerm($data['roleId'], 'Map', 'delete', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'doDelete', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'edit', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'doEdit', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'doRename', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'modifyObject', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'createObject', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'deleteObject', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'addModify', '*');
		
		// Access assignment: Managers => Allowed to create maps
		$this->addRolePerm($data['roleId'], 'Map', 'add', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'doAdd', '*');
		
		// Access assignment: Managers => Allowed to edit/delete all automaps
		$this->addRolePerm($data['roleId'], 'AutoMap', 'delete', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'doDelete', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'edit', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'doEdit', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'doRename', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'modifyObject', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'createObject', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'deleteObject', '*');
		
		// Access assignment: Managers => Allowed to create automaps
		$this->addRolePerm($data['roleId'], 'AutoMap', 'add', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'doAdd', '*');
		
		// Access assignment: Managers => Allowed to view the overview
		$this->addRolePerm($data['roleId'], 'Overview', 'view', '*');
		$this->addRolePerm($data['roleId'], 'Overview', 'getOverviewRotations', '*');
		$this->addRolePerm($data['roleId'], 'Overview', 'getOverviewProperties', '*');
		$this->addRolePerm($data['roleId'], 'Overview', 'getOverviewMaps', '*');
		$this->addRolePerm($data['roleId'], 'Overview', 'getOverviewAutomaps', '*');
		//$this->query('INSERT INTO roles2perms (roleId, permId) VALUES ('.$data['roleId'].', )');
		
		// Access assignment: Managers => Allowed to view all maps
		$this->addRolePerm($data['roleId'], 'Map', 'view', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'getMapProperties', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'getMapObjects', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'getObjectStates', '*');
		
		// Access assignment: Managers => Allowed to view all rotations
		$this->addRolePerm($data['roleId'], 'Rotation', 'view', '*');
		
		// Access assignment: Managers => Allowed to view all automaps
		$this->addRolePerm($data['roleId'], 'AutoMap', 'view', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'getAutomapProperties', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'getAutomapObjects', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'getObjectStates', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'parseAutomap', '*');
		
		// Access assignment: Managers => Allowed to change their passwords
		$this->addRolePerm($data['roleId'], 'ChangePassword', 'view', '*');
		$this->addRolePerm($data['roleId'], 'ChangePassword', 'change', '*');
		
		// Access assignment: Managers => Allowed to search objects
		$this->addRolePerm($data['roleId'], 'Search', 'view', '*');
		
		// Access assignment: Managers => Allowed to logout
		$this->addRolePerm($data['roleId'], 'Auth', 'logout', '*');
		
		/*
		 * Users handling
		 */
		
		$data = $this->fetchAssoc($this->query('SELECT roleId FROM roles WHERE name=\'Users (read-only)\''));
		
		// Permit all actions in General module
		$this->addRolePerm($data['roleId'], 'General', '*', '*');
		
		// Access assignment: Users => Allowed to view the overview
		$this->addRolePerm($data['roleId'], 'Overview', 'view', '*');
		$this->addRolePerm($data['roleId'], 'Overview', 'getOverviewRotations', '*');
		$this->addRolePerm($data['roleId'], 'Overview', 'getOverviewProperties', '*');
		$this->addRolePerm($data['roleId'], 'Overview', 'getOverviewMaps', '*');
		$this->addRolePerm($data['roleId'], 'Overview', 'getOverviewAutomaps', '*');
		
		// Access assignment: Users => Allowed to view all maps
		$this->addRolePerm($data['roleId'], 'Map', 'view', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'getMapProperties', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'getMapObjects', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'getObjectStates', '*');
		
		// Access assignment: Users => Allowed to view all rotations
		$this->addRolePerm($data['roleId'], 'Rotation', 'view', '*');
		
		// Access assignment: Users => Allowed to view all automaps
		$this->addRolePerm($data['roleId'], 'AutoMap', 'view', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'getAutomapProperties', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'getAutomapObjects', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'getObjectStates', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'parseAutomap', '*');
		
		// Access assignment: Users => Allowed to change their passwords
		$this->addRolePerm($data['roleId'], 'ChangePassword', 'view', '*');
		$this->addRolePerm($data['roleId'], 'ChangePassword', 'change', '*');
		
		// Access assignment: Users => Allowed to search objects
		$this->addRolePerm($data['roleId'], 'Search', 'view', '*');
		
		// Access assignment: Users => Allowed to logout
		$this->addRolePerm($data['roleId'], 'Auth', 'logout', '*');
		
		/*
		 * Guest handling
		 */
		
		$data = $this->fetchAssoc($this->query('SELECT roleId FROM roles WHERE name=\'Guests\''));
		
		// Role assignment: guest => Guests
		$this->query('INSERT INTO users2roles (userId, roleId) VALUES (2, '.$data['roleId'].')');
		
		// Permit all actions in General module
		$this->addRolePerm($data['roleId'], 'General', '*', '*');
		
		// Access assignment: Guests => Allowed to view the overview
		$this->addRolePerm($data['roleId'], 'Overview', 'view', '*');
		$this->addRolePerm($data['roleId'], 'Overview', 'getOverviewRotations', '*');
		$this->addRolePerm($data['roleId'], 'Overview', 'getOverviewProperties', '*');
		$this->addRolePerm($data['roleId'], 'Overview', 'getOverviewMaps', '*');
		$this->addRolePerm($data['roleId'], 'Overview', 'getOverviewAutomaps', '*');
		
		// Access assignment: Guests => Allowed to view the demo, demo2, demo-map and demo-servers map
		$this->addRolePerm($data['roleId'], 'Map', 'view', 'demo');
		$this->addRolePerm($data['roleId'], 'Map', 'getMapProperties', 'demo');
		$this->addRolePerm($data['roleId'], 'Map', 'getMapObjects', 'demo');
		$this->addRolePerm($data['roleId'], 'Map', 'getObjectStates', 'demo');
		$this->addRolePerm($data['roleId'], 'Map', 'view', 'demo2');
		$this->addRolePerm($data['roleId'], 'Map', 'getMapProperties', 'demo2');
		$this->addRolePerm($data['roleId'], 'Map', 'getMapObjects', 'demo2');
		$this->addRolePerm($data['roleId'], 'Map', 'getObjectStates', 'demo2');
		$this->addRolePerm($data['roleId'], 'Map', 'view', 'demo-map');
		$this->addRolePerm($data['roleId'], 'Map', 'getMapProperties', 'demo-map');
		$this->addRolePerm($data['roleId'], 'Map', 'getMapObjects', 'demo-map');
		$this->addRolePerm($data['roleId'], 'Map', 'getObjectStates', 'demo-map');
		$this->addRolePerm($data['roleId'], 'Map', 'view', 'demo-server');
		$this->addRolePerm($data['roleId'], 'Map', 'getMapProperties', 'demo-server');
		$this->addRolePerm($data['roleId'], 'Map', 'getMapObjects', 'demo-server');
		$this->addRolePerm($data['roleId'], 'Map', 'getObjectStates', 'demo-server');
		
		// Access assignment: Guests => Allowed to view the demo rotation
		$this->addRolePerm($data['roleId'], 'Rotation', 'view', 'demo');
		
		// Access assignment: Guests => Allowed to view the __automap automap
		$this->addRolePerm($data['roleId'], 'AutoMap', 'view', '__automap');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'getAutomapProperties', '__automap');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'getAutomapObjects', '__automap');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'getObjectStates', '__automap');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'parseAutomap', '__automap');
		
		// Access assignment: Guests => Allowed to change their passwords
		$this->addRolePerm($data['roleId'], 'ChangePassword', 'view', '*');
		$this->addRolePerm($data['roleId'], 'ChangePassword', 'change', '*');
		
		// Access assignment: Guests => Allowed to search objects
		$this->addRolePerm($data['roleId'], 'Search', 'view', '*');
		
		// Access assignment: Guests => Allowed to logout
		$this->addRolePerm($data['roleId'], 'Auth', 'logout', '*');
	}
}
?>