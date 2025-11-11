<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ModuleFinder\Business\Module\ModuleMatcher;

use Generated\Shared\Transfer\ModuleFilterTransfer;
use Generated\Shared\Transfer\ModuleTransfer;
use Generated\Shared\Transfer\OrganizationTransfer;
use Spryker\Shared\ModuleFinder\Transfer\Module;
use Spryker\Shared\ModuleFinder\Transfer\ModuleFilter;
use Spryker\Shared\ModuleFinder\Transfer\Organization;

class ModuleMatcher implements ModuleMatcherInterface
{
    public function matches(ModuleTransfer|Module $moduleTransfer, ModuleFilterTransfer|ModuleFilter $moduleFilterTransfer): bool
    {
        $accepted = true;

        if (!$this->matchesOrganization($moduleFilterTransfer, $moduleTransfer->getOrganization())) {
            $accepted = false;
        }
        if (!$this->matchesApplication($moduleFilterTransfer, $moduleTransfer)) {
            $accepted = false;
        }
        if (!$this->matchesModule($moduleFilterTransfer, $moduleTransfer)) {
            $accepted = false;
        }

        return $accepted;
    }

    protected function matchesOrganization(
        ModuleFilterTransfer|ModuleFilter $moduleFilterTransfer,
        OrganizationTransfer|Organization $organizationTransfer
    ): bool {
        if ($moduleFilterTransfer->getOrganization() === null) {
            return true;
        }

        return $this->match($moduleFilterTransfer->getOrganization()->getName(), $organizationTransfer->getName());
    }

    /**
     * Modules can hold several applications. We return true of one of the applications in the current module
     * matches the requested one.
     */
    protected function matchesApplication(ModuleFilterTransfer|ModuleFilter $moduleFilterTransfer, ModuleTransfer|Module $moduleTransfer): bool
    {
        if ($moduleFilterTransfer->getApplication() === null) {
            return true;
        }

        $applicationMatches = false;
        foreach ($moduleTransfer->getApplications() as $applicationTransfer) {
            if ($this->match($moduleFilterTransfer->getApplication()->getName(), $applicationTransfer->getName())) {
                $applicationMatches = true;
            }
        }

        return $applicationMatches;
    }

    /**
     * @return bool
     */
    protected function matchesModule(ModuleFilterTransfer|ModuleFilter $moduleFilterTransfer, ModuleTransfer|Module $moduleTransfer): bool
    {
        if ($moduleFilterTransfer->getModule() === null) {
            return true;
        }

        return $this->match($moduleFilterTransfer->getModule()->getName(), $moduleTransfer->getName());
    }

    protected function match(string $search, string $given): bool
    {
        if ($search === $given) {
            return true;
        }

        if (mb_strpos($search, '*') !== 0) {
            $search = '^' . $search;
        }

        if (mb_strpos($search, '*') === 0) {
            $search = mb_substr($search, 1);
        }

        if (mb_substr($search, -1) !== '*') {
            $search .= '$';
        }

        if (mb_substr($search, -1) === '*') {
            $search = mb_substr($search, 0, mb_strlen($search) - 1);
        }

        return (bool)preg_match(sprintf('/%s/', $search), $given);
    }
}
