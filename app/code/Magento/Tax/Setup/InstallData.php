<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tax\Setup;

use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    /**
     * Tax setup factory
     *
     * @var TaxSetupFactory
     */
    private $taxSetupFactory;

    /**
     * Region collection factory.
     *
     * @var \Magento\Directory\Model\ResourceModel\Region\CollectionFactory
     */
    private $regionCollectionFactory;

    /**
     * Region collection.
     *
     * @var \Magento\Directory\Model\ResourceModel\Region\Collection
     */
    private $regionCollection;

    /**
     * Init
     *
     * @param TaxSetupFactory $taxSetupFactory
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        TaxSetupFactory $taxSetupFactory,
        CollectionFactory $collectionFactory
    ) {
        $this->taxSetupFactory = $taxSetupFactory;
        $this->regionCollectionFactory = $collectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var TaxSetup $taxSetup */
        $taxSetup = $this->taxSetupFactory->create(['resourceName' => 'tax_setup', 'setup' => $setup]);

        /**
         * Add tax_class_id attribute to the 'eav_attribute' table
         */
        $taxSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'tax_class_id',
            [
                'group' => 'Product Details',
                'sort_order' => 40,
                'type' => 'int',
                'backend' => '',
                'frontend' => '',
                'label' => 'Tax Class',
                'input' => 'select',
                'class' => '',
                'source' => \Magento\Tax\Model\TaxClass\Source\Product::class,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'default' => '2',
                'searchable' => true,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'visible_in_advanced_search' => false,
                'used_in_product_listing' => true,
                'unique' => false,
                'apply_to' => implode(',', $taxSetup->getTaxableItems()),
                'is_used_in_grid' => true,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => true,
            ]
        );

        /**
         * install tax classes
         */
        $data = [
            [
                'class_id' => 2,
                'class_name' => 'Taxable Goods',
                'class_type' => \Magento\Tax\Model\ClassModel::TAX_CLASS_TYPE_PRODUCT,
            ],
            [
                'class_id' => 3,
                'class_name' => 'Retail Customer',
                'class_type' => \Magento\Tax\Model\ClassModel::TAX_CLASS_TYPE_CUSTOMER
            ],
        ];
        foreach ($data as $row) {
            $setup->getConnection()->insertForce($setup->getTable('tax_class'), $row);
        }

        /**
         * install tax calculation rates
         */
        $data = [
            [
                'tax_calculation_rate_id' => 1,
                'tax_country_id' => 'US',
                'tax_region_id' => $this->getRegionId('CA'),
                'tax_postcode' => '*',
                'code' => 'US-CA-*-Rate 1',
                'rate' => '8.2500',
            ],
            [
                'tax_calculation_rate_id' => 2,
                'tax_country_id' => 'US',
                'tax_region_id' => $this->getRegionId('NY'),
                'tax_postcode' => '*',
                'code' => 'US-NY-*-Rate 1',
                'rate' => '8.3750'
            ],
        ];

        foreach ($data as $row) {
            $setup->getConnection()->insertForce($setup->getTable('tax_calculation_rate'), $row);
        }
    }

    /**
     * Return region id by code.
     *
     * @param string $regionCode
     * @return string
     */
    private function getRegionId($regionCode)
    {
        if ($this->regionCollection === null) {
            /** @var \Magento\Directory\Model\ResourceModel\Region\Collection $regionCollection */
            $this->regionCollection = $this->regionCollectionFactory->create();
            $this->regionCollection->addCountryFilter('US')
                ->addRegionCodeOrNameFilter(['CA', 'NY']);
        }

        $regionId = '';

        /** @var \Magento\Directory\Model\Region $item */
        $item = $this->regionCollection->getItemByColumnValue('code', $regionCode);
        if ($item) {
            $regionId = $item->getId();
        }

        return $regionId;
    }
}
