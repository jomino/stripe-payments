<?php

use Phinx\Migration\AbstractMigration;

class CreateStripeUserTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    addCustomColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Any other destructive changes will result in an error when trying to
     * rollback the migration.
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {

        $table = $this->table('stripe_users');
        $table->addColumn('id', 'integer')
            ->addColumn('created_at', 'datetime')
            ->addColumn('updated_at', 'datetime')
            ->addColumn('active', 'smallinteger')
            ->addColumn('uuid', 'string', ['length' => 256])
            ->addColumn('name', 'string', ['length' => 128])
            ->addColumn('email', 'string', ['length' => 128])
            ->addColumn('pkey', 'string', ['length' => 256])
            ->addColumn('skey', 'string', ['length' => 256])
            ->create();

    }
}
