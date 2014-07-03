<?php
class ModelSaleShipping extends Model
{

    public function getShippingNet($order_id)
    {
        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "'" . " AND sort_order = '" . (int)$this->config->get('shipping_sort_order') . "'");

        return $query->row;
    }

    // @TODO: Doesn't take into account discounts...
    public function getShippingVat($order_id, $item_vat)
    {
        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "'");

        foreach ($query->rows as $result)
        {
            $shipping_data[$result['sort_order']] = array
            (
                'value' => $result['value'],
                'order' => $result['sort_order']
            );
        }

        /*
        echo "SUB TOTAL : " . $shipping_description_data[(int)$this->config->get('sub_total_sort_order')]['value'] . "</br>";
        echo "SHIP NET  : " . $shipping_description_data[(int)$this->config->get('shipping_sort_order')]['value'] . "</br>";
        echo "TAX       : " . $shipping_description_data[(int)$this->config->get('tax_sort_order')]['value'] . "</br>";
        echo "TOTAL     : " . $shipping_description_data[(int)$this->config->get('total_sort_order')]['value'] . "</br>";
        */

        // If there is no VAT on the order then the VAT component will not exist
        if (isset($shipping_data[(int)$this->config->get('tax_sort_order')]))
        {
            $carriage_vat = $shipping_data[(int)$this->config->get('tax_sort_order')]['value'] - $item_vat;
        }
        else
        {
            $carriage_vat = 0;
        }

        return round($carriage_vat, 2);
    }

    public function getShippings($data = array())
    {
        $sql = "SELECT c.shipping_id, cd.name, c.code, c.discount, c.date_start, c.date_end, c.status FROM " . DB_PREFIX . "shipping c LEFT JOIN " . DB_PREFIX . "shipping_description cd ON (c.shipping_id = cd.shipping_id) WHERE cd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        $sort_data = array
        (
            'cd.name',
            'c.code',
            'c.discount',
            'c.date_start',
            'c.date_end',
            'c.status'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data))
        {
            $sql .= " ORDER BY " . $data['sort'];
        }
        else
        {
            $sql .= " ORDER BY cd.name";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC'))
        {
            $sql .= " DESC";
        }
        else
        {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit']))
        {
            if ($data['start'] < 0)
            {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1)
            {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getShippingDescriptions($shipping_id)
    {
        $shipping_description_data = array();

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "shipping_description WHERE shipping_id = '" . (int)$shipping_id . "'");

        foreach ($query->rows as $result)
        {
            $shipping_description_data[$result['language_id']] = array
            (
                'name'        => $result['name'],
                'description' => $result['description']
            );
        }

        return $shipping_description_data;
    }

    public function getTotalShippings()
    {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "order_total WHERE sort_order = '" . (int)$this->config->get('shipping_sort_order') . "'");

        return $query->row['total'];
    }
}
?>