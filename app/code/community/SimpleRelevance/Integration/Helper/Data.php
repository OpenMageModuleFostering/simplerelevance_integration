<?php

/**
 * SimpleRelevance Integration main helper
 *
 * @category   SimpleRelevance
 * @package    SimpleRelevance_Integration
 */
class SimpleRelevance_Integration_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Get module User-Agent to use on API requests
     *
     * @return string
     */
    public function getUserAgent()
    {
        $modules = Mage::getConfig()->getNode('modules')->children();
        $modulesArray = (array)$modules;

        $mageType = (array_key_exists('Enterprise_Enterprise', $modulesArray))? 'EE' : 'CE' ;
        $v        = (string)Mage::getConfig()->getNode('modules/SimpleRelevance_Integration/version');

        return (string)'SimpleRelevance' . $v . '/Mage'. $mageType . Mage::getVersion();
    }

    /**
     * Retrieve configuration data from database
     *
     * @param string $value Value to get
     * @return mixed
     */
    public function config($value)
    {
        return Mage::getStoreConfig("simple_relevance/general/$value");
    }

    /**
     * Check module status
     *
     * @return bool
     */
    public function enabled()
    {
        return (bool)((int)$this->config('active') === 1);
    }

    /**
     * Return produt dict params to send to the API
     *
     * @param Mage_Catalog_Model_Product
     * @return array
     */
    public function getProductDict(Mage_Catalog_Model_Product $product)
    {
        $smallImageUrl = '';
        if($product->getSmallImage()) {
            try {
                $smallImageUrl = Mage::helper('catalog/image')->init($product, 'small_image')->resize(75);
            } catch(Exception $e) {
                Mage::logException($e);
            }
        }

        $baseImageUrl = '';
        if ($product->getImage()) {
            try {
                $baseImageUrl = Mage::helper('catalog/image')->init($product, 'image');
            } catch(Exception $e) {
                Mage::logException($e);
            }
        }

        // Category names
        $categoryIds = $product->getCategoryIds();
        $categories  = array();
        if (is_array($categoryIds) && !empty($categoryIds)) {
            foreach($categoryIds as $_catId) {

                $cat = Mage::getModel('catalog/category')->load($_catId);
                if($cat->getId()){
                    $categories []= $cat->getName();
                }
            }
        }

        $dict = array(
            'item_url'        => $product->getUrlPath(),
            'price'           => $product->getPrice(),
            'sku'             => $product->getSku(),
            'categories'      => $categories,
            'image_url_small' => (string)$smallImageUrl,
            'image_url'       => (string)$baseImageUrl,
            'description'     => $product->getDescription(),
        );
        return $dict;
    }
}
