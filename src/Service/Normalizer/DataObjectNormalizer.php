<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;


use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete as DataObject;
use stdClass;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Torq\PimcoreHelpersBundle\Model\Common\HelperContextBuilder;

#[AutoconfigureTag('serializer.normalizer.torq.data_object')]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class DataObjectNormalizer extends AbstractObjectNormalizer
{
    private const DEFAULT_CHILD_TYPES = [
        AbstractObject::OBJECT_TYPE_OBJECT,
        AbstractObject::OBJECT_TYPE_VARIANT,
        AbstractObject::OBJECT_TYPE_FOLDER,
    ];

    /** @param DataObject $data */
    protected function toStandardObject(mixed $data, ?string $format = null, array $context = []): stdClass
    {
        $inheritValues = $this->utils->get(HelperContextBuilder::INHERIT_VALUES, $context, true);
        $includeChildren = $this->utils->get(HelperContextBuilder::INCLUDE_CHILDREN, $context, false);
        $childTypes = $this->utils->get(HelperContextBuilder::CHILD_TYPES, $context, self::DEFAULT_CHILD_TYPES);

        // set for just this object
        if (!$inheritValues) {
            AbstractObject::setGetInheritedValues(false);
        }

        $object = parent::toStandardObject($data, $format, $context);

        if ($includeChildren) {
            $children = $data->getChildren($childTypes)->getData();
            $object->children = $children && count($children) > 0 ? $children : null;
        }

        // reset for next thing being serialized
        if (!$inheritValues) {
            AbstractObject::setGetInheritedValues(true);
        }

        return $object;
    }

    protected function getFields(object $data, ?string $format = null, array $context = []): array
    {
        $context = HelperContextBuilder::create()->withContext($context)->addExcludedFieldType('reverseObjectRelation');
        return parent::getFields($data, context: $context->toArray());
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof DataObject;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [DataObject::class => false];
    }
}