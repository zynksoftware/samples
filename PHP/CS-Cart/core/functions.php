<?php
    /*
      *  Convert a string containing XML into a nested array
      *
      *  Takes a raw XML string as input
      *  Returns a nested array
    */
    function xml2array($xml_data)
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
                    //$tag_or_id = (array_key_exists ('attributes', $xml_elem)) ? $xml_elem['attributes']['Id'] : $xml_elem['tag'];
                    $tag_or_id = $xml_elem['tag'];
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

    function safeRound($val, $dp)
    {
        $output = round($val, $dp);
  
        if ($output == '')
        {
            $output = '0.00';
        }
 
        return $output;
    }

    function safeGetAddress($array, $index, $default = '')
    {
        if (count($array) >= $index+1)
        {
            return $array[$index];
        }

        return $default;
    }

    
    function sendEmail($orderID, $to, $from)
    {
        $subject    = "Order Recieved for " . $orderID . " .";
        $body       = "Order Recieved for " . $orderID . " .";
        $headers    = "From: " . $from . "\r\n" ;

        mail($to, $subject, $body, $headers);
    }

    /*
      *  Explodes an array to create a SQL string for multiple entries
      *
      *  Takes string as input
      *  Returns a string
    */
    function sqlExplode($inputArray, $field)
    {
        $Array       = explode(",", $inputArray);
        $ArraySize   = sizeof($Array);
        $sql         = " ";

        for ($i = 0 ; $i < $ArraySize ; $i++)
        {
            if ($i == 0)
            {
                $sql .= " AND ";

                if  ($ArraySize > 1) { $sql .= " ("; }

                $sql .= "$field = '" . $Array[$i] . "'";
            }
            else
            {
                $sql .= " OR $field = '" . $Array[$i] . "'";
            }
        }
        
        if  ($ArraySize > 1) { $sql .= " )"; }

        return $sql;
    }

?>