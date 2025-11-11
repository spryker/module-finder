<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ModuleFinder\Business\Module\ModuleMatcher;

use Generated\Shared\Transfer\ModuleFilterTransfer;
use Generated\Shared\Transfer\ModuleTransfer;
use Spryker\Shared\ModuleFinder\Transfer\Module;
use Spryker\Shared\ModuleFinder\Transfer\ModuleFilter;

interface ModuleMatcherInterface
{
    public function matches(ModuleTransfer|Module $moduleTransfer, ModuleFilterTransfer|ModuleFilter $moduleFilterTransfer): bool;
}
