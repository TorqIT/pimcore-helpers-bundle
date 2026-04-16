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

    public function getForAssetId(?int $assetId)
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

    public function getByName(?int $assetId, ?string $name): ?AssetMetadata
    {
        if ($assetId === null || $name === null) {
            return null;
        }

        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')->from('assets_metadata', 'metadata');
        $qb->where('cid = :assetId')->setParameter('assetId', $assetId);
        $qb->andWhere('name = :name')->setParameter('name', $name);
        if ($data = $qb->executeQuery()->fetchAllAssociative()[0] ?? null) {
            return $this->hydrate($data);
        } else {
            return null;
        }
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