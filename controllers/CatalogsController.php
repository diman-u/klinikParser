<?php

namespace console\controllers;

use common\entities\Catalog;
use common\entities\Organization;
use common\entities\OrganizationCatalog;
use common\entities\Question;
use common\entities\QuestionAnswer;

use common\entities\SpecialistCatalog;
use common\modules\files\services\FilesService;
use yii\console\Controller;
use yii\db\Exception;
use yii\helpers\Console;

/**
 * Manage catalogs
 * Class CatalogsController
 * @package console\controllers
 */
class CatalogsController extends Controller
{
    /**
     * @var string
     */
    public $defaultAction = 'import';
    

    /**
     * Creates a new user
     */
    public function actionImport()
    {
        // получаем путь к файлу импорта
        $level = $this->prompt('Enter level of importing catalogs (1-3):');
        if (!is_numeric($level) || $level < 1 || $level > 3 ) {
            Console::output('Invalid level');
        }


        $deleteCurrent = $this->prompt('Delete current catalogs? (y/n):') == 'y';

        if ($deleteCurrent) {
            \Yii::$app->db->createCommand('SET FOREIGN_KEY_CHECKS=0;')->execute();
            Catalog::deleteAll('level >= :level', ['level' => $level]);
            \Yii::$app->db->createCommand('SET FOREIGN_KEY_CHECKS=1;')->execute();
        }

        $hasHeader = $this->prompt('Has header line? (y/n):') == 'y';

        $csvPath = $this->prompt('Enter filename of csv-file (in static-data/catalogs):', ['pattern' => '/\w+/']);

        $csvPath = \Yii::$app->basePath.'/../static-data/catalogs/'.$csvPath;

        try {

            $countImported = 0;

            if (($handle = fopen($csvPath, "r")) !== FALSE) {
                $line = 0;
                while (($data = fgetcsv($handle, null, ";")) !== FALSE) {
                    $line++;
                    if ($hasHeader && $line == 1) {
                        continue;
                    }

                    $title = $data[$level == 1 ? 1 : 2];
                    $importId = $data[0];
                    $parentImportId = $data[1];

                    if (!$catalog = Catalog::find()->where(['level' => $level, 'importId' => $importId])->one()) {
                        $catalog = new Catalog();
                    }
                    $catalog->title = $title;
                    $catalog->shortTitle = $title;
                    $catalog->importId = $importId;
                    $catalog->priority = 0;
                    $catalog->level = $level;
                    if ($level > 1) {
                        $parentCatalog = Catalog::find()->where(['importId' => $parentImportId, 'level' => $level - 1])->one();
                        if ($parentCatalog) {
                            $catalog->parentId = $parentCatalog->getPrimaryKey();
                            $countCatalogsInThisLevel = Catalog::find()->where(['parentId' => $parentCatalog->getPrimaryKey()])->count();
                            if ($countCatalogsInThisLevel < 10) {
                                $catalog->priority = 1000;
                            }
                        } else {
                            Console::output('Parent catalog (id = ' . $parentImportId . ') for id = ' . $importId . ' was not found');
                        }
                    }
                    $catalog->save();
                    $countImported++;
                }
                fclose($handle);
            } else {
                Console::output('File has been not found or is invalid');
            }

            Console::output('Imported catalogs: ' . $countImported);

            $countOrganizationsWithoutCatalogs = OrganizationCatalog::find()->alias('o')->innerJoin(Catalog::tableName().' as c', 'c.id = o.catalogId')->where('c.id is null')->count();
            Console::output('Count organizations without catalogs: ' . $countOrganizationsWithoutCatalogs);

            $countSpecialistsWithoutCatalogs = SpecialistCatalog::find()->alias('s')->innerJoin(Catalog::tableName().' as c', 'c.id = s.catalogId')->where('c.id is null')->count();
            Console::output('Count specialists without catalogs: ' . $countSpecialistsWithoutCatalogs);
        } catch (Exception $e) {
            Console::output('Error: ' . $e);
        }

        Console::output('Import is finished');
    }
}
