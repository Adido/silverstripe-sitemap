<?php

namespace Innoweb\Sitemap\Pages;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Versioned\Versioned;

class SitemapPage extends \Page {

    private static $singular_name = 'Sitemap Page';
    private static $plural_name = 'Sitemap Pages';
    private static $description = 'Displays a sitemap with all pages marked for display in the sitemap.';

    private static $table_name = 'SitemapPage';

    private static $icon  = 'innoweb/silverstripe-sitemap: client/images/treeicons/sitemap.gif';

	private static $excluded_pagetypes = [];

    /**
     * An associative array representing which data objects should appear as
     * direct descendants to SiteMap pages.
     *
     * Qualified SiteMap Class Name => Qualified DataObject ClassName
     *
     * @var array<string, string>
     */
	private static $data_object_children = [];

    /**
     * An associative array of filters to be applied to child data object queries.
     *
     * Qualified DataObject ClassName => [Field Name => Value]
     *
     * @var array<string, <string, string>>
     */
	private static $data_object_filters = [];

    private static $defaults = [
        'ShowInMenus'   => false,
        'ShowInSearch'  => false,
        'ShowInSitemap' => false,
        'Priority'      => '1.0',
    ];

    public function SitemapRootItems()
    {
        $parent = class_exists('Symbiote\Multisites\Multisites') ? $this->SiteID : 0;
        $filter = [
            'ParentID'       =>  $parent,
            'ShowInSitemap'  =>  1,
        ];

        if (count(self::config()->get('excluded_pagetypes'))) {
            $filter['ClassName:not'] = self::config()->get('excluded_pagetypes');
        }

        return Versioned::get_by_stage(SiteTree::class, Versioned::LIVE)->filter($filter);
    }

}
