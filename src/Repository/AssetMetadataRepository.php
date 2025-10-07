<?php

namespace Torq\PimcoreHelpersBundle\Repository;

use Torq\PimcoreHelpersBundle\Model\Asset\AssetMetadata;
use Torq\PimcoreHelpersBundle\Service\Normalizer\AssetMetadataNormalizer;
use Doctrine\DBAL\Connection;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class AssetMetadataRepository
{
    public function __construct(
        private AssetMetadataNormalizer $normalizer,
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
        $metadata = $metadata ?? new AssetMetadata();
        return $this->normalizer->denormalize($data, AssetMetadata::class, context: [
            AbstractNormalizer::OBJECT_TO_POPULATE => $metadata,
            AssetMetadataNormalizer::IS_VALUE_SERIALIZED => true
        ]);
    }
}