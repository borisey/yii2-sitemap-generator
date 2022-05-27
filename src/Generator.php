<?php
/**
 * Класс генерирует карты сайта
 */

namespace Borisey\Yii2SitemapGenerator;

use Yii;

class Generator
{
    //todo:: Создавать директории, если их нет на сервере
    const SITEMAP_INDEX_FILE_TITLE = 'sitemap_index.xml';
    const ID = '0';
    const FILE_ID = '1';

    public $host;
    public $sitemapPath;
    public $dir;
    public $route;
    public $prefix;
    public $tableName;

    public function __construct(
        $host = 'http://1slovar.ru',
        $sitemapPath = 'web/sitemaps',
        $dir = 'enc',
        $route,
        $prefix,
        $tableName
    )
    {
        $this->host        = $host . '/';
        $this->sitemapPath = $sitemapPath . '/';
        $this->dir         = $dir;
        $this->route       = $route;
        $this->prefix      = $prefix;
        $this->tableName   = $tableName;
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

        $sitemapIndexPath = $this->sitemapPath . $this->dir . '/' . self::SITEMAP_INDEX_FILE_TITLE;

        // Сохраняем в главном файле карт сайта начальную строку
        $this->putIndexSitemapStart($sitemapIndexPath);

        $scanResults = scandir($this->sitemapPath . $this->dir);

        foreach ($scanResults as $item) {
            $filePath = $this->sitemapPath . $this->dir . '/' . $item;

            if (is_file($filePath)) {
                // Добавляем ссылки на карты сайта
                $sitemapLocPath = "<sitemap><loc>" . $this->host . $filePath . "</loc></sitemap>\n";
                file_put_contents($sitemapIndexPath, $sitemapLocPath, FILE_APPEND | LOCK_EX);
            }
        }

        // Сохраняем в главном файле карт сайта последнюю строку
        $this->putIndexSitemapEnd($sitemapIndexPath);

        echo 'Файл карт сайта всех словарей успешно создан' . PHP_EOL;
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
        $count = $this->getCount();
        $this->createSitemapsIndex(self::ID, $count);
    }

    /**
     * Получаем общее количество записей в базе
     * @return mixed
     * @throws \yii\db\Exception
     */
    private function getCount() {
        $count = Yii::$app->db->createCommand("SELECT COUNT(*) FROM {$this->tableName}")
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
    private function getData($id) {
        return Yii::$app->db->createCommand("SELECT `id` FROM {$this->tableName} WHERE `id` > {$id} ORDER BY `id` ASC LIMIT 50000")
            ->queryAll();
    }

    /**
     * Создаем карту сайта конкретного словаря
     *
     * @param $data
     * @param $fileId
     */
    private function createSitemap($data, $fileId) {
        $start = "<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9' xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:schemaLocation='http://www.sitemaps.org/schemas/sitemap/0.9http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd'>\n";
        $sitemapPath = $this->sitemapPath . $this->dir . '/' . $this->route . '_' . $fileId . '.xml';
        file_put_contents($sitemapPath, $start, FILE_APPEND | LOCK_EX);

        foreach ($data as $item) {
            $urlLoc = "<url><loc>" . $this->host . $this->prefix . '/' . $this->route . '/' . $item['id'] . "/</loc></url>\n";
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

            $data = $this->getData($id, $this->tableName);

            $this->createSitemap($data, $fileId);

            // Увеличиваем счетчики id и file
            $id = $id + 50000;
            $fileId++;
        }
    }

    /**
     * Удаляем переданный файл карты сайта
     *
     * @param $fileId
     * @return mixed
     */
    private function delExistsFiles($fileId) {
        $filePath = $this->sitemapPath . $this->dir . '/' . $this->route . '_' . $fileId . '.xml';
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * Метод удаляет главный файл карт сайта ('sitemap_index.xml')
     */
    private function delSitemapIndex()
    {
        if (file_exists($this->sitemapPath . $this->dir . '/' . self::SITEMAP_INDEX_FILE_TITLE)) {
            unlink($this->sitemapPath . $this->dir. '/' . self::SITEMAP_INDEX_FILE_TITLE);
        }
    }
}
