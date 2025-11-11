<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\ModuleFinder\Transfer;

class ModuleFilter
{
    protected ?Organization $organization = null;

    protected ?Application $application = null;

    protected ?Module $module = null;

    protected ?bool $isProjectOnly = null;

    public function setIsProjectOnly(bool $isProjectOnly): self
    {
        $this->isProjectOnly = $isProjectOnly;

        return $this;
    }

    public function getIsProjectOnly(): ?bool
    {
        return $this->isProjectOnly;
    }

    public function setOrganization(Organization $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setApplication(Application $application): self
    {
        $this->application = $application;

        return $this;
    }

    public function getApplication(): ?Application
    {
        return $this->application;
    }

    public function setModule(Module $module): self
    {
        $this->module = $module;

        return $this;
    }

    public function getModule(): ?Module
    {
        return $this->module;
    }
}
