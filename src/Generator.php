<?php
/**
 * Класс генерирует карты сайта
 */

namespace Borisey\RussianParliamentApi;

use Yii;

class SitemapGeneratorController
{
    //todo:: Создавать директории, если их нет на сервере
    const SITEMAP_PATH = 'web/sitemaps';
    const DIR = '/enc/';
    const FILE_TITLE = 'enc_';
    const SITEMAP_INDEX_FILE_TITLE = 'sitemap_index.xml';
    const URL = 'http://example.ru';
    const URL_PATH = '/enc/';
    const ID = '0';
    const FILE_ID = '1';

    public function generate($tableName, $route)
    {
        // Создаем все файлы карт сайта
        $this->createAllSitemaps($tableName, $route);
    }

    /**
     * Метод создает все файлы карт сайта
     * @param $tableName
     * @param $route
     */
    private function createAllSitemaps($tableName, $route)
    {
        // Создаем карты сайта переданных словарей
        $this->createSitemaps($tableName, $route);
    }

    /**
     * Метод создает главный файл карт сайта
     */
    public function createSitemapIndex()
    {
        // Удаляем главный файл карт сайта ('sitemap_index.xml')
        $this->delSitemapIndex();

        $sitemapsDir =  self::SITEMAP_PATH . self::DIR;
        $sitemapIndexPath = $sitemapsDir . self::SITEMAP_INDEX_FILE_TITLE;

        // Сохраняем в главном файле карт сайта начальную строку
        $this->putIndexSitemapStart($sitemapIndexPath);

        $scanResults = scandir($sitemapsDir);
        foreach ($scanResults as $item) {
            $filePath = $sitemapsDir . $item;
            if (is_file($filePath)) {
                // Добавляем ссылки на карты сайта
                $sitemapLocPath = "<sitemap><loc>" . self::URL . '/' . $filePath . "</loc></sitemap>\n";
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
    private function createSitemaps($tableName, $route)
    {
        $count = $this->getCount($tableName);
        $this->createSitemapsIndex(self::ID, $count, $tableName, $route);
    }

    /**
     * Получаем общее количество записей в базе
     * @param $tableName
     * @return mixed
     * @throws \yii\db\Exception
     */
    private function getCount($tableName) {
        $count = Yii::$app->db->createCommand("SELECT COUNT(*) FROM {$tableName}")
            ->queryOne();

        return $count['COUNT(*)'];
    }

    /**
     * Метод получает 50000 строк из переданной таблицы
     *
     * @param $id
     * @param $tableName
     * @return mixed
     * @throws \yii\db\Exception
     */
    private function getData($id, $tableName) {
        return Yii::$app->db->createCommand("SELECT `id` FROM {$tableName} WHERE `id` > {$id} ORDER BY `id` ASC LIMIT 50000")
            ->queryAll();
    }

    /**
     * Создаем карту сайта конкретного словаря
     *
     * @param $data
     * @param $route
     * @param $fileId
     */
    private function createSitemap($data, $route, $fileId) {
        $start = "<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9' xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:schemaLocation='http://www.sitemaps.org/schemas/sitemap/0.9http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd'>\n";
        $sitemapPath = self::SITEMAP_PATH . self::DIR . $route . '_' . $fileId . '.xml';
        file_put_contents($sitemapPath, $start, FILE_APPEND | LOCK_EX);

        foreach ($data as $item) {
            $urlLoc = "<url><loc>" . self::URL . '/d/' . $route . '/' . $item['id'] . "/</loc></url>\n";
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
     * @param $tableName
     * @param $route
     */
    private function createSitemapsIndex($id, $count, $tableName, $route) {
        $fileId = self::FILE_ID;

        // Запускаем цикл создания карт сайта.
        // Цикл работает до тех пор пока не переобойдет все записи в базе.
        // Количество записей в переменной $count
        while ($id <= $count)
        {
            // Удаляем существующий файл карты сайта
            $this->delExistsFiles($route, $fileId);

            $data = $this->getData($id, $tableName);

            $this->createSitemap($data, $route, $fileId);

            // Создаем массив карт сайта
            $this->sitemaps[] = $route . '_' . $fileId . '.xml';

            // Увеличиваем счетчики id и file
            $id = $id + 50000;
            $fileId++;
        }
    }

    /**
     * Удаляем переданный файл карты сайта
     *
     * @param $route
     * @param $fileId
     * @return mixed
     */
    private function delExistsFiles($route, $fileId) {
        $filePath = self::SITEMAP_PATH . self::DIR . $route . '_' . $fileId . '.xml';
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * Метод удаляет главный файл карт сайта ('sitemap_index.xml')
     */
    private function delSitemapIndex()
    {
        if (file_exists(self::SITEMAP_PATH . self::DIR . self::SITEMAP_INDEX_FILE_TITLE)) {
            unlink(self::SITEMAP_PATH . self::DIR . self::SITEMAP_INDEX_FILE_TITLE);
        }
    }
}