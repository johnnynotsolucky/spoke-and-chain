<?php
/**
 * @link http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license http://craftcms.com/license
 */

namespace craft\behaviors;

use yii\base\Behavior;

/**
 * Custom field behavior
 *
 * This class provides attributes for all the unique custom field handles.
 *
 * @method $this ariaLabel(mixed $value) Sets the [[ariaLabel]] property
 * @method $this bikeType(mixed $value) Sets the [[bikeType]] property
 * @method $this body(mixed $value) Sets the [[body]] property
 * @method $this body2(mixed $value) Sets the [[body2]] property
 * @method $this businessAddress(mixed $value) Sets the [[businessAddress]] property
 * @method $this businessHours(mixed $value) Sets the [[businessHours]] property
 * @method $this button(mixed $value) Sets the [[button]] property
 * @method $this buttonUrl(mixed $value) Sets the [[buttonUrl]] property
 * @method $this categoryLink(mixed $value) Sets the [[categoryLink]] property
 * @method $this color(mixed $value) Sets the [[color]] property
 * @method $this colors(mixed $value) Sets the [[colors]] property
 * @method $this contentBlocks(mixed $value) Sets the [[contentBlocks]] property
 * @method $this entryLink(mixed $value) Sets the [[entryLink]] property
 * @method $this equipmentType(mixed $value) Sets the [[equipmentType]] property
 * @method $this footerNav(mixed $value) Sets the [[footerNav]] property
 * @method $this form(mixed $value) Sets the [[form]] property
 * @method $this geometry(mixed $value) Sets the [[geometry]] property
 * @method $this heading(mixed $value) Sets the [[heading]] property
 * @method $this heading2(mixed $value) Sets the [[heading2]] property
 * @method $this heading3(mixed $value) Sets the [[heading3]] property
 * @method $this heading4(mixed $value) Sets the [[heading4]] property
 * @method $this icon(mixed $value) Sets the [[icon]] property
 * @method $this image(mixed $value) Sets the [[image]] property
 * @method $this imageAlt(mixed $value) Sets the [[imageAlt]] property
 * @method $this imageCaption(mixed $value) Sets the [[imageCaption]] property
 * @method $this intro(mixed $value) Sets the [[intro]] property
 * @method $this label(mixed $value) Sets the [[label]] property
 * @method $this mainImage(mixed $value) Sets the [[mainImage]] property
 * @method $this material(mixed $value) Sets the [[material]] property
 * @method $this pages(mixed $value) Sets the [[pages]] property
 * @method $this plainText1(mixed $value) Sets the [[plainText1]] property
 * @method $this plainText2(mixed $value) Sets the [[plainText2]] property
 * @method $this planFeatures(mixed $value) Sets the [[planFeatures]] property
 * @method $this product(mixed $value) Sets the [[product]] property
 * @method $this productCategories(mixed $value) Sets the [[productCategories]] property
 * @method $this productImages(mixed $value) Sets the [[productImages]] property
 * @method $this products(mixed $value) Sets the [[products]] property
 * @method $this seo(mixed $value) Sets the [[seo]] property
 * @method $this showForm(mixed $value) Sets the [[showForm]] property
 * @method $this stars(mixed $value) Sets the [[stars]] property
 * @method $this subheading(mixed $value) Sets the [[subheading]] property
 */
class CustomFieldBehavior extends Behavior
{
    /**
     * @var bool Whether the behavior should provide methods based on the field handles.
     */
    public bool $hasMethods = false;

    /**
     * @var bool Whether properties on the class should be settable directly.
     */
    public bool $canSetProperties = true;

