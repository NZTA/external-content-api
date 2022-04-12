<?php

namespace NZTA\ContentApi\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

class ExternalContentApplication extends DataObject
{
    /**
     * @var string
     */
    private static $singular_name = 'Application';

    /**
     * @var string
     */
    private static $plural_name = 'Applications';

    /**
     * @var string
     */
    private static $table_name = 'ExternalContentApplication';

    /**
     * @var array
     */
    private static $db = [
        'Name' => 'Varchar',
    ];

    /**
     * @var array
     */
    private static $has_many = [
        'Areas' => ExternalContentArea::class,
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

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        //we need to create a group for Permissions check to work correctly
        // check if one is created already
        $readers = $this->findOrMakeExternalContentGroup([
            'Title' => 'External content API readers',
            'Description' => 'Controls view access to external content API',
        ]);

        if ($readers && $readers->ID) {
            Permission::grant($readers->ID, 'VIEW_EXTERNAL_CONTENT_API');

            $editors = $this->findOrMakeExternalContentGroup([
                'Title' => 'External content API editors',
                'Description' => 'Controls editor access to external content API',
            ]);

            if ($editors && $editors->ID) {
                Permission::grant($editors->ID, 'VIEW_EXTERNAL_CONTENT_API');
                Permission::grant($editors->ID, 'CMS_ACCESS_ExternalContentAdmin');
                $editors->HtmlEditorConfig = 'external-content-api';
                $editors->write();
                $readers->Groups()->add($editors);
            }
        }
    }

    /**
     * Update ExternalContentPage AppName
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();
        $appName = $this->Name;
        $areas = $this->Areas();
        foreach ($areas as $area) {
            $pages = $area->Pages();
            foreach ($pages as $page) {
                if ($page->AppName != $appName) {
                    $page->AppName = $appName;
                    $page->write();
                }
            }
        }
    }

    /**
     * @param array $attributes
     *
     * @return null|\SilverStripe\ORM\DataObject
     * @throws ValidationException
     */
    private function findOrMakeExternalContentGroup(array $attributes)
    {
        $group = Group::get()->find('Title', $attributes['Title']);
        if (!($group && $group->ID)) {
            //create one with a specific title
            $group = Group::create()->update($attributes);

            //save its ID if successfully written
            $groupID = $group->write();

            //display the ID in the build logs
            if ($groupID) {
                DB::alteration_message(
                    sprintf(
                        "External content API group \'%s\'created (ID: %d)",
                        $group->Title,
                        $group->ID
                    )
                );
            }
        }
        return $group;
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
     * @return bool|int
     */
    public function canCreate($member = null, $context = array())
    {
        return Permission::check('CMS_ACCESS_ExternalContentAdmin');
    }
}
