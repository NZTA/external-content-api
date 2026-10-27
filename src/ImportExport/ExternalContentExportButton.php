<?php

namespace NZTA\ContentApi\ImportExport;

use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldPaginator;

class ExternalContentExportButton extends GridFieldExportButton
{
    protected $exportColumns = [
        'Page.Area.Application.Name' => 'Application',
        'Page.Area.Name' => 'Area',
        'Page.Name' => 'PageName',
        'Page.URL' => 'PageUrl',
        'ContentID' => 'ContentID',
        'Content' => 'Content',
        'Type' => 'Type',
    ];

    /**
     * @param GridField $gridField
     *
     * @return false|string
     */
    // public function generateExportFileData($gridField)
    // {
    //     parent::generateExportFileData($gridField);

    //     //Remove GridFieldPaginator as we're going to export the entire list.
    //     $config = $gridField->getConfig();
    //     $config->removeComponentsByType(GridFieldPaginator::class);
    //     $config->getComponentByType(GridFieldDataColumns::class)->setDisplayFields($data)

    //     $listOfContent = $gridField->getManipulatedList();

    //     $row = [];

    //     //PHP doesn't let you output CSV directly to a variable; it expects a file handle
    //     //Rather than use the filesystem, dump to the output buffer
    //     //ob_get_clean will dump it to a string that can be returned
    //     $csvOut = fopen('php://output', 'w');
    //     ob_start();

    //     //native function generates all the tricky formatting for us
    //     fputcsv($csvOut, array_values($data));

    //     //data rows
    //     foreach ($listOfContent as $content) {
    //         foreach ($content->Pages() as $page) {
    //             $row['Application'] = $page->Area() && $page->Area()->Application()
    //                 ? $page->Area()->Application()->Name
    //                 : '';

    //             $row['Area'] = $page->Area() ? $page->Area()->Name : '';
    //             $row['PageName'] = $page->Name;
    //             $row['PageUrl'] = $page->URL;
    //             $row['ContentID'] = $content->ExternalID;
    //             $row['Body'] = str_replace(["\r", "\n"], "\n", $content->Content ?? '');
    //             $row['Type'] = $content->Type() ? $content->Type()->Name : '';

    //             //dump CSV row to output buffer
    //             fputcsv($csvOut, $row);
    //         }
    //     }

    //     //close the handle and dump the output buffer to a string
    //     fclose($csvOut);
    //     $csv = ob_get_clean();
    //     return $csv;
    // }
}
