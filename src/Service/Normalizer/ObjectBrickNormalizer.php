<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use Torq\PimcoreHelpersBundle\Service\Common\DataObjectFieldFetcher;
use ArrayObject;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData as ObjectBrickData;
use stdClass;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Torq\PimcoreHelpersBundle\Service\Utility\ArrayUtils;

#[AutoconfigureTag('serializer.normalizer.torq.object_brick')]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class ObjectBrickNormalizer implements NormalizerInterface
{
    public const WITH_INHERITED_VALUES = 'withInheritedValues';

    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')] protected NormalizerInterface $normalizer,
        protected ArrayUtils $utils,
    ) {
    }

    /** @param Objectbrick $data */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        $withInheritedValues = $this->utils->get(self::WITH_INHERITED_VALUES, $context, false);

        /** @var ObjectBrickData[] $bricks */
        $bricks = $data->getItems($withInheritedValues);
        $output = new stdClass();
        foreach ($bricks as $brick) {
            $brickName = $brick->getType();
            $output->$brickName = $brick;
        }
        return $this->normalizer->normalize($output, $format, $context);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Objectbrick;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Objectbrick::class => false];
    }
}