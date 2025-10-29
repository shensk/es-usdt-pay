<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSystemConfigsTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $table = $this->table('system_configs', [
            'id' => true,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '系统配置表'
        ]);
        
        $table->addColumn('key', 'string', [
                'limit' => 100,
                'null' => false,
                'comment' => '配置键'
            ])
            ->addColumn('value', 'text', [
                'null' => true,
                'comment' => '配置值'
            ])
            ->addColumn('type', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'string',
                'comment' => '数据类型：string,int,float,bool,json'
            ])
            ->addColumn('group', 'string', [
                'limit' => 50,
                'null' => false,
                'default' => 'default',
                'comment' => '配置分组'
            ])
            ->addColumn('title', 'string', [
                'limit' => 200,
                'null' => true,
                'comment' => '配置标题'
            ])
            ->addColumn('description', 'text', [
                'null' => true,
                'comment' => '配置描述'
            ])
            ->addColumn('sort', 'integer', [
                'null' => false,
                'default' => 0,
                'comment' => '排序'
            ])
            ->addColumn('created_at', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => '创建时间'
            ])
            ->addColumn('updated_at', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'comment' => '更新时间'
            ])
            ->addIndex(['key'], ['unique' => true, 'name' => 'uk_key'])
            ->addIndex(['group'], ['name' => 'idx_group'])
            ->create();
    }
}
