<?php
// Admin page for Full-Text RSS
// Author: Keyvan Minoukadeh
// Copyright (c) 2012 Keyvan Minoukadeh
// License: AGPLv3
// Date: 2012-04-29
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
// Access this file in your browser

error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors", 1);
@set_time_limit(120);

////////////////////////////////
// Load config file
////////////////////////////////
require_once('../config.php');
require_once('require_login.php');
require_once('template.php');
tpl_header('Admin');

?>
<p>The admin pages are intended to help you manage your copy of Full-Text RSS more easily.</p>
<ul>
<li><a href="update.php">Update patterns</a>: an easy way to keep site config files up to date.</li>
<li><a href="edit-pattern.php">Edit patterns</a>: need to fine-tune extraction for a certain site? Use this tool.</li>
<li><a href="apc.php?OB=3">APC</a>: If APC is enabled, you can use this tool to see what Full-Text RSS caches, and clear the cache if you need to.</li>
</ul>