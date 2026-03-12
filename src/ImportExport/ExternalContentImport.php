<?php

namespace NZTA\ContentApi\ImportExport;

use NZTA\ContentApi\Model\ExternalContent;
use NZTA\ContentApi\Model\ExternalContentApplication;
use NZTA\ContentApi\Model\ExternalContentArea;
use NZTA\ContentApi\Model\ExternalContentPage;
use NZTA\ContentApi\Model\ExternalContentType;
use SilverStripe\Control\Director;
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
        if (!in_array($applicationName, $this->previousApplicationName)) {
            $this->deletedRecord = false;
        }

        if (
            $this->deleteExistingRecords &&
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
        $application = $this->findOrMake(ExternalContentApplication::class, ['Name' => $applicationName]);
        if (!$application->exists()) {
            $application->write();
        }

        //find or make an existing area. If it's new, set the name and application ID, then write it
        $area = $this->findOrMake(
            ExternalContentArea::class,
            ['Name' => $areaName, 'ApplicationID' => $application->ID]
        );
        if (!$area->exists()) {
            $area->write();
        }

        //find or make existing page. If it's new, set the name, URL, and area ID, then write it
        $page = $this->findOrMake(
            ExternalContentPage::class,
            ['Name' => $pageName],
            ['Area.ApplicationID' => $application->ID]
        );
        if (!$page->exists()) {
            $page->URL = $pageUrl;
            $page->AreaID = $area->ID;
            $page->write();
        }

        //find or make existing content type. If it's new, determine if it's plaintext or not, then write
        $type = $this->findOrMake(ExternalContentType::class, ['Name' => $contentType]);
        if (!$type->exists() && $contentType) {
            $type->ContentIsPlaintext = preg_match('/rich text$/i', $contentType) === 0;
            $type->write();
        }

        //find or make existing content by ContentID. If it's new, set the content and type, then write
        $content = $this->findOrMake(ExternalContent::class, ['ExternalID' => $contentExternalID]);
        if (!$content->exists()) {
            $content->Content = $this->deWordify($contentContent);
            $content->TypeID = $type->ID;
            $content->write();
            if ((Director::isLive() || Director::isTest()) && !$content->isPublished()) {
                $content->publishSingle();
            }
            // we only want to add notification if there's a new content created
            $results->addCreated($content, 'content record created');
        }
        //add the page created above as a relation to this content
        $content->Pages()->add($page);

        return $content;
    }

    /**
     * Create a new dataobject, or find one matching the specified key and name
     * If the dataobject is new, it will set the $key to the given $name
     * This function will not write to the database, it will just return existing objects,
     * or newly created ones that haven't been written yet.
     *
     * @param string $dataObjectClass a subclass of DataObject to find
     * @param array $fields search filters or default values for the desired record
     * @param array $extraFilters
     *
     * @return DataObject
     */
    private function findOrMake($dataObjectClass, $fields = [], $extraFilters = [])
    {
        if (!class_exists($dataObjectClass) || !$fields || !is_array($fields) || !is_array($extraFilters)) {
            return null;
        }

        $filters = array_merge($fields, $extraFilters);

        $record = $dataObjectClass::get()->filter($filters)->first();

        if (!$record) {
            unset($fields['ID']);
            $record = $dataObjectClass::create($fields);
        }

        return $record;
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
        if (!$applicationObj) {
            return;
        }
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
