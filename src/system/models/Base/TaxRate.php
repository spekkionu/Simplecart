<?php

/**
 * Base_TaxRate
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property string $state
 * @property decimal $rate
 * 
 * @package    SimpleCart
 * @subpackage Models
 * @author     Jonathan Bernardi <spekkionu@gmail.com>
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
abstract class Base_TaxRate extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('tax_rate');
        $this->hasColumn('state', 'string', 2, array(
             'type' => 'string',
             'fixed' => 1,
             'primary' => true,
             'usstate' => true,
             'length' => '2',
             ));
        $this->hasColumn('rate', 'decimal', 8, array(
             'type' => 'decimal',
             'length' => 8,
             'scale' => 3,
             ));
    }

    public function setUp()
    {
        parent::setUp();
        
    }
}