<?php
/**
 * Получение путей к файлам и директориям
 */

namespace Borisey\Yii2SitemapGenerator;

use Yii;
use Borisey\Yii2SitemapGenerator\Generator;

class Path
{
    public $sitemapPath;
    public $sitemapName;
    public $sitemapFilePrefix;

    public function __construct(
        string $sitemapPath,
        string $sitemapName
    )
    {
        $this->sitemapPath       = $sitemapPath;
        $this->sitemapName       = $sitemapName;
        $this->sitemapFilePrefix = $this->getFilePrefix();
    }

    /**
     * Метод возвращает префикс файдла
     *
     * @return mixed|string
     */
    private function getFilePrefix()
    {
        $sitemapPathExploded = explode('/', $this->sitemapPath);
        $lastElement = array_key_last($sitemapPathExploded);
        return (!empty($sitemapPathExploded[$lastElement]))
            ? $sitemapPathExploded[$lastElement]
            : $sitemapPathExploded[$lastElement - 1];
    }

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

    /**
     * Метод возвращает путь к текущему файлу карт сайта
     *
     * @return string
     */
    public function getCurrentSitemapPath($fileId)
    {
        return Yii::getAlias('@app') . '/web' . $this->sitemapPath . 'sitemap_' . $this->sitemapFilePrefix . '_' . $this->sitemapName  . '_' . $fileId . '.xml';
    }

    /**
     * Метод возвращает путь к главному файлу карт сайта
     *
     * @return string
     */
    public function getSitemapIndexPath()
    {
        return Yii::getAlias('@app') . '/web' . $this->sitemapPath . '/' . Generator::SITEMAP_INDEX_FILE_TITLE;
    }
}
