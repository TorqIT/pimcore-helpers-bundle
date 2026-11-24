<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use Nelmio\ApiDocBundle\Model\Model;
use OpenApi\Annotations\Schema;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use stdClass;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Torq\PimcoreHelpersBundle\Service\Describer\PimcoreModelDescriber;

#[AsAlias('torq.normalizer.element_metadata', public: true)]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class ElementMetadataNormalizer implements DescribingNormalizerInterface
{
    use PimcoreModelSupportTrait;

    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')] private NormalizerInterface $normalizer,
        protected PimcoreModelDescriber $describer,
    ) {
    }

    /* @param ElementMetadata $data */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        $output = new stdClass();
        $output->element = $data->getElement();
        foreach ($data->getData() as $column => $value) {
            $output->$column = $value;
        }
        return $this->normalizer->normalize($output, $format, $context);
    }

    public function describe(Model $model, Schema $schema): void
    {
        $this->describer->describeObject($schema, ['element' => 'object'], nullable: false);
        $schema->additionalProperties = true;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof ElementMetadata;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [ElementMetadata::class => true];
    }
}