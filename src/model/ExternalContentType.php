<?php

namespace NZTA\ContentApi\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

class ExternalContentType extends DataObject
{

    /**
     * @var string
     */
    private static $singular_name = 'Content Type';

    /**
     * @var string
     */
    private static $plural_name = 'Content Types';

    /**
     * @var array
     */
    private static $db = [
        'Name' => 'Varchar',
        'ContentIsPlaintext' => 'Boolean', // 0 is html, 1 is plaintext
    ];

    /**
     * @var string
     */
    private static $table_name = 'ExternalContentType';

    /**
     * @var array
     */
    private static $has_many = [
        'Contents' => ExternalContent::class,
    ];

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
