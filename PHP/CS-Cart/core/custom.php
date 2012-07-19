<?php
    // Include DevKit file
    include_once('devkit.php');
    include_once('..\config.php');
    include_once('functions.php');

    // Create new instance of DevKit
    $d = new DevKit();

    // Read from post or file
    if (isset($_GET['backup']))
    {
        //save file
        //$filename = 'db-backup-'.time().'-'.(md5(implode(',',$tables))).'.sql';
        $filename       = 'db-backup.sql';
        $outputToFile   = true;
        //$tables = array('cm_shop_order_items', 'cm_shop_orders');
        $tables         = '*';
        backupTables(DB_SERVER,DB_USERNAME,DB_PASSWORD,DB_DATABASE, $tables);
        Disconnect($connection);
    }
    
    // Reset all notifications
    if (isset($_GET['resetnotifications']))
    {
        markUnposted();
        Disconnect($connection);
    }

    // Note if using multiple statuses then check this won't work...
    function markUnposted()
    {
        Global $connection;
        $sql        = "UPDATE %s SET %s = '0', %s = '%s';";
        $sql        = sprintf($sql, OrdersTable, OrdersTable_PostedColumn, OrdersTable_StatusColumn, OrderStatus);
        $result     = mysql_query($sql, $connection) or die("Couldn't update Order: " . $orderID . " </br>\n$sql</br>\n " . mysql_error() . "</br></br>\n\n");
        echo ("Updated Orders Table - All orders marked as un-posted.</br>\n");
        echo($sql);
    }

    /* backup the db OR just a table */
    function backupTables($host,$user,$pass,$name,$tables = '*')
    {
        Global         $filename;
        Global         $outputToFile;

        $link = mysql_connect($host,$user,$pass);
        mysql_select_db($name,$link);

        //get all of the tables
        if($tables == '*')
        {
            $tables = array();
            $result = mysql_query('SHOW TABLES');

            while($row = mysql_fetch_row($result))
            {
                $tables[] = $row[0];
            }
        }
        else
        {
            $tables = is_array($tables) ? $tables : explode(',',$tables);
        }

        //cycle through
        foreach($tables as $table)
        {
            $result = mysql_query('SELECT * FROM '.$table);
            $num_fields = mysql_num_fields($result);
            $return .= 'DROP TABLE '.$table.';';

            $row2 = mysql_fetch_row(mysql_query('SHOW CREATE TABLE '.$table));

            $return.= "\n\n".$row2[1].";\n\n";

            for ($i = 0; $i < $num_fields; $i++)
            {
                while($row = mysql_fetch_row($result))
                {
                    $return.= 'INSERT INTO '.$table.' VALUES(';
                    for($j=0; $j<$num_fields; $j++)
                    {
                        $row[$j] = htmlspecialchars($row[$j]);
                        $row[$j] = preg_replace("/\n/","\\n",$row[$j]);
                        //$row[$j] = reg_replace("\n","\\n",$row[$j]);
                        if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
                        if ($j<($num_fields-1)) { $return.= ','; }
                    }
                    $return.= ");\n";
                }
            }
            $return.="\n\n\n";
        }

        if ($outputToFile)
        {
            try
            {
                //$handle = fopen('db-backup-'.time().'-'.(md5(implode(',',$tables))).'.sql','w+');
                $handle = fopen($filename,'w+');

                // Write to our opened file.
                if (fwrite($handle, $return) === FALSE)
                {
                    echo("Cannot write to file ($filename) - Try manually creating the file first and then chmod to 777.");
                    exit;
                }
                //echo('Backup complete: db-backup-'.time().'-'.(md5(implode(',',$tables))).'.sql');
                echo("Backup complete: $filename");
            }
            catch (Exception $e)
            {
                echo("Backup failed: $e");
            }

            fclose($handle);
        }
        else
        {
            echo($return);
        }
        
    }