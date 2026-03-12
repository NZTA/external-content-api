<?php

namespace NZTA\ContentApi\Admin;

use NZTA\ContentApi\ImportExport\ExternalContentExportButton;
use NZTA\ContentApi\ImportExport\ExternalContentImport;
use NZTA\ContentApi\Model\ExternalContent;
use NZTA\ContentApi\Model\ExternalContentApplication;
use NZTA\ContentApi\Model\ExternalContentArea;
use NZTA\ContentApi\Model\ExternalContentPage;
use NZTA\ContentApi\Model\ExternalContentType;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SilverStripe\Security\PermissionProvider;

class ExternalContentAdmin extends ModelAdmin implements PermissionProvider
{
    /**
     * @var array
     */
    private static $managed_models = [
        ExternalContent::class,
        ExternalContentApplication::class,
        ExternalContentArea::class,
        ExternalContentPage::class,
        ExternalContentType::class,
    ];

    /**
     * @var string
     */
    private static $table_name = 'ExternalContentAdmin';

    /**
     * @var string
     */
    private static $url_segment = 'external-content';

    /**
     * @var string
     */
    private static $menu_title = 'External Content';

    /**
     * @var array
     */
    private static $model_importers = [
        ExternalContent::class => ExternalContentImport::class,
    ];

    /**
     * @param integer $id
     * @param array $fields
     *
     * @return \SilverStripe\Forms\Form
     */
    public function getEditForm($id = null, $fields = null)
    {
        // add ability to search
        $form = parent::getEditForm($id, $fields);
        $gridFieldName = $this->sanitiseClassName($this->modelClass);
        $gridField = $form->Fields()->fieldByName($gridFieldName);
        $gridField->getConfig()
            ->removeComponentsByType(GridFieldPrintButton::class)
            ->removeComponentsByType(GridFieldExportButton::class);

        if ($this->modelClass === ExternalContent::class) {
            $exportButton = new ExternalContentExportButton('buttons-before-left');
            $gridField->getConfig()
                ->addComponent($exportButton);
        }

        return $form;
    }

    /**
     * @return array
     */
    public function providePermissions()
    {
        return [
            'VIEW_EXTERNAL_CONTENT_API' => 'Ability to view and use the external content API',
        ];
    }
}
