<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ModuleFinder\Business\Module\ModuleFinder;

use Generated\Shared\Transfer\ModuleFilterTransfer;
use Spryker\Shared\ModuleFinder\Transfer\ModuleFilter;

interface ModuleFinderInterface
{
    /**
     * @return array<\Generated\Shared\Transfer\ModuleTransfer|\Spryker\Shared\ModuleFinder\Transfer\Module>
     */
    public function getModules(ModuleFilterTransfer|ModuleFilter|null $moduleFilterTransfer = null): array;
}
