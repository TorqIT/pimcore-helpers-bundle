<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData as ObjectBrickData;
use stdClass;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Torq\PimcoreHelpersBundle\Model\Common\HelperContextBuilder;
use Torq\PimcoreHelpersBundle\Service\Utility\ArrayUtils;

#[AsAlias('torq.normalizer.object_brick', public: true)]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class ObjectBrickNormalizer implements NormalizerInterface
{
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
        $inheritedValues = $this->utils->get(HelperContextBuilder::INHERIT_VALUES, $context, false);

        /** @var ObjectBrickData[] $bricks */
        $bricks = $data->getItems($inheritedValues);
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