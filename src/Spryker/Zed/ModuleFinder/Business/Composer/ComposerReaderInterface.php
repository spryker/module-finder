<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ModuleFinder\Business\Composer;

interface ComposerReaderInterface
{
    /**
     * @return array<string>
     */
    public function getInstalledPackageNames(): array;

    /**
     * @return array<string>
     */
    public function getDevPackageNames(): array;
}
