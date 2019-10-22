<?php

namespace NZTA\ContentApi\ImportExport;

use NZTA\ContentApi\Model\ExternalContent;
use NZTA\ContentApi\Model\ExternalContentApplication;
use NZTA\ContentApi\Model\ExternalContentArea;
use NZTA\ContentApi\Model\ExternalContentPage;
use NZTA\ContentApi\Model\ExternalContentType;
use SilverStripe\Control\Director;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\BulkLoader_Result;
use SilverStripe\Dev\CsvBulkLoader;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationException;

class ExternalContentImport extends CsvBulkLoader
{
    /**
     * used to check if record has been deleted
     * during import
     * @var bool
     */
    protected $deletedRecord = false;
    /**
     * stores all processed application name for
     * import check
     * @var
     */
    protected $previousApplicationName = [];

    /**
     * @param array $record
     * @param array $columnMap
     * @param BulkLoader_Result $results
     * @param bool $preview
     *
     * @return ExternalContent|DataObject
     * @throws ValidationException
     */
    public function processRecord($record, $columnMap, &$results, $preview = false)
    {
        // if we have reached this point, assume that the row has been parsed already

        // application name
        $applicationName = isset($record['Application']) ? $record['Application'] : null;

        // if replace data was checked we only want to delete records for each application uploaded.
        // if more than one application was uploaded on csv, reset deletedRecord property
        if (!in_array($applicationName, $this->previousApplicationName)){
            $this->deletedRecord = false;
        }

        if ($this->deleteExistingRecords &&
            !$this->deletedRecord &&
            !in_array($applicationName, $this->previousApplicationName)
        ) {
            $this->deleteRecordByApplication($applicationName);
            $this->deletedRecord = true;
            $this->previousApplicationName[] = $applicationName;
        }

        //area name
        $areaName = isset($record['Area']) ? $record['Area'] : null;

        //page name and URL
        $pageName = isset($record['PageName']) ? $record['PageName'] : null;
        $pageUrl = isset($record['PageUrl']) ? $record['PageUrl'] : null;

        //content ID and content
        $contentExternalID = isset($record['ContentID']) ? $record['ContentID'] : null;
        $contentContent = isset($record['Content']) ? $record['Content'] : null;

        //what type of content?
        $contentType = isset($record['Type']) ? $record['Type'] : null;


        //find or make an existing application. If it's new, set the name and write it
        $application = $this->findOrMake(ExternalContentApplication::class, $applicationName);
        if ($application && !$application->ID) {
            $application->write();
        }

        //find or make an existing area. If it's new, set the name and application ID, then write it
        $area = $this->findOrMake(ExternalContentArea::class, $areaName);
        if ($area && !$area->ID) {
            $area->ApplicationID = $application->ID;
            $area->write();
        }

        //find or make existing page. If it's new, set the name, URL, and area ID, then write it
        $contentPage = $this->findOrMake(ExternalContentPage::class, $pageName);
        if ($contentPage && !$contentPage->ID) {
            $contentPage->URL = Convert::raw2sql($pageUrl);
            $contentPage->AreaID = $area->ID;
            $contentPage->write();
        }

        //find or make existing content type. If it's new, determine if it's plaintext or not, then write
        $type = $this->findOrMake(ExternalContentType::class, $contentType);
        if ($type && !$type->ID) {
            $type->ContentIsPlaintext = true;
            if (preg_match('/rich text$/', strtolower($contentType))) {
                $type->ContentIsPlaintext = false;
            }
            $type->write();
        }

        //find or make existing content by ContentID. If it's new, set the content and type, then write
        $c = $this->findOrMake(ExternalContent::class, $contentExternalID, 'ExternalID');
        if ($c) {
            if (!$c->ID) {
                $c->Content = $this->deWordify($contentContent);
                $c->TypeID = $type->ID;
                $c->write();
                if ((Director::isLive() || Director::isTest()) && !$c->isPublished()) {
                    $c->publishSingle();
                }
                // we only want to add notification if there's a new content created
                $results->addCreated($c, 'content record created');
            }
            //add the page created above as a relation to this content
            $c->Pages()->add($contentPage);
        }

        return $c;
    }

