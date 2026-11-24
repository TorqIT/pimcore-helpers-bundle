<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use Nelmio\ApiDocBundle\Model\Model;
use Torq\PimcoreHelpersBundle\Service\Describer\PimcoreModelDescriber;

/** Supports the models of the types the normalizer already declares it normalizes */
trait PimcoreModelSupportTrait
{
    /** Injected through the constructor of the using class */
    protected PimcoreModelDescriber $describer;

    public function supports(Model $model): bool
    {
        return $this->describer->isSupportedBy($this, $model);
    }
}
