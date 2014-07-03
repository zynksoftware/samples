<?php
class ZynkCustomers extends Controller
{
    var $pricelists;

    private function _startup()
    {
        include_once('zynk_pricelists.php');
        $this->pricelists = new ZynkPricelists($this->registry);
        
        include_once('zynk.php');
        $this->zynk = new ControllerModuleZynk($this->registry);
    }

    public function UploadCustomers(&$customers)
    {
        $this->_startup();

        foreach ($customers->Get() as $customer)
        {
            if(!empty($customer->AccountReference) && !empty($customer->CustomerInvoiceAddress->Email))
            //if ($customer->AccountReference != "" && $customer->CustomerInvoiceAddress->Email != "" )
            {
                $result = $this->GetCustomerByAccountReference($customer->AccountReference);

                $customerTelephone = '';
                if (isset($customer->CustomerInvoiceAddress->TelephoneCountryCode) || isset($customer->CustomerInvoiceAddress->TelephoneAreaCode))
                {
                    $customerTelephone = $customer->CustomerInvoiceAddress->TelephoneCountryCode.$customer->CustomerInvoiceAddress->TelephoneAreaCode;
                }

                $data = array
                (
                    'AccountReference'  => $customer->AccountReference,
                    'firstname'         => $customer->CustomerInvoiceAddress->Forename,
                    'lastname'          => $customer->CustomerInvoiceAddress->Surname,
                    'email'             => $customer->CustomerInvoiceAddress->Email,
                    'telephone'         => $customerTelephone.$customer->CustomerInvoiceAddress->Telephone,
                    'fax'               => $customer->CustomerInvoiceAddress->Fax,
                    'newsletter'        => 0,
                    'customer_group_id' => $this->config->get('config_customer_group_id'), //Set to the DEFAULT group, correct group is assigned on a price list upload
                    'password'          => $this->generateRandomString(),//"password",
                    //'status'            => ($customer->AccountStatus == 0) ? 1 : 0, //LINE50
                    //'status'            => ($customer->AccountStatus == 1) ? 1 : 0,
                    'status'            => $this->getStatus($customer),
                    'approved'          => ($customer->TermsAgreed == "true") ? 1 : 0
                );

                // Does account exist?
                if ($result->num_rows > 0)
                {
                    $customer_id = $result->row['customer_id'];
                    $this->EditCustomer($customer_id, $data);
                }
                else
                { 
                    $customer_id = $this->AddCustomer($data);

                    // Finally send an email to the customer informing them that the account has been created.
                    $this->SendEmail($data);
                }

                $country_id         = $this->GetCountryFromIsoCode($customer->CustomerInvoiceAddress->Country);
                $data = array
                (
                    'company'       => $customer->CustomerInvoiceAddress->Company,
                    'firstname'     => $customer->CustomerInvoiceAddress->Forename,
                    'lastname'      => $customer->CustomerInvoiceAddress->Surname,
                    'address_1'     => $customer->CustomerInvoiceAddress->Address1,
                    'address_2'     => $customer->CustomerInvoiceAddress->Address2,
                    'postcode'      => $customer->CustomerInvoiceAddress->Postcode,
                    'city'          => $customer->CustomerInvoiceAddress->Town,
                    'country'       => $country_id,
                    'zone'          => $this->GetZone($customer->CustomerInvoiceAddress->County, $country_id)
                );
                // Add/Edit Address
                $address_id = $this->SetAddress($customer_id, $data);

                $data = null;
            }
            else
            {
                if(empty($customer->AccountReference))
                {
                    $this->zynk->screenOutput($customer->CustomerInvoiceAddress->Forename ." ".$customer->CustomerInvoiceAddress->Surname." has not been uploaded. You must supply an Account Reference.</br>", 1);
                }
                
                if(empty($customer->CustomerInvoiceAddress->Email))
                {
                    $this->zynk->screenOutput($customer->CustomerInvoiceAddress->Forename ." ".$customer->CustomerInvoiceAddress->Surname." has not been uploaded. You must supply an email address.</br>", 1);
                }
            }
        }
    }

    public function AddCustomer($data)
    {
        $this->db->query("INSERT INTO " . DB_PREFIX . "customer SET firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', fax = '" . $this->db->escape($data['fax']) . "', sage_account_ref = '" . $this->db->escape($data['AccountReference']) . "', password = '" . $this->db->escape(md5($data['password'])) . "', status = '" . (int)$data['status'] . "', approved = '" . (int)$data['approved'] . "', date_added = NOW()");

        $customer_id = $this->db->getLastId();

        if ($data['newsletter'])
        {
            $this->db->query("UPDATE " . DB_PREFIX . "customer SET newsletter = '" . $this->db->escape($data['newsletter']) . "' WHERE sage_account_ref = '" . $this->db->escape($data['AccountReference']) . "'");
        }

        if ($data['customer_group_id'])
        {
            $this->db->query("UPDATE " . DB_PREFIX . "customer SET customer_group_id = '" . $this->db->escape($data['customer_group_id']) . "' WHERE sage_account_ref = '" . $this->db->escape($data['AccountReference']). "'");
        }

        $this->zynk->screenOutput("Inserted ". $data['firstname'] . " " . $data['lastname'] . " - Account Reference:[".$data['AccountReference']."]. </br>", 2);

        return $customer_id;
    }

