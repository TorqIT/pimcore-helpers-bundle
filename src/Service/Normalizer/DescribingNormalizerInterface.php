<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use Nelmio\ApiDocBundle\ModelDescriber\ModelDescriberInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/** A normalizer which describes the OpenAPI schema of its own output, ahead of Nelmio's generic object describer */
#[AutoconfigureTag(self::TAG)]
#[AutoconfigureTag('nelmio_api_doc.model_describer', ['priority' => 10])]
interface DescribingNormalizerInterface extends NormalizerInterface, ModelDescriberInterface
{
    public const string TAG = 'torq.describing_normalizer';
}
