<?php

namespace Torq\PimcoreHelpersBundle\Repository;

use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Torq\PimcoreHelpersBundle\Model\Asset\AssetMetadata;
use Torq\PimcoreHelpersBundle\Model\Common\HelperContextBuilder;
use Torq\PimcoreHelpersBundle\Service\Normalizer\AssetMetadataNormalizer;

class AssetMetadataRepository
{
    public function __construct(
        #[Autowire(service: 'torq.normalizer.asset_metadata')] private AssetMetadataNormalizer $normalizer,
        private Connection $connection,
    ) {
    }

    public function getByAssetId(?int $assetId)
    {
        if ($assetId === null) {
            return [];
        }

        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')->from('assets_metadata', 'metadata');
        $qb->where('cid = :assetId')->setParameter('assetId', $assetId);
        $data = $qb->executeQuery()->fetchAllAssociative();
        return array_map(fn(array $d) => $this->hydrate($d), $data);
    }

    /**
     * @template T of array{name: string, language: string, type: string, data: string}
     * @param T $data
     */
    public function hydrate(array $data, ?AssetMetadata $metadata = null): AssetMetadata
    {
        $context = HelperContextBuilder::create();
        $context = $context->withObjectToPopulate($metadata ?? new AssetMetadata());
        $context = $context->valuesAreSerialized();
        $context = $context->toArray();

        return $this->normalizer->denormalize($data, AssetMetadata::class, context: $context);
    }
}