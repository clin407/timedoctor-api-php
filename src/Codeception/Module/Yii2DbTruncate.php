<?php

namespace Codeception\Module;

use Codeception\Exception\ModuleException;
use Codeception\Lib\Interfaces\DependsOnModule;
use Codeception\Module;
use Codeception\TestInterface;
use PDO;
use yii\db\Connection;

/**
 * Truncates all tables in the database except for some before every test.
 * @see https://dzone.com/articles/customizing-codeception This class is
 * based on that article.
 */
class Yii2DbTruncate extends Module implements DependsOnModule
{

    protected $config = [
        // Path to a php file that returns an array of the names of the
        // tables that should not be truncated. All the other tables in the
        // database will be truncated.
        "pathToExcludes" => "config/dbTruncate/skipped-tables.php"
    ];

    /* @var Connection */
    private $db;

    /* @var Yii2 */
    private $yii;

    public function _initialize()
    {
        parent::_initialize();
    }

    public function _depends()
    {
        return [
            'Yii2' => "This module requires enabling Yii2 module"
        ];
    }

    public function _inject(Yii2 $yii2Module)
    {
        $this->yii = $yii2Module;
    }

    public function _afterSuite()
    {
        $this->cleanup();
    }

    public function _before(TestInterface $test)
    {
        $this->cleanup();
    }


    protected function cleanup()
    {
        $this->db = $this->yii->app->getDb();
        try {
            $this->disableForeignKeyChecks();
            $tablesToCleanUp = array_diff(
                $this->queryListOfAllTables(),
                require $this->pathToExcludesFile()
            );
            \Yii::info(
                "Cleaning up tables " . implode(", ", $tablesToCleanUp)
            );
            foreach ($tablesToCleanUp as $table) {
                $this->truncateTable($table);
            }
            $this->enableForeignKeysChecks();
        } catch (\Exception $e) {
            throw new ModuleException(__CLASS__, $e->getMessage());
        }
    }

    /**
     * Path to the file where excluded tables are listed.
     * @return string
     */
    private function pathToExcludesFile()
    {
        return codecept_root_dir() . "/" . $this->config["pathToExcludes"];
    }


    protected function disableForeignKeyChecks()
    {
        $this->db->createCommand('SET FOREIGN_KEY_CHECKS=0;')->execute();
    }

    protected function enableForeignKeysChecks()
    {
        $this->db->createCommand('SET FOREIGN_KEY_CHECKS=1;')->execute();
    }

    /**
     * @return string[] List of all tables in the database.
     */
    private function queryListOfAllTables()
    {
        return array_map(
            function ($row) {
                return $row[0];
            },
            $this->db
                ->createCommand(
                    "SHOW FULL TABLES WHERE TABLE_TYPE LIKE '%TABLE';"
                )
                ->queryAll(PDO::FETCH_NUM)
        );
    }

    /**
     * @param string $tableName
     */
    protected function truncateTable($tableName)
    {
        $this->db
            ->createCommand('TRUNCATE TABLE `' . $tableName . '`')
            ->execute();
    }

}
