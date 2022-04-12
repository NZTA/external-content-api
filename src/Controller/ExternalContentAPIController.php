<?php

namespace NZTA\ContentApi\Controller;

use NZTA\ContentApi\Model\ExternalContent;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\DataList;
use SilverStripe\RestfulServer\DataFormatter;
use SilverStripe\RestfulServer\DataFormatter\XMLDataFormatter;
use SilverStripe\Security\BasicAuth;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

class ExternalContentAPIController extends Controller
{
    /**
     * @var string
     */
    private static $realm = 'External Content API';

    /**
     * @var array
     */
    private static $allowed_actions = [
        'ExternalContent',
    ];

    public function init()
    {
        parent::init();
        $realm = $this->config()->get('realm');
        $member = BasicAuth::requireLogin($this->getRequest(), $realm);
        if (!Permission::checkMember($member, "VIEW_EXTERNAL_CONTENT_API")) {
            Security::permissionFailure();
        }
        Security::setCurrentUser($member);
    }

    /**
     * @param HTTPRequest $request
     *
     * @return string
     */
    public function ExternalContent(HTTPRequest $request)
    {
        $format = $request->getVar('format');
        $formatter = DataFormatter::for_extension($format);
        if (!$formatter) {
            $formatter = new XMLDataFormatter();
        } // default to XML
        $this->getResponse()->addHeader('Content-Type', $formatter->getOutputContentType());
        return $formatter->convertDataObjectSet($this->getData($request));
    }

    /**
     * @param HTTPRequest $request
     *
     * @return DataList of ExternalContent
     */
    protected function getData(HTTPRequest $request)
    {
        $r = ExternalContent::get();

        $applicationName = $request->getVar('applicationName');
        $areaName = $request->getVar('areaName');
        $pageName = $request->getVar('pageName');

        $filterOptions = [];
        if ($applicationName) {
            $filterOptions['Pages.Area.Application.Name:ExactMatch'] = $applicationName;
        }

        if ($areaName) {
            $filterOptions['Pages.Area.Name:ExactMatch'] = $areaName;
        }

        if ($pageName) {
            $filterOptions['Pages.Name:ExactMatch'] = $pageName;
        }

        return $r->filter($filterOptions);
    }
}