    /**
     * Create a new dataobject, or find one matching the specified key and name
     * If the dataobject is new, it will set the $key to the given $name
     * This function will not write to the database, it will just return existing objects,
     * or newly created ones that haven't been written yet.
     *
     * @param string $dataObject
     * @param string $name
     * @param string $key
     *
     * @return DataObject
     */
    private function findOrMake($dataObject, $name, $key = 'Name')
    {
        if (!class_exists($dataObject)) {
            return null;
        }

        $do = $dataObject::create();

        if ($dataObject && $key && $name) {
            $key = Convert::raw2sql($key);
            $name = Convert::raw2sql($name);
            $do = $dataObject::get()->find($key, $name);
            if (!($do && $do->ID)) {
                $do = $dataObject::create();
                $do->$key = $name;
            }
        }
        return $do;
    }

    /**
     * Convert "smart" Microsoft Word characters to standard ASCII
     * see http://stackoverflow.com/questions/1262038/how-to-replace-microsoft-encoded-quotes-in-php
     *
     * @param   $content string that contains Word generated characters
     *
     * @return string
     */
    private function deWordify($content)
    {
        $search =
            [
                chr(145), //‘ msword single quote
                chr(146), //’  msword single quote
                chr(147), //“  msword double quote
                chr(148), //”  msword double quote
                chr(151) // msword emdash
            ];

        $replace = [
            "'",
            "'",
            '"',
            '"',
            '-',
        ];

        return str_replace($search, $replace, $content);
    }

    /**
     * @return array
     */
    public function getImportSpec()
    {
        // CSV format shown to the user, does not affect functionality
        return [
            'fields'    => [
                'Application' => 'Application.Name',
                'Area'        => 'Area.Name',
                'PageName'    => 'Page.Name',
                'PageUrl'     => 'Page.URL',
                'ContentID'   => 'Content.ID',
                'Content'     => 'Content.Content',
                'Type'        => 'Type.Name',
            ],
            'relations' => [],
        ];
    }

    /**
     * Override BulkLoader::load with custom deleteExistingRecords functionality
     *
     * @param string $filepath same as @link BulkLoader::load
     *
     * @return null|\SilverStripe\Dev\BulkLoader_Result
     */
    public function load($filepath)
    {
        set_time_limit(3600);
        ini_set('memory_limit', '512M');

        return $this->processAll($filepath);
    }

    /**
     * This will allow processRecord() to delete records by Application Name
     * @param $applicationName
     */
    private function deleteRecordByApplication($applicationName)
    {
        $areaIds = array();
        $pageIds = array();
        $contentIds = array();
        $applicationObj = ExternalContentApplication::get()
            ->filter(['Name' => $applicationName])
            ->first();
        //if application is new, nothing to delete
        if (!$applicationObj) return;
        $areas = $applicationObj->Areas();
        foreach ($areas as $area) {
            $areaIds[] = $area->ID;
            $tempPages = $area->Pages();
            foreach ($tempPages as $tempPage) {
                $pageIds[] = $tempPage->ID;
                $tempContents = $tempPage->Contents();
                foreach ($tempContents as $tempContent) {
                    $contentIds[] = $tempContent->ID;
                }
            }
        }
        // if replacing data, we need to individually delete objects from the bottom-up
        // this means deleting in a particular order:
        // 1. Content, 2. Page, 3. Area, 4.Application
        //content
        ExternalContent::get()->removeMany($contentIds);
        //page
        ExternalContentPage::get()->removeMany($pageIds);
        //area
        ExternalContentArea::get()->removeMany($areaIds);
        //application
        ExternalContentArea::get()->removeByFilter(sprintf(
            '"Name" = \'%s\'',
            $applicationName
        ));
    }

}
