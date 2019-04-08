<?php

namespace NZTA\ContentApi\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

class ExternalContentArea extends DataObject
{

    /**
     * @var string
     */
    private static $singular_name = 'Area';

    /**
     * @var string
     */
    private static $plural_name = 'Areas';

    /**
     * @var string
     */
    private static $table_name = 'ExternalContentArea';

    /**
     * @var array
     */
    private static $db = [
        'Name' => 'Varchar',
    ];

    /**
     * @var array
     */
    private static $has_one = [
        'Application' => ExternalContentApplication::class,
    ];

    /**
     * @var array
     */
    private static $has_many = [
        'Pages' => ExternalContentPage::class,
    ];

    /**
     * @var array
     */
    private static $indexes = [
        'IndexName' => [
            'type' => 'index',
            'columns' => ['Name'],
        ],
    ];

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        //update AppName field
        $pages = $this->Pages();
        foreach ($pages as $page) {
            if (!$page->Area()->ApplicationID) {
                $page->AppName = 'N/A';
            } else {
                $page->AppName = $this->Application()->Name;
                $page->write();
            }
        }
    }

    /**
     * @param null $member
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
}
