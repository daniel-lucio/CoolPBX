<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//make sure that prefix-a-leg is set to true in the xml_cdr.conf.xml file

	if ($domains_processed == 1) {
		/*
		$file_contents = file_get_contents($_SESSION['switch']['conf']['dir']."/autoload_configs/xml_cdr.conf.xml");
		$file_contents_new = str_replace("param name=\"prefix-a-leg\" value=\"false\"/", "param name=\"prefix-a-leg\" value=\"true\"/", $file_contents);
		if ($file_contents != $file_contents_new) {
			$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/xml_cdr.conf.xml","w");
			fwrite($fout, $file_contents_new);
			fclose($fout);
			if ($display_type == "text") {
				echo "	xml_cdr.conf.xml: 	updated\n";
			}
		}
		*/

       //create a view for xml details
               $database = new database;
               $database->execute("DROP VIEW h;", null);
               $sql = "CREATE VIEW h  AS ( SELECT 0 AS s_id, 1 AS s_start, 0 AS s_end, 1 AS s_hour UNION SELECT 1 AS s_id, 2 AS s_start, 1 AS s_end, 1 AS s_hour UNION SELECT 2 AS s_id, 3 AS s_start, 2 AS s_end, 1 AS s_hour UNION SELECT 3 AS s_id, 4 AS s_start, 3 AS s_end, 1 AS s_hour UNION SELECT 4 AS s_id, 5 AS s_start, 4 AS s_end, 1 AS s_hour UNION SELECT 5 AS s_id, 6 AS s_start, 5 AS s_end, 1 AS s_hour UNION SELECT 6 AS s_id, 7 AS s_start, 6 AS s_end, 1 AS s_hour UNION SELECT 7 AS s_id, 8 AS s_start, 7 AS s_end, 1 AS s_hour UNION SELECT 8 AS s_id, 9 AS s_start, 8 AS s_end, 1 AS s_hour UNION SELECT 9 AS s_id, 10 AS s_start, 9 AS s_end, 1 AS s_hour UNION SELECT 10 AS s_id, 11 AS s_start, 10 AS s_end, 1 AS s_hour UNION SELECT 11 AS s_id, 12 AS s_start, 11 AS s_end, 1 AS s_hour UNION SELECT 12 AS s_id, 13 AS s_start, 12 AS s_end, 1 AS s_hour UNION SELECT 13 AS s_id, 14 AS s_start, 13 AS s_end, 1 AS s_hour UNION SELECT 14 AS s_id, 15 AS s_start, 14 AS s_end, 1 AS s_hour UNION SELECT 15 AS s_id, 16 AS s_start, 15 AS s_end, 1 AS s_hour UNION SELECT 16 AS s_id, 17 AS s_start, 16 AS s_end, 1 AS s_hour UNION SELECT 17 AS s_id, 18 AS s_start, 17 AS s_end, 1 AS s_hour UNION SELECT 18 AS s_id, 19 AS s_start, 18 AS s_end, 1 AS s_hour UNION SELECT 19 AS s_id, 20 AS s_start, 19 AS s_end, 1 AS s_hour UNION SELECT 20 AS s_id, 21 AS s_start, 20 AS s_end, 1 AS s_hour UNION SELECT 21 AS s_id, 22 AS s_start, 21 AS s_end, 1 AS s_hour UNION SELECT 22 AS s_id, 23 AS s_start, 22 AS s_end, 1 AS s_hour UNION SELECT 23 AS s_id, 24 AS s_start, 23 AS s_end, 1 AS s_hour UNION SELECT 25 AS s_id, 24 AS s_start, 0 AS s_end, 24 AS s_hour UNION SELECT 26 AS s_id, 168 AS s_start, 0 AS s_end, 168 AS s_hour UNION SELECT 27 AS s_id, 720 AS s_start, 0 AS s_end, 720 AS s_hour ); \n";
               $database = new database;
               $database->execute($sql, null);
               unset($sql);
	}

?>
