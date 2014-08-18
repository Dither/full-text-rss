<?php
// Require login for admin access
// Author: Keyvan Minoukadeh
// Copyright (c) 2013 Keyvan Minoukadeh
// License: AGPLv3
// Date: 2013-05-09
// More info: http://fivefilters.org/content-only/
// Help: http://help.fivefilters.org

/*
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Usage
// -----
// This file is included on pages which require admin privileges - e.g. updating the software.
// The username is 'admin' by default and the password should be set in the custom_config.php file.
session_start();
require_once(dirname(dirname(__FILE__)).'/config.php');

if (isset($_GET['logout'])) $_SESSION['auth'] = 0;

if (!isset($_SESSION['auth']) || $_SESSION['auth'] != 1) {
	if (isset($admin_page)) {
		header('Location: login.php?redirect='.$admin_page);
	} else {
		header('Location: login.php');
	}
	exit;
}