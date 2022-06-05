<?php
/**
 * Класс генерирует карты сайта
 */

namespace Borisey\Yii2SitemapGenerator;

use Yii;
use Borisey\Yii2SitemapGenerator\{
    Query,
    Path
};

class Generator
{
    //todo:: Создавать директории, если их нет на сервере
    const SITEMAP_INDEX_FILE_TITLE = 'sitemap_index.xml';
    const ID = '0';
    const FILE_ID = '1';

    public $host;
    public $sitemapPath;
    public $sitemapName;
    public $sitemapFilePrefix;
    public $url;
    public $tableName;
    public $where;
    public $select;
    public $query;

    public function __construct(
        $host = '',
        $sitemapPath = __DIR__  . '/sitemaps/',
        $sitemapName,
        $url,
        $tableName,
        $where
    )
    {
        $this->host        = $host;
        $this->sitemapPath = $sitemapPath;
        $this->sitemapName = $sitemapName;
        $this->url         = $url;
        $this->tableName   = $tableName;
        $this->where       = $where;
        $this->query       = new Query;
        $this->path        = new Path;

        $select = '';
        foreach ($this->url as $key => $value) {
            $select .= ($value != "" ? '`' . $value . '`' : '');
        }
        $this->select = $select;

        $sitemapPathExploded = explode('/', $this->sitemapPath);
        $lastElement = array_key_last($sitemapPathExploded);
        $this->sitemapFilePrefix = (!empty($sitemapPathExploded[$lastElement]))
            ? $sitemapPathExploded[$lastElement]
            : $sitemapPathExploded[$lastElement - 1];
    }

    public function generate()
    {
        $this->createSitemaps();
    }

    /**
     * Метод создает главный файл карт сайта
     */
    public function createSitemapIndex()
    {
        // Удаляем главный файл карт сайта ('sitemap_index.xml')
        $this->delSitemapIndex();

        $sitemapIndexPath = Yii::getAlias('@app') . '/web' . $this->sitemapPath . self::SITEMAP_INDEX_FILE_TITLE;

        // Сохраняем в главном файле карт сайта начальную строку
        $this->putIndexSitemapStart($sitemapIndexPath);

        $scanResults = scandir($this->path->getSitemapsDir($this->sitemapPath));

        foreach ($scanResults as $item) {
            $filePath = Yii::getAlias('@app') . '/web' . $this->sitemapPath . $item;

            if (is_file($filePath) && $item!= self::SITEMAP_INDEX_FILE_TITLE) {
                // Добавляем ссылки на карты сайта
                $sitemapLocPath = "<sitemap><loc>" . $this->host . $this->sitemapPath . $item . "</loc></sitemap>\n";
                file_put_contents($sitemapIndexPath, $sitemapLocPath, FILE_APPEND | LOCK_EX);
            }
        }

        // Сохраняем в главном файле карт сайта последнюю строку
        $this->putIndexSitemapEnd($sitemapIndexPath);

        echo 'Карта сайта "' . $sitemapIndexPath . '" успешно создана' . PHP_EOL;
    }

    /**
     * Метод сохраняет в главном файле карт сайта начальную строку
     *
     * @param $sitemapIndexPath
     */
    private function putIndexSitemapStart($sitemapIndexPath)
    {
        $startSitemaps = "<sitemapindex xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>\n";

        // Создаем файл со ссылками на все карты сайта и загружаем туда заголовок из переменной $start
        file_put_contents($sitemapIndexPath, $startSitemaps, FILE_APPEND | LOCK_EX);
    }

    /**
     * Метод сохраняет в главном файле карт сайта последнюю строку
     *
     * @param $sitemapIndexPath
     */
    private function putIndexSitemapEnd($sitemapIndexPath)
    {
        $endSitemaps = '</sitemapindex>';
        file_put_contents($sitemapIndexPath, $endSitemaps, FILE_APPEND | LOCK_EX);
    }

    /**
     * Метод создает карты сайта переданных словарей
     *
     * @param $activeDicts
     */
    private function createSitemaps()
    {
        $count = $this->query->getCount($this->tableName);
        $this->createSitemapsIndex(self::ID, $count);
    }

    /**
     * Создаем карту сайта конкретного словаря
     *
     * @param $data
     * @param $fileId
     */
    private function createSitemap($data, $fileId) {
        $start = "<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9' xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:schemaLocation='http://www.sitemaps.org/schemas/sitemap/0.9http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd'>\n";
        $sitemapPath = $this->getCurrentSitemapPath($fileId);
        file_put_contents($sitemapPath, $start, FILE_APPEND | LOCK_EX);

        foreach ($data as $item) {
            $urlLink = $this->getUrlLink($item);

            $urlLoc = "<url><loc>" . $urlLink . "</loc></url>\n";
            file_put_contents($sitemapPath, $urlLoc, FILE_APPEND | LOCK_EX);
        }

        $end = '</urlset>';
        file_put_contents($sitemapPath, $end, FILE_APPEND | LOCK_EX);

        echo 'Карта сайта "' . $sitemapPath . '" успешно создана' . PHP_EOL;
    }

    /**
     * Создаем файл со списком всех карт сайта
     *
     * @param $id
     * @param $count
     */
    private function createSitemapsIndex($id, $count) {
        $fileId = self::FILE_ID;

        // Запускаем цикл создания карт сайта.
        // Цикл работает до тех пор пока не переобойдет все записи в базе.
        // Количество записей в переменной $count
        while ($id <= $count)
        {
            // Удаляем существующий файл карты сайта
            $this->delExistsFiles($fileId);

            $data = $this->query->getData($this->tableName, $id, $this->select, $this->where);

            $this->createSitemap($data, $fileId);

            // Увеличиваем счетчики id и file
            $id = $id + 50000;
            $fileId++;
        }
    }

    /**
     * Метод удаляет переданный файл карты сайта
     *
     * @param $fileId
     * @return mixed
     */
    private function delExistsFiles($fileId) {
        $filePath = $this->getCurrentSitemapPath($fileId);

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * Метод удаляет главный файл карт сайта ('sitemap_index.xml')
     */
    private function delSitemapIndex()
    {
        $sitemapIndexPath = $this->getSitemapIndexPath();
        if (file_exists($sitemapIndexPath)) {
            unlink($sitemapIndexPath);
        }
    }


    /**
     * Метод возвращает путь к текущему файлу карт сайта
     *
     * @return string
     */
    private function getCurrentSitemapPath($fileId)
    {
        return Yii::getAlias('@app') . '/web' . $this->sitemapPath . 'sitemap_' . $this->sitemapFilePrefix . '_' . $this->sitemapName  . '_' . $fileId . '.xml';
    }

    /**
     * Метод возвращает путь к главному файлу карт сайта
     *
     * @return string
     */
    private function getSitemapIndexPath()
    {
        return Yii::getAlias('@app') . '/web' . $this->sitemapPath . '/' . self::SITEMAP_INDEX_FILE_TITLE;
    }

    /**
     * Метод возвращает ссылку на элемент
     *
     * @param $item
     * @return string
     */
    private function getUrlLink($item)
    {
        $url = '';
        foreach ($this->url as $key => $value) {
            $url .= $key . ($value != "" ? $item[$value] : '');
        }

        return $this->host . $url;
    }


}