    /**
     * @var array<string,bool> List of supported field handles.
     */
    public static $fieldHandles = [
        'ariaLabel' => true,
        'bikeType' => true,
        'body' => true,
        'body2' => true,
        'businessAddress' => true,
        'businessHours' => true,
        'button' => true,
        'buttonUrl' => true,
        'categoryLink' => true,
        'color' => true,
        'colors' => true,
        'contentBlocks' => true,
        'entryLink' => true,
        'equipmentType' => true,
        'footerNav' => true,
        'form' => true,
        'geometry' => true,
        'heading' => true,
        'heading2' => true,
        'heading3' => true,
        'heading4' => true,
        'icon' => true,
        'image' => true,
        'imageAlt' => true,
        'imageCaption' => true,
        'intro' => true,
        'label' => true,
        'mainImage' => true,
        'material' => true,
        'pages' => true,
        'plainText1' => true,
        'plainText2' => true,
        'planFeatures' => true,
        'product' => true,
        'productCategories' => true,
        'productImages' => true,
        'products' => true,
        'seo' => true,
        'showForm' => true,
        'stars' => true,
        'subheading' => true,
    ];

    /**
     * @var string|null Value for field with the handle “ariaLabel”.
     */
    public $ariaLabel;

    /**
     * @var \craft\elements\db\CategoryQuery|\craft\elements\ElementCollection<\craft\elements\Category> Value for field with the handle “bikeType”.
     */
    public $bikeType;

    /**
     * @var string Value for field with the handle “body”.
     */
    public $body;

    /**
     * @var string Value for field with the handle “body2”.
     */
    public $body2;

    /**
     * @var string|null Value for field with the handle “businessAddress”.
     */
    public $businessAddress;

    /**
     * @var mixed Value for field with the handle “businessHours”.
     */
    public $businessHours;

    /**
     * @var \craft\elements\db\EntryQuery|\craft\elements\ElementCollection<\craft\elements\Entry> Value for field with the handle “button”.
     */
    public $button;

    /**
     * @var string|null Value for field with the handle “buttonUrl”.
     */
    public $buttonUrl;

    /**
     * @var \craft\elements\db\CategoryQuery|\craft\elements\ElementCollection<\craft\elements\Category> Value for field with the handle “categoryLink”.
     */
    public $categoryLink;

    /**
     * @var \craft\fields\data\ColorData|null Value for field with the handle “color”.
     */
    public $color;

    /**
     * @var \craft\elements\db\CategoryQuery|\craft\elements\ElementCollection<\craft\elements\Category> Value for field with the handle “colors”.
     */
    public $colors;

    /**
     * @var \craft\elements\db\EntryQuery|\craft\elements\ElementCollection<\craft\elements\Entry> Value for field with the handle “contentBlocks”.
     */
    public $contentBlocks;

    /**
     * @var \craft\elements\db\EntryQuery|\craft\elements\ElementCollection<\craft\elements\Entry> Value for field with the handle “entryLink”.
     */
    public $entryLink;

    /**
     * @var \craft\elements\db\CategoryQuery|\craft\elements\ElementCollection<\craft\elements\Category> Value for field with the handle “equipmentType”.
     */
    public $equipmentType;

    /**
     * @var \craft\elements\db\EntryQuery|\craft\elements\ElementCollection<\craft\elements\Entry> Value for field with the handle “footerNav”.
     */
    public $footerNav;

    /**
     * @var mixed Value for field with the handle “form”.
     */
    public $form;

    /**
     * @var array|null Value for field with the handle “geometry”.
     */
    public $geometry;

    /**
     * @var string|null Value for field with the handle “heading”.
     */
    public $heading;

    /**
     * @var string|null Value for field with the handle “heading2”.
     */
    public $heading2;

    /**
     * @var string|null Value for field with the handle “heading3”.
     */
    public $heading3;

    /**
     * @var string|null Value for field with the handle “heading4”.
     */
    public $heading4;

    /**
     * @var \craft\elements\db\AssetQuery|\craft\elements\ElementCollection<\craft\elements\Asset> Value for field with the handle “icon”.
     */
    public $icon;

    /**
     * @var \craft\elements\db\AssetQuery|\craft\elements\ElementCollection<\craft\elements\Asset> Value for field with the handle “image”.
     */
    public $image;

    /**
     * @var string|null Value for field with the handle “imageAlt”.
     */
    public $imageAlt;

