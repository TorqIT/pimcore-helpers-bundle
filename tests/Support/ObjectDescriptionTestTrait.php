<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Tests\Support;

use Pimcore\Model\DataObject\ClassDefinition\Data as FieldDef;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Torq\PimcoreHelpersBundle\Service\Common\FieldFetcher;
use Torq\PimcoreHelpersBundle\Service\Normalizer\DataObjectNormalizer;
use Torq\PimcoreHelpersBundle\Service\Utility\ArrayUtils;

/** Describes objects whose fields come from a stubbed FieldFetcher, so no class definitions are needed */
trait ObjectDescriptionTestTrait
{
    use DescriberTestTrait;

    /**
     * @param array<string, FieldDef|null> $definitions field definitions by field name, null for a field without one
     * @return array<string, array> the described properties by field name
     */
    private function describeFields(array $definitions, array $context = []): array
    {
        $normalizer = new DataObjectNormalizer(
            $this->createStub(NormalizerInterface::class),
            new ArrayUtils(),
            $this->createFieldFetcher($definitions),
            $this->describer,
        );
        $this->registerNormalizers($normalizer);

        return $this->describe($normalizer, Concrete::class, $context)['properties'];
    }

    /** @param array<string, FieldDef|null> $definitions */
    private function createFieldFetcher(array $definitions): FieldFetcher
    {
        $fieldFetcher = $this->createStub(FieldFetcher::class);
        $fieldFetcher->method('toObject')->willReturn($this->createStub(Concrete::class));
        $fieldFetcher->method('getFields')->willReturn(array_keys($definitions));
        $fieldFetcher->method('getFieldDefinition')->willReturnCallback(
            fn(object $object, string $field) => $definitions[$field],
        );

        return $fieldFetcher;
    }

    /** Pimcore generates these classes per project; the describer only ever builds their names */
    private static function aliasOnce(string $class, string $alias): void
    {
        if (!class_exists($alias, false)) {
            class_alias($class, $alias);
        }
    }
}
