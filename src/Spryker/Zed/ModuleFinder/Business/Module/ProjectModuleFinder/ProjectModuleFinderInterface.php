<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ModuleFinder\Business\Module\ProjectModuleFinder;

use Generated\Shared\Transfer\ModuleFilterTransfer;
use Spryker\Shared\ModuleFinder\Transfer\ModuleFilter;

interface ProjectModuleFinderInterface
{
    public function getProjectModules(ModuleFilterTransfer|ModuleFilter|null $moduleFilterTransfer = null): array;
}
