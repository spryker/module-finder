<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ModuleFinder\Business;

use Generated\Shared\Transfer\ModuleFilterTransfer;
use Spryker\Shared\ModuleFinder\Transfer\ModuleFilter;

interface ModuleFinderFacadeInterface
{
    /**
     * Specification:
     * - Gets all modules.
     * - Creates an array of ModuleTransfer objects.
     * - The key of the returned array is `OrganizationName.ModuleName`.
     * - A ModuleFilterTransfer can be used to filter the returned collection.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ModuleFilterTransfer|null $moduleFilterTransfer
     *
     * @return array<\Generated\Shared\Transfer\ModuleTransfer>
     */
    public function getModules(?ModuleFilterTransfer $moduleFilterTransfer = null): array;

    /**
     * Specification:
     * - Finds all project modules.
     *
     * @api
     *
     * @return array<\Generated\Shared\Transfer\ModuleTransfer>
     */
    public function getProjectModules(ModuleFilterTransfer|ModuleFilter|null $moduleFilterTransfer = null): array;

    /**
     * Specification:
     * - Returns a list of packages defined in the Spryker namespace.
     * - Packages are not spryker modules.
     *
     * @api
     *
     * @internal
     *
     * @return array<\Generated\Shared\Transfer\PackageTransfer>
     */
    public function getPackages(): array;

    /**
     * Specification:
     * - Returns a list of all installed composer packages.
     *
     * @api
     *
     * @return array<string>
     */
    public function getInstalledPackageNames(): array;

    /**
     * Specification:
     * - Returns a list of all composer packages installed in dev mode.
     *
     * @api
     *
     * @return array<string>
     */
    public function getDevPackageNames(): array;
}
