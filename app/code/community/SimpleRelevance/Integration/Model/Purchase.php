<?php

/**
 * SimpleRelevance Integration Purchase model
 *
 * @category   SimpleRelevance
 * @package    SimpleRelevance_Integration
 */
class SimpleRelevance_Integration_Model_Purchase
{
    protected $_order = null;

    function __construct(Mage_Sales_Model_Order $order)
    {
        $this->_order = $order;
    }

    /**
     * Return post data array to be sent to SimpleRelevance API
     *
     * @return array
     */
    public function getPostData()
    {
        $data = array();
        $data['items'] = $this->getItems();
        return $data;
    }

    /**
     * Get items purchased on this order
     *
     * @return array
     */
    public function getItems()
    {
        $items = array();
        $date = Mage::getModel('core/date')->date('m/d/Y H:i:s', $this->_order->getCreatedAt());

        foreach ($this->_order->getItemsCollection() as $item) {
            $items [] = array(
                'price'       => (float)$item->getRowTotalInclTax(),
                'item_id'     => $item->getProductId(),
                'email'       => $this->_order->getCustomerEmail(),
                'timestamp'   => $date,
                'action_type' => 1
            );
        }

        return $items;
    }
}

?>
