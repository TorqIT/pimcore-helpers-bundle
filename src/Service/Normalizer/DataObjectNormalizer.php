<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations\Items;
use OpenApi\Annotations\Schema;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete as DataObject;
use stdClass;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Torq\PimcoreHelpersBundle\Model\Common\HelperContextBuilder;

#[AsAlias('torq.normalizer.data_object', public: true)]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class DataObjectNormalizer extends AbstractObjectNormalizer
{
    private const array DEFAULT_CHILD_TYPES = [
        AbstractObject::OBJECT_TYPE_OBJECT,
        AbstractObject::OBJECT_TYPE_VARIANT,
        AbstractObject::OBJECT_TYPE_FOLDER,
    ];

    public function getDefaultContext(array $context): array
    {
        $context = [
            HelperContextBuilder::INHERIT_VALUES => true,
            HelperContextBuilder::INCLUDE_CHILDREN => false,
            HelperContextBuilder::CHILD_TYPES => self::DEFAULT_CHILD_TYPES,
            ...parent::getDefaultContext($context),
        ];
        if ($this->utils->get(HelperContextBuilder::RELATIONS_AS_IDS, $context)) {
            return $context;
        }

        return HelperContextBuilder::create()
            ->withContext($context)
            ->addExcludedFieldType('reverseObjectRelation')
            ->toArray();
    }

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

    public function describe(Model $model, Schema $schema): void
    {
        parent::describe($model, $schema);

        $context = $this->getDefaultContext($model->getSerializationContext());
        if (!$context[HelperContextBuilder::INCLUDE_CHILDREN]) {
            return;
        }

        $children = Util::getProperty($schema, 'children');
        $children->type = 'array';
        $children->nullable = true;
        $children->items = new Items(['_context' => Util::createWeakContext($children->_context)]);
        if ($context[HelperContextBuilder::CHILD_TYPES] !== [AbstractObject::OBJECT_TYPE_VARIANT]) {
            $children->items->type = 'object';
            return;
        }

        // Variants share their parent's class, so they reference this same model
        $this->describeModel(
            $model->getTypeInfo()->getClassName(),
            $children->items,
            $model->getSerializationContext(),
        );
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
