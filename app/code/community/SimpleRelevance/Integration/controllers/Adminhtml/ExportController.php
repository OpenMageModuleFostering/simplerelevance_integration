<?php

/**
 * Mass export actions to push data to SimpleRelevance API
 *
 * @category   SimpleRelevance
 * @package    SimpleRelevance_Integration
 */
class SimpleRelevance_Integration_Adminhtml_ExportController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Mass send customers to SimpleRelevance
     *
     */
    public function massCustomerAction()
    {
        try {
            $customers = $this->getRequest()->getPost('customer', array());
            $sent      = 0;
            $notSent   = 0;

            $api_arr = array(Mage::helper('simple_relevance')->config('apikey'), Mage::helper('simple_relevance')->config('sitename'));
            $api = Mage::getModel('simple_relevance/api', $api_arr);

            foreach ($customers as $customerId) {
                $customer = Mage::getModel('customer/customer')->load($customerId);

                $customerData = array(
                    'email' => $customer->getEmail(),
                    'user_id' => $customer->getId(),
                );

                $result = $api->postUsers($customerData);

                if (!$api->errorCode) {
                    $sent++;
                } else {
                    $this->_getSession()->addError($this->__('Error on customer #%s, - %s -', $customer->getId(), $api->errorMessage));
                    $notSent++;
                }
            }

            if ($notSent) {
                if ($sent) {
                    $this->_getSession()->addError($this->__('%s customer(s) were not sent.', $notSent));
                } else {
                    $this->_getSession()->addError($this->__('No customer(s) were sent successfully.'));
                }
            }

            if ($sent) {
                $this->_getSession()->addSuccess($this->__('%s customer(s) have been sent successfully.', $sent));
            }
        }

        catch (Exception $e) {
            $api->_log($e->getMessage());
            $this->_redirect('adminhtml/customer/index');
        }

        $this->_redirect('adminhtml/customer/index');
    }

    /**
     * Mass send Catalog/Products to SimpleRelevance
     *
     */
    public function massInventoryAction()
    {
        try {
            $products = $this->getRequest()->getPost('product', array());
            $sent     = 0;
            $notSent  = 0;

            $api_arr = array(Mage::helper('simple_relevance')->config('apikey'), Mage::helper('simple_relevance')->config('sitename'));
            $api = Mage::getModel('simple_relevance/api', $api_arr);

            foreach ($products as $productId) {
                $product = Mage::getModel('catalog/product')->load($productId);

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

                $result = $api->postItems($data);

                if (!$api->errorCode) {
                    $sent++;
                } else {
                    $this->_getSession()->addError($this->__('Error on items #%s, - %s -', $product->getId(), $api->errorMessage));
                    $notSent++;
                }
            }
            if ($notSent) {
                if ($sent) {
                    $this->_getSession()->addError($this->__('%s item(s) were not sent.', $notSent));
                } else {
                    $this->_getSession()->addError($this->__('No item(s) were sent successfully.'));
                }
            }

            if ($sent) {
                $this->_getSession()->addSuccess($this->__('%s item(s) have been sent successfully.', $sent));
            }
        }

        catch (Exception $e) {
            $api->_log($e->getMessage());
            $this->_redirect('adminhtml/catalog_product/index');
        }

        $this->_redirect('adminhtml/catalog_product/index');
    }

    /**
     * Mass send Order items (user actions) to SimpleRelevance
     *
     */
    public function massOrderAction()
    {
        try {
            $orders  = $this->getRequest()->getPost('order_ids', array());
            $sent    = 0;
            $notSent = 0;

            $api_arr = array(Mage::helper('simple_relevance')->config('apikey'), Mage::helper('simple_relevance')->config('sitename'));
            $api = Mage::getModel('simple_relevance/api', $api_arr);

            foreach ($orders as $orderId) {
                $order = Mage::getModel('sales/order')->load($orderId);

                if (!$order->getId()) {
                    continue;
                }

                $purchase = Mage::getModel('simple_relevance/purchase', $order);
                $postData = $purchase->getPostData();

                foreach($postData['items'] as $p) {
                    $result = $api->postPurchases($p);
                }

                if (!$api->errorCode) {
                    $sent++;
                } else {
                    $this->_getSession()->addError($this->__('Error on order #%s, - %s -', $order->getId(), $api->errorMessage));
                    $notSent++;
                }
            }

            if ($notSent) {
                if ($sent) {
                    $this->_getSession()->addError($this->__('%s order(s) were not sent.', $notSent));
                } else {
                    $this->_getSession()->addError($this->__('No order(s) were sent successfully.'));
                }
            }

            if ($sent) {
                $this->_getSession()->addSuccess($this->__('%s order(s) have been sent successfully.', $sent));
            }
        }

        catch (Exception $e) {
            $api->_log($e->getMessage());
            $this->_redirect('adminhtml/sales_order/index');
        }

        $this->_redirect('adminhtml/sales_order/index');
    }
}

?>