    public function EditCustomer($customer_id, $data)
    {
        $this->db->query("UPDATE " . DB_PREFIX . "customer SET firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', fax = '" . $this->db->escape($data['fax']) . "', status = '" . (int)$data['status'] . "', approved = '" . (int)$data['approved'] . "' WHERE customer_id = '" . (int)$customer_id . "'");

        if ($data['newsletter'])
        {
            $this->db->query("UPDATE " . DB_PREFIX . "customer SET newsletter = '" . $this->db->escape($data['newsletter']) . "' WHERE customer_id = '" . (int)$customer_id . "'");
        }

        if ($data['customer_group_id'])
        {
            $this->db->query("UPDATE " . DB_PREFIX . "customer SET customer_group_id = '" . $this->db->escape($data['customer_group_id']) . "' WHERE customer_id = '" . (int)$customer_id . "'");
        }

        if ($data['AccountReference'])
        {
            $this->db->query("UPDATE " . DB_PREFIX . "customer SET sage_account_ref = '" . $this->db->escape($data['AccountReference']) . "' WHERE customer_id = '" . (int)$customer_id . "'");
        }
        
        $this->zynk->screenOutput("Updated ". $data['firstname'] . " " . $data['lastname'] . " - Account ID[$customer_id]. </br>", 2);
    }


    public function AssignCustomersToPricelists(&$accounts)
    {
        foreach ($accounts->Get() as $account)
        {
            // Does account exist?
            $result = $this->GetCustomerByAccountReference($account->AccountReference);
            if ($result->num_rows > 0)
            {
                if (!empty($account->DiscountGroupReference))
                {
                    $customer_group = $this->pricelists->GetPricelistByRef($account->DiscountGroupReference);

                    if ($customer_group->num_rows > 0 && $this->GetCustomerByAccountReference($account->AccountReference)->num_rows > 0)
                    {
                        $query = $this->db->query("UPDATE " . DB_PREFIX . "customer SET customer_group_id = '" . (int)$customer_group->row['customer_group_id'] . "' WHERE sage_account_ref = '" . $this->db->escape($account->AccountReference) . "'");
                        $this->zynk->screenOutput("Updated Price List for ". $account->CustomerInvoiceAddress->Forename . " " . $account->CustomerInvoiceAddress->Surname . " - Account Reference:[".$account->AccountReference."]. </br>", 2);
                    }
                }
                else
                {
                    $query = $this->db->query("UPDATE " . DB_PREFIX . "customer SET customer_group_id = '". $this->config->get('config_customer_group_id') ."' WHERE sage_account_ref = '" . $this->db->escape($account->AccountReference) . "'");
                    $this->zynk->screenOutput("Updated Price List for ". $account->CustomerInvoiceAddress->Forename . " " . $account->CustomerInvoiceAddress->Surname . " - Account Reference:[".$account->AccountReference."]. </br>", 2);
                }
            }
        }
    }

    public function GetCustomerByAccountReference($ref)
    {
        $result = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE sage_account_ref = '".$this->db->escape($ref)."';");
        return $result;
    }

    public function SetAddress($customer_id, $data)
    {
        // Does the customer have an address record?
        $address = $this->model_sale_customer->getAddresses($customer_id);

        if (sizeof($address) > 0 )
        {
            $customer = $this->model_sale_customer->getCustomer($customer_id);
            $address_id = $customer['address_id'];
            // Update the default address
            $this->EditAddress($customer_id, $address_id, $data);
            //$address_id = $address[0]['address_id'];
        }
        else
        {
            // Add new
            $address_id = $this->AddAddress($customer_id, $data);
        }

        // Update the customer acccount
        $this->UpdateCustomerAddress($customer_id, $address_id);
    }

    public function AddAddress($customer_id, $data)
    {
        $this->db->query("INSERT INTO " . DB_PREFIX . "address SET firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', company = '" . $this->db->escape($data['company']) . "', address_1 = '" . $this->db->escape($data['address_1']) . "', address_2 = '" . $this->db->escape($data['address_2']) . "', postcode = '" . $this->db->escape($data['postcode']) . "', city = '" . $this->db->escape($data['city']) . "', country_id = '" . (int)$data['country'] . "', zone_id = '" . (int)($data['zone']) . "', customer_id = '" . (int)$customer_id . "'");

        $this->zynk->screenOutput("Inserted Address for ". $data['firstname'] . " " . $data['lastname'] . " - Account ID:[".$customer_id."]. </br>", 2);

        return $this->db->getLastId();
    }

