<?php
/**
 * Получение путей к файлам и директориям
 */

namespace Borisey\Yii2SitemapGenerator;

use Yii;

class Path
{
    /**
     * Метод возвращает путь к директории карт сайта
     *
     * @return string
     */
    public function getSitemapsDir($sitemapPath)
    {
        return Yii::getAlias('@app') . '/web' . $sitemapPath;
    }
}
