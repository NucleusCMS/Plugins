*************************************************
* MySQL-SQLite wrapper for Nucleus ver 0.8      *
*************************************************

Copyright (C) 2006  Katsumi

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

----------------------------------------------------------------------

This tool is needed when using Nucleus with SQLite as database engine.


****************************** How to use ******************************

  When installing Nucleus, the access-ID and password are not required. SQLite
uses normal file as database.  This is fixed to "nucleus/sqlite/.dbsqlite" 
(if you would like another file name, please set it in "sqlite.php" file). 
Set the permission of "nucleus/sqlite/" directory to 777 or something like this. 
Then execute install.php. That's it! If you see the install-complete message, 
probably the install is succeded.  NP_SkinFiles as well as NP_SQLite are 
automatically installed.  If not, install it manually.

  The usage of Nucleus is almost (99%) the same as that of normal Nucleus.  When
installing plugins, NP_SQLite will convert the plugin files automatically if the
permission of these files is set to be writable.