    public function EditAddress($customer_id, $address_id, $data)
    {
        $this->db->query("UPDATE " . DB_PREFIX . "address SET firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', company = '" . $this->db->escape($data['company']) . "', address_1 = '" . $this->db->escape($data['address_1']) . "', address_2 = '" . $this->db->escape($data['address_2']) . "', postcode = '" . $this->db->escape($data['postcode']) . "', city = '" . $this->db->escape($data['city']) . "', country_id = '" . (int)$data['country'] . "', zone_id = '" . (int)($data['zone']) . "' WHERE address_id = '" . (int)$address_id . "'");

        $this->zynk->screenOutput("Updated Address for ". $data['firstname'] . " " . $data['lastname'] . " - Account ID:[".$customer_id."]. </br>", 2);
    }

    public function UpdateCustomerAccountReference($customer_id, $accountReference)
    {
        $result = $this->db->query("UPDATE " . DB_PREFIX . "customer SET sage_account_ref = '" . $this->db->escape($accountReference) . "' WHERE customer_id = '" . (int)$customer_id . "'");
        return $result;
    }
    public function UpdateCustomerGroupID($customer_id, $group_id)
    {
        $result = $this->db->query("UPDATE " . DB_PREFIX . "customer SET customer_group_id = '" . (int)$group_id . "' WHERE customer_id = '" . (int)$customer_id . "'");
        return $result;
    }
    public function UpdateCustomerPassword($customer_id, $password)
    {
        $result = $this->db->query("UPDATE " . DB_PREFIX . "customer SET password = '" . $this->db->escape(md5($data['password'])) . "' WHERE customer_id = '" . (int)$customer_id . "'");
        return $result;
    }
    public function UpdateCustomerAddress($customer_id, $address_id)
    {
        $result = $this->db->query("UPDATE " . DB_PREFIX . "customer SET address_id = '" . (int)$address_id . "' WHERE customer_id = '" . (int)$customer_id . "'");
        return $result;
    }
    // Return country_id from $country
    public function GetCountry($country)
    {
        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "country WHERE name = '" . $this->db->escape($country). "'");
        foreach ($query->rows as $result)
        {
            return $result['country_id'];
        }
    }
    // Return country_id from $country
    public function GetCountryFromIsoCode($country)
    {
        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "country WHERE iso_code_2 = '" . $this->db->escape($country). "'");
        foreach ($query->rows as $result)
        {
            return $result['country_id'];
        }
    }
    // Return country_id from $country
    public function GetIsoCodeFromCountry($country)
    {
        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "country WHERE name = '" . $this->db->escape($country). "'");
        foreach ($query->rows as $result)
        {
            return $result['iso_code_2'];
        }
    }
    // Return zone_id from $zone
    public function GetZone($zone, $country_id)
    {
        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "zone WHERE name = '" . $this->db->escape($zone). "' AND country_id = '" . (int)($country_id). "'");
        foreach ($query->rows as $result)
        {
            return $result['zone_id'];
        }
    }
    // Send email to customer
    public function sendEmail($data)
    {
        //$to         = $this->db->escape($data['email']);
        $to         = "zarar@internetware.co.uk";
        $subject    = "Welcome to " . $this->config->get('config_name');
        $body       = "Dear " . $this->db->escape($data['firstname']) . " " . $this->db->escape($data['lastname']) . "\r\n";
        $body       .= "Welcome to our new website, as a valued customer we have already created your account for you.\r\n";
        $body       .= "\r\n";
        $body       .= "To login please use the credentials provided below:\r\n";
        $body       .= "E-Mail Address : " . $this->db->escape($data['email'])  ."\r\n";
        $body       .= "Password       : " . $this->db->escape($data['password'])  ."\r\n";
        $body       .= "\r\n";
        $body       .= "We do suggest that you change your password as soon as possible.\r\n";
        $body       .= "\r\n";
        $body       .= "Regards,\r\n";
        $body       .= "The " . $this->config->get('config_name') . " team.\r\n";
        //echo("</br>".$body."</br>");
        $headers    = "From: " . $this->config->get('config_name') . "\r\n" ;
        //$headers    = "From: " . $this->config->get('config_url') . "\r\n" ;

        mail($to, $subject, $body, $headers);
    }
    
    // Is customer active/inactive
    public function getStatus($object)
    {
        // Sage 200 doesn't export the status flag, use AccountOnHold
        // Active (1) / Inactive (0)
        if(!empty($object->AccountOnHold) && $object->AccountOnHold != "")
        {
            $status = ($object->AccountOnHold == "true") ? 0 : 1;
        }
        else
        {
            $status = ($object->Status ==  0) ? 1 : 0; //LINE50
        }
        
        return $status;
    }

    private function generateRandomString()
    {
        $length     = 10;
        $characters = "0123456789abcdefghijklmnopqrstuvwxyz";
        $string     = "";    

        for ($p = 0; $p < $length; $p++)
        {
            $string .= $characters[mt_rand(0, strlen($characters))];
        }

    return $string;
}

}
