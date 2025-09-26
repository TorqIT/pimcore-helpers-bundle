<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use Torq\PimcoreHelpersBundle\Repository\GroupRepository;
use Torq\PimcoreHelpersBundle\Repository\KeyRepository;
use ArrayObject;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Concrete as DataObject;
use stdClass;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Torq\PimcoreHelpersBundle\Service\Utility\ArrayUtils;

#[AutoconfigureTag('serializer.normalizer.torq.classification_store')]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class ClassificationStoreNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')] private NormalizerInterface $normalizer,
        private ArrayUtils $utils,
        private GroupRepository $groupRepository,
        private KeyRepository $keyRepository
    ) {
    }

    /** @param Classificationstore $data */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        $language = $this->utils->get(AbstractObjectNormalizer::LANGUAGE, $context, 'default');

        $output = new stdClass();
        foreach ($data->getItems() as $groupId => $keys) {
            $group = $this->groupRepository->getById($groupId);
            $groupName = $group?->getName();
            if ($group === null || $groupName === null) {
                continue;
            }
            $output->$groupName = [];
            foreach ($keys as $keyId => $languages) {
                $key = $this->keyRepository->getById($keyId);
                $keyName = $key?->getName();
                if ($key === null || $keyName === null) {
                    continue;
                }
                $value = $this->utils->get($language, $languages) ?? $this->utils->get('default', $languages);
                $value = $this->getNormalizedValue($value, $language, $key, $group, $data, $format, $context);
                $output->$groupName[] = $value;
            }
        }
        return !empty((array)$output) ?  $this->normalizer->normalize($output, $format, $context) : null;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Classificationstore;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Classificationstore::class => true];
    }

    /** Overridable function for operating on field value prior to inclusion in normalized output */
    protected function getNormalizedValue(
        mixed $value,
        string $language,
        KeyConfig $key,
        GroupConfig $group,
        Classificationstore $store,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        return $value;
    }
}