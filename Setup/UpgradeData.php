<?php
namespace Deco\Shipping\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeData implements UpgradeDataInterface
{
    private $eavSetupFactory;

    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            $installer = $setup;
            $installer->startSetup();
            if (!$installer->tableExists('deco_shipping')) {
                $tableName = $installer->getTable('deco_shipping');
                $table = $installer->getConnection()
                    ->newTable($tableName)
                    ->addColumn(
                        'cep_range_id',
                        Table::TYPE_INTEGER,
                        10,
                        [
                            'identity' => true,
                            'unsigned' => true,
                            'nullable' => false,
                            'primary' => true
                        ],
                        'Cep Range Id'
                    )
                    ->addColumn(
                        'name',
                        Table::TYPE_TEXT,
                        255,
                        [
                            'nullable' => true,
                            'default' => null
                        ],
                        'name'
                    )
                    ->addColumn(
                        'cep_inicial',
                        Table::TYPE_TEXT,
                        255,
                        [
                            'nullable' => true,
                            'default' => null
                        ],
                        'CEP Inicial'
                    )
                    ->addColumn(
                        'cep_final',
                        Table::TYPE_TEXT,
                        255,
                        [
                            'nullable' => true,
                            'default' => null
                        ],
                        'CEP Final'
                    )
                    ->addColumn(
                        'price',
                        Table::TYPE_TEXT,
                        255,
                        [
                            'nullable' => true,
                            'default' => null
                        ],
                        'price'
                    )
                    ->addColumn(
                        'status',
                        Table::TYPE_SMALLINT,
                        null,
                        [
                            'nullable' => true,
                            'default' => null
                        ],
                        'Status'
                    )
                    ->setComment('Deco Shipping')
                    ->setOption('type', 'InnoDB')
                    ->setOption('charset', 'utf8');
                    $installer->getConnection()->createTable($table);
                }
            $installer->endSetup();
        }
    }
}