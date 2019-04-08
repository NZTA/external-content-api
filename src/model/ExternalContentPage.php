<?php

namespace NZTA\ContentApi\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

class ExternalContentPage extends DataObject
{

    /**
     * @var string
     */
    private static $singular_name = 'Page';

    /**
     * @var string
     */
    private static $plural_name = 'Pages';

    /**
     * @var string
     */
    private static $table_name = 'ExternalContentPage';

    /**
     * @var array
     */
    private static $db = [
        'Name' => 'Varchar',
        'URL' => 'Varchar',
        'AppName' => 'Varchar',
    ];

    /**
     * @var array
     */
    private static $has_one = [
        'Area' => ExternalContentArea::class,
    ];

    /**
     * @var array
     */
    private static $belongs_many_many = [
        'Contents' => ExternalContent::class,
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
        //update AppName field, check if ApplicationID = 0
        if (!$this->Area()->ApplicationID) {
            $this->AppName = 'N/A';
        } else {
            $this->AppName = $this->Area()->Application()->Name;
        }
    }

    /**
     * @param Member $member
     *
     * @return bool
     */
    public function canView($member = null)
    {
        return Permission::check('VIEW_EXTERNAL_CONTENT_API');
    }

    /**
     * @param Member $member
     *
     * @return bool
     */
    public function canEdit($member = null)
    {
        return Permission::check('CMS_ACCESS_ExternalContentAdmin');
    }

    /**
     * @param Member $member
     *
     * @return bool
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
