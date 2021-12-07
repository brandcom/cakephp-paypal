<?php

use Migrations\AbstractMigration;

class Paypals extends AbstractMigration
{

    public $autoId = false;

    public function up()
    {
        $this->table('paypals')
            ->removeIndexByName('order_id')
            ->update();

        $this->table('paypals')
            ->removeColumn('order_id')
            ->update();

        $this->table('paypals')
            ->addColumn('fk_id', 'integer', [
                'after' => 'id',
                'default' => null,
                'length' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('fk_model', 'string', [
                'after' => 'fk_id',
                'default' => '',
                'length' => 255,
                'null' => false,
            ])
            ->addIndex(
                [
                    'fk_id',
                ],
                [
                    'name' => 'fk_id',
                ]
            )
            ->update();
    }
}
