<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\ModuleFinder\Transfer;

class Module
{
    protected string $name;

    protected string $nameDashed;

    protected string $path;

    protected bool $isStandalone;

    protected bool $isProjectOnly = true;

    /**
     * @var array<\Spryker\Shared\ModuleFinder\Transfer\Module>
     */
    protected array $usedModules = [];

    protected Organization $organization;

    /**
     * @var array<\Spryker\Shared\ModuleFinder\Transfer\Application>
     */
    protected array $applications = [];

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setNameDashed(string $nameDashed): self
    {
        $this->nameDashed = $nameDashed;

        return $this;
    }

    public function getNameDashed(): string
    {
        return $this->nameDashed;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setIsStandalone(bool $isStandalone): self
    {
        $this->isStandalone = $isStandalone;

        return $this;
    }

    public function getIsStandalone(): bool
    {
        return $this->isStandalone;
    }

    public function setIsProjectOnly(bool $isProjectOnly): self
    {
        $this->isProjectOnly = $isProjectOnly;

        return $this;
    }

    public function getIsProjectOnly(): bool
    {
        return $this->isProjectOnly;
    }

    /**
     * @param array<\Spryker\Shared\ModuleFinder\Transfer\Module> $usedModules
     *
     * @return $this
     */
    public function setUsedModules(array $usedModules)
    {
        $this->usedModules = $usedModules;

        return $this;
    }

    /**
     * @return array<\Spryker\Shared\ModuleFinder\Transfer\Module>
     */
    public function getUsedModules(): array
    {
        return $this->usedModules;
    }

    public function addUsedModule(self $usedModule): self
    {
        $this->usedModules[] = $usedModule;

        return $this;
    }

    public function setOrganization(Organization $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function addApplication(Application $application): self
    {
        $this->applications[] = $application;

        return $this;
    }

    /**
     * @return array<\Spryker\Shared\ModuleFinder\Transfer\Application>
     */
    public function getApplications(): array
    {
        return $this->applications;
    }
}
