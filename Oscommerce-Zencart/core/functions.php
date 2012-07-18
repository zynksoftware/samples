<?php

    // Databse conenction
    function dbConnect()
    {
        $cnx = mysql_connect(C_DBSERVER, C_DBUSERNAME, C_DBPASSWORD) or die('Could not connect: ' . mysql_error());
        mysql_select_db(C_DBDATABASE) or die('Could not select database');

        return $cnx;
    }

    function testConnect($link = 'db_link')
    {
        global $$link, $db_error;
        $db_error = false;
        $$link = @mysql_connect(C_DBSERVER, C_DBUSERNAME, C_DBPASSWORD) or $db_error = mysql_error();

        return $$link;
    }


    function safeRound($val, $dp)
    {
        $output = round ($val, $dp);

        if ($output == '')
        {
            $output = '0.00';
        }

        return $output;
    }


    /*
      *  Convert a string containing XML into a nested array
      *
      *  Takes a raw XML string as input
      *  Returns a nested array
      */

    function xml2array ($xml_data)
    {
        // parse the XML datastring
        $xml_parser = xml_parser_create ();
        xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($xml_parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct ($xml_parser, $xml_data, $vals, $index);
        xml_parser_free ($xml_parser);

        // convert the parsed data into a PHP datatype
        $params = array();
        $ptrs[0] = & $params;
        foreach ($vals as $xml_elem)
        {
            $level = $xml_elem['level'] - 1;
            switch ($xml_elem['type'])
            {
                case 'open':
                    $tag_or_id = (array_key_exists ('attributes', $xml_elem)) ? $xml_elem['attributes']['ID'] : $xml_elem['tag'];
                    $ptrs[$level][$tag_or_id][] = array ();
                    $ptrs[$level+1] = & $ptrs[$level][$tag_or_id][count($ptrs[$level][$tag_or_id])-1];
                    break;
                case 'complete':
                    $ptrs[$level][$xml_elem['tag']] = (isset ($xml_elem['value'])) ? $xml_elem['value'] : '';
                    break;
            }
        }
        return ($params);
    }

    /**
      * Recursive XML node construction function.
      * Inputs          :
      * @param              nodeName      - The name of the top node in the given input
      * @param              nodeStructure - An array structure that matches the XML to be produced
      * @param              outStr        - Reference to a variable that the output of this function will be written to.
      * @param              noCheckEnts   - An array containing node names not to check for HTML entity conversion.
      */

    function outputXMLString($nodeName, $nodeStructure, &$outStr, $noCheckEnts = NULL)
    {
        // Output node opening tag
        $outStr .= "<$nodeName>";
        //loop through node attributes
        foreach($nodeStructure as $attr => $value)
        {
            // If attribute is a child node then call recursion
            if ( is_array($value) )
            {
                outputXMLString($attr, $value, $outStr, $noCheckEnts);
            }
            else
            {
                // If attr not in $noCheckEntities array then check for quotes and other things in values inbetween start and end tags
                // All special characters (& ' " < >) are transformed into their HTML entity. i.e '&amp;' for &
                if(is_array($noCheckEnts) && in_array($attr, $noCheckEnts))
                {
                    // Assume attribute has already been checked
                    $outStr .= "<$attr" . (($value != '') ? ">" . $value . "</$attr>" : " />");
                }
                else
                {
                    $outStr .= "<$attr" . (($value != '') ? ">" . htmlspecialchars($value, ENT_QUOTES) . "</$attr>" : " />");
                }
            }
        }
        // Output node closing tag
        $outStr .= "</$nodeName>";
    }

    function OLDoutputXMLString($nodeName, $nodeStructure, $noCheckEnts = NULL)
    {
        // Output node opening tag
        $outStr .= "<$nodeName>";
        //loop through node attributes
        foreach($nodeStructure as $attr => $value)
        {
            // If attribute is a child node then call recursion
            if ( is_array($value) )
            {
                $outStr .= outputXMLString($attr, $value, $noCheckEnts);
            }
            else
            {
                // If attr not in $noCheckEntities array then check for quotes and other things in values inbetween start and end tags
                // All special characters (& ' " < >) are transformed into their HTML entity. i.e '&amp;' for &
                if(is_array($noCheckEnts) && in_array($attr, $noCheckEnts))
                {
                    // Assume attribute has already been checked
                    $outStr .= "<$attr" . (($value != '') ? ">" . $value . "</$attr>" : " />");

                }
                else
                {
                    $outStr .= "<$attr" . (($value != '') ? ">" . htmlspecialchars($value, ENT_QUOTES) . "</$attr>" : " />");
                }
            }
        }
         // Output node closing tag
        $outStr .= "</$nodeName>";

        return $outStr;
    }


    /**
      * Date formatting function add a 'T' inbetween date and time. returns given string if null passed
      *
      * Inputs      :
      * @param      Timestamp   - A unix timestamp
      */

    function XSDDate($timestamp = NULL)
    {
        if (!empty($timestamp))
        {
            return date("Y-m-d\TH:i:s", $timestamp);
        }
        else
        {
            return "0001-01-01T00:00:00";
        }
    }


    /**
      * Function to split a string containing a multipart name into two parts (forename, surname
      * where forename will be everything but the surname
      *
      * Inputs      :
      * @param      name    - The name to be split
      */

    function splitName($name)
    {
        $wholeName = explode(' ', $name);
        $numNames = count($wholeName);
        $names = array('forename' => '', 'surname' => '');
        for ($i = 0; $i < $numNames - 1; $i++)
        {
            $names['forename'] .= $wholeName[$i];
        }
        $names['surname'] = $wholeName[$numNames - 1];
        return $names;
    }

    function testConnection($database)
    {
        global $db_error;

        $db_created = false;
        $db_error = false;

        if (!$database)
        {
          $db_error = "No Database selected - $database";
          return false;
        }

        if (!$db_error)
        {
          if (!@osc_db_select_db($database))
          {
            $db_created = true;
            if (!@osc_db_query('create database ' . $database))
            {
              $db_error = mysql_error();
            }
          } else {
            $db_error = mysql_error();
          }
          if (!$db_error)
          {
            if (@osc_db_select_db($database))
            {
              if (@osc_db_query('create table temp ( temp_id int(5) )'))
              {
                if (@osc_db_query('drop table temp'))
                {
                  if ($db_created)
                  {
                    if (@osc_db_query('drop database ' . $database))
                    {
                    }
                    else
                    {
                      $db_error = mysql_error();
                    }
                  }
                }
                else
                {
                  $db_error = mysql_error();
                }
              }
              else
              {
                $db_error = mysql_error();
              }
            }
            else
            {
              $db_error = mysql_error();
            }
          }
        }

        if ($db_error)
        {
          return false;
        }
        else
        {
          return true;
        }

    }

    // Save base64 encoded image
    function saveImage( $imageData, $outputfile )
    {
        $ifp = fopen( $outputfile, "wb" );
        fwrite( $ifp, base64_decode( $imageData) );
        fclose( $ifp );
    }

    function OrderStatusFilter()
    {
        $sql = "";
        $orderStatusArray       = explode(",", C_DOWNLOAD_ORDER_STATUS);
        $orderStatusArraySize   = sizeof($orderStatusArray);

        for ($i = 0 ; $i < $orderStatusArraySize ; $i++)
        {
            if ($i == 0)
            {
                $sql .= " AND";

                if  ($orderStatusArraySize > 1) { $sql .= " ("; }

                $sql .= " ordersStatus.orders_status_name = '" . $orderStatusArray[$i] . "'";
            }
            else
            {
                $sql .= " OR ordersStatus.orders_status_name = '" . $orderStatusArray[$i] . "'";
            }
        }

        if  ($orderStatusArraySize > 1) { $sql .= " )"; }

        return $sql;
    }

    function StatusIDFromName($status, $cnx)
    {
        $rtnVal  = null;

        $sql = "SELECT orders_status_id FROM orders_status WHERE orders_status_name = '$status'";
        $result = mysql_query($sql, $cnx) or die('Couldn\'t get Status ID for [' . $status . '] in ' . SHOPPING_CART . ' orders_status. ' . mysql_error() . ' SQL: ' . $sql);

        if (mysql_num_rows($result) > 0)
        {
            $row    = mysql_fetch_assoc($result);
            $rtnVal = $row['orders_status_id'];
        }

        return $rtnVal;
    }

    function StatusNameFromID($status, $cnx)
    {
        $rtnVal  = null;

        $sql = "SELECT orders_status_name FROM orders_status WHERE orders_status_id = $status";
        $result = mysql_query($sql, $cnx) or die('Couldn\'t get Status Name for [' . $status . '] in ' . SHOPPING_CART . ' orders_status. ' . mysql_error() . ' SQL: ' . $sql);

        if (mysql_num_rows($result) > 0)
        {
            $row    = mysql_fetch_assoc($result);
            $rtnVal = $row['orders_status_name'];
        }

        return $rtnVal;
    }
