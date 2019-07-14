<?php

namespace NZTA\ContentApi\Model;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Versioned\Versioned;
use SilverStripe\VersionedAdmin\Forms\HistoryViewerField;
use SilverStripe\View\Requirements;

class ExternalContent extends DataObject
{

    /**
     * @var string
     */
    private static $singular_name = 'Content';

    /**
     * @var string
     */
    private static $plural_name = 'Content items';

    /**
     * @var string
     */
    private static $table_name = 'ExternalContent';

    /**
     * @var array
     */
    private static $db = [
        'ExternalID' => 'Varchar',
        'Content' => 'HTMLText',
    ];


    /**
     * @var array
     */
    private static $has_one = [
        'Type' => ExternalContentType::class,
    ];

    /**
     * @var array
     */
    private static $many_many = [
        'Pages' => ExternalContentPage::class,
    ];

    /**
     * @var array
     */
    private static $indexes = [
        'ExternalIDIndex' => [
            'type' => 'index',
            'columns' => ['ExternalID'],
        ],
    ];

    private static $searchable_fields = [
        'ExternalID',
        'Type.Name' => ['title' => 'Content Type'],
        'Pages.AppName' => ['title' => 'Application']
    ];

    /**
     * Combine summary fields with field labels
     *
     * @var array
     */
    private static $summary_fields = [
        'ExternalID' => 'External ID',
        'ContentSummary' => 'Content',
        'Type.Name' => 'Content type',
    ];

    private static $extensions = [
        Versioned::class
    ];

    /**
     * Strip HTML from content summary
     */
    public function ContentSummary()
    {
        return $this->obj('Content')->Summary(10);
    }

    /**
     * @param Member $member
     *
     * @return bool|int
     */
    public function canView($member = null)
    {
        return Permission::check('VIEW_EXTERNAL_CONTENT_API');
    }

    /**
     * @param Member $member
     *
     * @return bool|int
     */
    public function canEdit($member = null)
    {
        return Permission::check('CMS_ACCESS_ExternalContentAdmin');
    }

    /**
     * @param Member $member
     *
     * @return bool|int
     */
    public function canDelete($member = null)
    {
        return Permission::check('CMS_ACCESS_ExternalContentAdmin');
    }

    /**
     * @param Member $member
     * @param array $context
     *
     * @return bool|int
     */
    public function canCreate($member = null, $context = array())
    {
        return Permission::check('CMS_ACCESS_ExternalContentAdmin');
    }

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $contentField = null;
        Requirements::javascript('nzta/external-content-api: client/dist/js/bundle.js');

        if ($this->IsPlaintext()) {
            $contentField = TextareaField::create('Content', 'Content', $this->Content);
            $contentField->setDescription('This editor accepts plain text only due to the Content Type selected');
        } else {
            $contentField = HtmlEditorField::create('Content', 'Content')->setEditorConfig('external-content-api');
        }

        $fields->addFieldToTab('Root.History', HistoryViewerField::create('ExternalContentHistory'));
        $fields->replaceField('Content', $contentField);

        return $fields;
    }

    /**
     * @return bool
     */
    public function IsPlaintext()
    {
        if (!$this->Type()) {
            return false;
        } // default to html
        return $this->Type()->ContentIsPlaintext;
    }

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if ($this->IsPlaintext()) {
            $this->Content = $this->cleanupPlaintext($this->Content);
        }
    }

    /**
     * @param $raw
     *
     * @return string
     */
    protected function cleanupPlaintext($raw)
    {
        $r = strip_tags($raw);
        $r = trim(preg_replace('/\s+/', ' ', $r)); // remove newlines
        return $r;
    }
}
