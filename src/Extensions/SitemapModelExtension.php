<?php

namespace Innoweb\Sitemap\Extensions;

use Innoweb\Sitemap\Pages\SitemapPage;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\ORM\DB;
use SilverStripe\Versioned\Versioned;

class SitemapModelExtension extends DataExtension
{
    private static $db = [
        'ShowInSitemap' => 'Boolean(1)',
    ];

    private static $defaults = [
        'ShowInSitemap' => true,
    ];

    public function updateSettingsFields(FieldList $fields)
    {
        // only update SiteTree settings section
        if (! $this->owner instanceof SiteTree) return;

        if (in_array($this->owner->ClassName, SitemapPage::config()->get('excluded_pagetypes'))) {
            return;
        }

        $this->addShowInSitemapField($fields);
    }

    public function updateCMSFields(FieldList $fields)
    {
        // ensure sitemap setting isn't added twice to SiteTree pages
        if ($this->owner instanceof SiteTree) return;

        $this->addShowInSitemapField($fields);
    }

    /**
     * Get all descendants of a SiteMap page.
     *
     * @return null|DataList
     */
    public function SitemapChildren()
    {
        $treeChildren = $this->getSiteTreeChildren();
        if (! $treeChildren) return null;

        $dataObjectChildren = $this->getDataObjectChildren();
        if (! $dataObjectChildren) return $treeChildren;

        // merge both tree and data object children results
        $children = new ArrayList();
        $children->merge($treeChildren);
        $children->merge($dataObjectChildren);
        return $children;
    }

    /**
     * Get the sitemap cache key.
     *
     * @return string
     */
    public function SitemapCacheKey()
    {
        $query = '
            SELECT MAX(LastEdited) as LastEdited, SUM(count) as Total
            FROM (
                SELECT MAX(LastEdited) AS LastEdited, COUNT(ID) AS count from SiteTree_Live
        ';

        // sum any child models
        $mapping = SitemapPage::config()->get('data_object_children');
        if (! is_array($mapping)) $mapping = [];
        foreach (array_values($mapping) as $model) {
            $table = Convert::raw2sql(
                Injector::inst()->get(DataObjectSchema::class)->tableName($model)
            );
            $query .= " UNION SELECT MAX(LastEdited) AS LastEdited, COUNT(ID) AS count from {$table} ";
        }

        $query .= ') as summary';

        $results = DB::query($query)->next();
        return implode('-_-', [
            $this->owner->ID,
            $results['LastEdited'] ?? '',
            (int) ($results['Total'] ?? 0),
        ]);
    }

    /**
     * Get all SiteTree descendants of a page.
     *
     * @return null|DataList
     */
    protected function getSiteTreeChildren()
    {
        if (! $this->owner instanceof SiteTree) return null;

        $filter = [
            'ShowInSitemap' => 1,
            'ParentID' => $this->owner->ID,
        ];
        $excludedPageTypes = SitemapPage::config()->get('excluded_pagetypes');
        if (count($excludedPageTypes)) $filter['ClassName:not'] = $excludedPageTypes;

        return Versioned::get_by_stage(SiteTree::class, Versioned::LIVE)->filter($filter);
    }

    /**
     * Get all DataObject descendants of a page.
     *
     * @return null|DataList
     */
    protected function getDataObjectChildren()
    {
        if (! $this->owner instanceof SiteTree) return null;

        // don't return any children if mapping is not defined for the page type
        $mapping = SitemapPage::config()->get('data_object_children');
        if (! is_array($mapping)) $mapping = [];
        if (! in_array(get_class($this->owner), array_keys($mapping))) return null;

        $objectClass = $mapping[get_class($this->owner)];
        if (! $objectClass) return null;

        $filters = SitemapPage::config()->get('data_object_filters');
        $objectFilters = isset($filters[$objectClass]) && is_array($filters[$objectClass])
            ? $filters[$objectClass] :
            [];

        return $objectClass::get()->filter(array_merge($objectFilters, [
            'ShowInSitemap' => 1
        ]));
    }

    /**
     * Append the show in search field to a field list.
     */
    private function addShowInSitemapField(FieldList $fields)
    {
        $fields->addFieldToTab(
            'Root.Settings',
            CheckboxField::create(
                'ShowInSitemap',
                _t('SitemapDecorator.SHOWINSITEMAP', 'Show in sitemap?')
            ),
            'ShowInSearch'
        );
    }
}