    /**
     * @var string|null Value for field with the handle “imageCaption”.
     */
    public $imageCaption;

    /**
     * @var bool Value for field with the handle “intro”.
     */
    public $intro;

    /**
     * @var string|null Value for field with the handle “label”.
     */
    public $label;

    /**
     * @var \craft\elements\db\AssetQuery|\craft\elements\ElementCollection<\craft\elements\Asset> Value for field with the handle “mainImage”.
     */
    public $mainImage;

    /**
     * @var \craft\elements\db\CategoryQuery|\craft\elements\ElementCollection<\craft\elements\Category> Value for field with the handle “material”.
     */
    public $material;

    /**
     * @var \craft\elements\db\EntryQuery|\craft\elements\ElementCollection<\craft\elements\Entry> Value for field with the handle “pages”.
     */
    public $pages;

    /**
     * @var string|null Value for field with the handle “plainText1”.
     */
    public $plainText1;

    /**
     * @var string|null Value for field with the handle “plainText2”.
     */
    public $plainText2;

    /**
     * @var array|null Value for field with the handle “planFeatures”.
     */
    public $planFeatures;

    /**
     * @var \craft\elements\db\ElementQueryInterface|\craft\elements\ElementCollection<\craft\base\ElementInterface> Value for field with the handle “product”.
     */
    public $product;

    /**
     * @var \craft\elements\db\CategoryQuery|\craft\elements\ElementCollection<\craft\elements\Category> Value for field with the handle “productCategories”.
     */
    public $productCategories;

    /**
     * @var \craft\elements\db\AssetQuery|\craft\elements\ElementCollection<\craft\elements\Asset> Value for field with the handle “productImages”.
     */
    public $productImages;

    /**
     * @var \craft\elements\db\ElementQueryInterface|\craft\elements\ElementCollection<\craft\base\ElementInterface> Value for field with the handle “products”.
     */
    public $products;

    /**
     * @var \nystudio107\seomatic\models\MetaBundle Value for field with the handle “seo”.
     */
    public $seo;

    /**
     * @var bool Value for field with the handle “showForm”.
     */
    public $showForm;

    /**
     * @var mixed Value for field with the handle “stars”.
     */
    public $stars;

    /**
     * @var string|null Value for field with the handle “subheading”.
     */
    public $subheading;

    /**
     * @var array Additional custom field values we don’t know about yet.
     */
    private array $_customFieldValues = [];

    /**
     * @inheritdoc
     */
    public function __call($name, $params)
    {
        if ($this->hasMethods && isset(self::$fieldHandles[$name]) && count($params) === 1) {
            $this->$name = $params[0];
            return $this->owner;
        }
        return parent::__call($name, $params);
    }

    /**
     * @inheritdoc
     */
    public function hasMethod($name): bool
    {
        if ($this->hasMethods && isset(self::$fieldHandles[$name])) {
            return true;
        }
        return parent::hasMethod($name);
    }

    /**
     * @inheritdoc
     */
    public function __isset($name): bool
    {
        if (isset(self::$fieldHandles[$name])) {
            return true;
        }
        return parent::__isset($name);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        if (isset(self::$fieldHandles[$name])) {
            return $this->_customFieldValues[$name] ?? null;
        }
        return parent::__get($name);
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        if (isset(self::$fieldHandles[$name])) {
            $this->_customFieldValues[$name] = $value;
            return;
        }
        parent::__set($name, $value);
    }

    /**
     * @inheritdoc
     */
    public function canGetProperty($name, $checkVars = true): bool
    {
        if ($checkVars && isset(self::$fieldHandles[$name])) {
            return true;
        }
        return parent::canGetProperty($name, $checkVars);
    }

    /**
     * @inheritdoc
     */
    public function canSetProperty($name, $checkVars = true): bool
    {
        if (!$this->canSetProperties) {
            return false;
        }
        if ($checkVars && isset(self::$fieldHandles[$name])) {
            return true;
        }
        return parent::canSetProperty($name, $checkVars);
    }
}
