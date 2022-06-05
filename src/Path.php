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
     * @param $sitemapPath
     * @return string
     */
    public function getSitemapsDir($sitemapPath)
    {
        return Yii::getAlias('@app') . '/web' . $sitemapPath;
    }

    /**
     * Метод возвращает ссылку на элемент
     *
     * @param $host
     * @param $url
     * @param $item
     * @return string
     */
    public function getUrlLink($host, $url, $item)
    {
        $urlLink = '';
        foreach ($url as $key => $value) {
            $urlLink .= $key . ($value != "" ? $item[$value] : '');
        }

        return $host . $urlLink;
    }
}
