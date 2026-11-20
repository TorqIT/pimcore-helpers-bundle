<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use Pimcore\Model\DataObject\Data\BlockElement;
use stdClass;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Torq\PimcoreHelpersBundle\Model\Common\HelperContextBuilder;

#[AsAlias('torq.normalizer.block_element', public: true)]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class BlockElementNormalizer implements NormalizerInterface
{
    use RelationsAsIdsTrait;

    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')] private NormalizerInterface $normalizer
    ) {
    }

    /* @param BlockElement $data */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        $output = new stdClass();
        $relationsAsIds = $context[HelperContextBuilder::RELATIONS_AS_IDS] ?? false;
        $output->data = $relationsAsIds
            ? $this->relationsToIds($data->getType(), $data->getData())
            : $data->getData();
        return $this->normalizer->normalize($output, $format, $context);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof BlockElement;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [BlockElement::class => true];
    }
}