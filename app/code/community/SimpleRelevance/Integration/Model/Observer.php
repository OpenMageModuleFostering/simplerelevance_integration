<?php

/**
 * SimpleRelevance Integration Events observer model
 *
 * @category   SimpleRelevance
 * @package    SimpleRelevance_Integration
 */
class SimpleRelevance_Integration_Model_Observer
{
    protected $_block;

    /**
     * Add option to MassAction block in adminhtml
     *
     */
    public function massaction($observer)
    {
        $block  = $observer->getEvent()->getBlock();
        $action = $block->getRequest()->getControllerName();

        if (!Mage::helper('simple_relevance')->enabled()) {
            return $this;
        }

        if (get_class($block) == 'Mage_Adminhtml_Block_Widget_Grid_Massaction') {
            $this->_block = $block;

            switch ($action) {
                case 'customer':
                    $this->_addMassItem('massCustomer');
                    break;
                case 'catalog_product':
                    $this->_addMassItem('massInventory');
                    break;
                case 'sales_order':
                    $this->_addMassItem('massOrder');
                    break;
            }
        }

        return $observer;
    }

    /**
     * Add item to block
     *
     * @param string $action
     * @return SimpleRelevance_Integration_Model_Observer
     */
    protected function _addMassItem($action)
    {
        $this->_block->addItem('simple_relevance_send', array(
            'label'    => Mage::helper('simple_relevance')->__('Send to SimpleRelevance'),
            'url'      => Mage::helper('adminhtml')->getUrl("simple_relevance/adminhtml_export/{$action}")
        ));
    }

    /**
     * Send new order to SimpleRelevance API
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function pushPurchase(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('simple_relevance')->enabled()) {
            return $this;
        }

        try {

            $orderId = (int)(Mage::getSingleton('checkout/type_onepage')->getCheckout()->getLastOrderId());

            if ($orderId) {
                $order = Mage::getModel('sales/order')->load($orderId);
                
                if ($order->getId()) {
                    $purchase = Mage::getModel('simple_relevance/purchase', $order);
                    $postData = $purchase->getPostData();

                    $api_arr = array(Mage::helper('simple_relevance')->config('apikey'), Mage::helper('simple_relevance')->config('sitename'));
                    $api = Mage::getModel('simple_relevance/api', $api_arr);

                    foreach ($postData['items'] as $p) {
                        $api->postPurchases($p);
                    }
                }
            }

            return $this;
        }

        catch (Exception $e) {
            $api->_log($e->getMessage());
            return $this;
        }
    }

    /**
     * Automatically send customer when creating one.
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function pushCustomer(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('simple_relevance')->enabled()) {
            return $this;
        }

        try {
            $customer = $observer->getEvent()->getCustomer();

            $api_arr = array(Mage::helper('simple_relevance')->config('apikey'), Mage::helper('simple_relevance')->config('sitename'));
            $api = Mage::getModel('simple_relevance/api', $api_arr);

            $customerData = array(
                'email' => $customer->getEmail(),
                'user_id' => $customer->getId(),
            );

            $api->postUsers($customerData);
        }

        catch (Exception $e) {
            $api->_log($e->getMessage());
            return $this;
        }
    }

    /**
     * Automatically send Catalog-Product when creating or modifying it.
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function pushItem(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('simple_relevance')->enabled()) {
                return $this;
        }
        
        try {
            $product = $observer->getEvent()->getProduct();

            $api_arr = array(Mage::helper('simple_relevance')->config('apikey'), Mage::helper('simple_relevance')->config('sitename'));
            $api = Mage::getModel('simple_relevance/api', $api_arr);
            $dict = Mage::helper('simple_relevance')->getProductDict($product);

            // categories should be a string of ';'-separated values
            $category_str = "";

            foreach ($dict['categories'] as $category) {
                $category_str = $category_str . $category . ';';
            }

            $dict['categories'] = $category_str;

            $data = array(
                'item_name' => $product->getName(),
                'item_id'   => $product->getId(),
                'data_dict' => $dict,
            );

            $api->postItems($data);
        }
        
        catch (Exception $e) {
            $api->_log($e->getMessage());
            return $this;
        }
    }
}

?>
