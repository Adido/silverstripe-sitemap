<?php

namespace Innoweb\Sitemap\Pages;

use SilverStripe\Control\HTTPRequest;

class SitemapPageController extends \PageController
{
    /**
     * Render the sitemap page.
     *
     * @param HTTPRequest $request
     * @return \SilverStripe\ORM\FieldType\DBHTMLText
     */
    public function index(HTTPRequest $request)
    {
        return $this->customise([])->renderWith([
            'SitemapPage',
            'Page'
        ]);
    }
}
