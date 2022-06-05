<?php
/**
 * Запросы к базе данных
 */

namespace Borisey\Yii2SitemapGenerator;

use Yii;

class Query
{
    /**
     * Получаем общее количество записей в базе
     *
     * @return mixed
     * @throws \yii\db\Exception
     */
    public function getCount($tableName) {
        $count = Yii::$app->db->createCommand("SELECT COUNT(*) FROM {$tableName}")
            ->queryOne();

        return $count['COUNT(*)'];
    }

    /**
     * Метод получает 50000 строк из переданной таблицы
     *
     * @param $id
     * @return mixed
     * @throws \yii\db\Exception
     */
    public function getData($tableName, $id, $select, $where) {
        return Yii::$app->db->createCommand("SELECT {$select} FROM {$tableName} WHERE `id` > {$id} {$where} ORDER BY `id` ASC LIMIT 50000")
            ->queryAll();
    }
}
