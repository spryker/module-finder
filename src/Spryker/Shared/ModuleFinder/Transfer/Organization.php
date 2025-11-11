<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\ModuleFinder\Transfer;

class Organization
{
    protected string $name;

    protected string $nameDashed;

    protected bool $isProject;

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

    public function setIsProject(bool $isProject): self
    {
        $this->isProject = $isProject;

        return $this;
    }

    public function getIsProject(): bool
    {
        return $this->isProject;
    }
}
