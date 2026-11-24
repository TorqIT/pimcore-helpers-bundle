# Changelog

## 4.0.0

Adds OpenAPI model describers to the normalizers. This is a breaking change for projects with custom normalizers.

**Breaking**

* `nelmio/api-doc-bundle` and `symfony/type-info` are now required.
* `AbstractObjectNormalizer` and `AssetNormalizer` take a `PimcoreModelDescriber` as their last constructor argument.
  A subclass which defines its own constructor has to accept it and pass it to the parent.
  Until it does, the API documentation and the console fail as well as normalization, because Nelmio constructs
  every describing normalizer.
* `AbstractObjectNormalizer::getFields()` is private. To change which fields are normalized, override
  `addRequiredContext()` instead.
* The remaining normalizers which now describe their output also take a `PimcoreModelDescriber`. This only matters
  if you construct them by hand: `CarbonPeriodNormalizer`, `ElementMetadataNormalizer`, `HotspotImageNormalizer`,
  `ImageGalleryNormalizer`, `ListingNormalizer`, `NumericRangeNormalizer`, `ObjectMetadataNormalizer`,
  `QuantityValueNormalizer`, `QuantityValueRangeNormalizer` and `VideoNormalizer`.
* With `NelmioApiDocBundle` registered, a data object or asset used as a `Model` is now described by its normalizer
  instead of Nelmio's generic object describer, which changes the generated documentation for it.

**Changed**

* `relationsAsIds()` now applies to relations inside blocks.
* `relationsAsIds()` on advanced many-to-many relations returns the IDs of the related elements.
* `HelperContextBuilder::addExcludedField()`, `addIncludedFieldType()` and `addExcludedFieldType()` ignore a value
  which was already added.
* `ArrayField` declares its value as `string[]|null`. Generated class docblocks change the next time classes are
  rebuilt.
* `FieldFetcher` accepts the class name of an object brick, and `FieldFetcher::toObject()` is public.
