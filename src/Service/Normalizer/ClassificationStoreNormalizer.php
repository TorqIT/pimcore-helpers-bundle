<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use stdClass;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Torq\PimcoreHelpersBundle\Repository\GroupRepository;
use Torq\PimcoreHelpersBundle\Repository\KeyRepository;
use Torq\PimcoreHelpersBundle\Service\Utility\ArrayUtils;
use Torq\PimcoreHelpersBundle\Model\Common\HelperContextBuilder;

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
        $object = $this->toStandardObject($data, $format, $context);
        return !empty((array)$object) ? $this->normalizer->normalize($object, $format, $context) : null;
    }

    /** @param Classificationstore $data */
    protected function toStandardObject(mixed $data, ?string $format = null, array $context = []): stdClass
    {
        $inheritValues = $this->utils->get(HelperContextBuilder::INHERIT_VALUES, $context, true);
        $groupFilter = $this->utils->get(HelperContextBuilder::GROUP_FILTER, $context);
        $keyFilter = $this->utils->get(HelperContextBuilder::KEY_FILTERS, $context);

        // set for just this object
        if (!$inheritValues) {
            AbstractObject::setGetInheritedValues(false);
        }

        $object = new stdClass();
        foreach ($data->getItems() as $groupId => $keys) {
            $group = $this->groupRepository->getById($groupId);
            $groupName = $group?->getName();
            if ($group === null || $groupName === null || (is_callable($groupFilter) && !$groupFilter($group))) {
                continue;
            }
            $object->$groupName = new stdClass();
            foreach ($keys as $keyId => $valuesByLanguage) {
                $key = $this->keyRepository->getById($keyId);
                $keyName = $key?->getName();
                if ($key === null || $keyName === null || (is_callable($keyFilter) && !$keyFilter($key))) {
                    continue;
                }
                $value = $this->getValue($valuesByLanguage, $key, $group, $data, $format, $context);
                $object->$groupName->$keyName = $value;
            }
        }

        // reset for next thing being serialized
        if (!$inheritValues) {
            AbstractObject::setGetInheritedValues(true);
        }

        return $object;
    }

    protected function getValue(
        array $valuesByLanguage,
        KeyConfig $key,
        GroupConfig $group,
        Classificationstore $store,
        ?string $format = null,
        array $context = []
    ) {
        $language = $this->utils->get(HelperContextBuilder::LANGUAGE, $context, 'default');
        return $this->utils->get($language, $valuesByLanguage) ?? $this->utils->get('default', $valuesByLanguage);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Classificationstore;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Classificationstore::class => true];
    }
}