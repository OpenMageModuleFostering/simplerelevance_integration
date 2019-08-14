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
            $api_arr = array(Mage::helper('simple_relevance')->config('apikey'), Mage::helper('simple_relevance')->config('sitename'));
            $api = Mage::getModel('simple_relevance/api', $api_arr);
            $customers = $this->getRequest()->getPost('customer', array());
            $customerArray = array();

            foreach ($customers as $customerId) {
                $customer = Mage::getModel('customer/customer')->load($customerId);

                $customerData = array(
                    'email' => $customer->getData('email'),
                    'user_id' => $customer->getId(),
                );

                $customerArray[] = $customerData; // append
            }

            $result = $api->postUsers($customerArray, true);

            if ($api->errorCode) {
                $this->_getSession()->addError($this->__('Error uploading customer(s) to SimpleRelevance. Email support@simplerelevance.com to let us know.'));
            } else {
                $this->_getSession()->addSuccess($this->__('Customer(s) have been uploaded to SimpleRelevance.'));
            }
        }

        catch (Exception $e) {
            try {
                $api->_log($e->getMessage());
            }
            catch (Exception $e) {
                // do nothing
            }
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
            $api_arr = array(Mage::helper('simple_relevance')->config('apikey'), Mage::helper('simple_relevance')->config('sitename'));
            $api = Mage::getModel('simple_relevance/api', $api_arr);
            $products = $this->getRequest()->getPost('product', array());
            $productArray = array();

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
                    'item_name' => $product->getData('name'),
                    'item_id'   => $product->getId(),
                    'data_dict' => $dict,
                );

                $productArray[] = $data;
            }

            $result = $api->postItems($productArray, true);

            if ($api->errorCode) {
                $this->_getSession()->addError($this->__('Error uploading item(s) to SimpleRelevance. Email support@simplerelevance.com to let us know.'));
            } else {
                $this->_getSession()->addSuccess($this->__('Item(s) have been uploaded to SimpleRelevance.'));
            }
        }

        catch (Exception $e) {
            try {
                $api->_log($e->getMessage());
                $this->_getSession()->addError($this->__('No item(s) were uploaded to SimpleRelevance.'));
            }
            catch (Exception $e) {
                // do nothing
            }
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
            $api_arr = array(Mage::helper('simple_relevance')->config('apikey'), Mage::helper('simple_relevance')->config('sitename'));
            $api = Mage::getModel('simple_relevance/api', $api_arr);
            $orders = $this->getRequest()->getPost('order_ids', array());
            $orderArray = array();

            foreach ($orders as $orderId) {
                $order = Mage::getModel('sales/order')->load($orderId);

                if (!$order->getId()) {
                    continue;
                }

                $purchase = Mage::getModel('simple_relevance/purchase', $order);
                $postData = $purchase->getPostData();

                foreach($postData['items'] as $p) {
                    $orderArray[] = $p;
                }
            }
            $result = $api->postPurchases($orderArray, true);

            if ($api->errorCode) {
                $this->_getSession()->addError($this->__('Error uploading purchases(s) to SimpleRelevance. Email support@simplerelevance.com to let us know.'));
            } else {
                $this->_getSession()->addSuccess($this->__('Purchases(s) have been uploaded to SimpleRelevance.'));
            }
        }

        catch (Exception $e) {
            try {
                $api->_log($e->getMessage());
            }
            catch (Exception $e) {
                // do nothing
            }
            $this->_redirect('adminhtml/sales_order/index');
        }

        $this->_redirect('adminhtml/sales_order/index');
    }
}

?>
