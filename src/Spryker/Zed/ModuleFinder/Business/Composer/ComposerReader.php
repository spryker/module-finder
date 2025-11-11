<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ModuleFinder\Business\Composer;

use Spryker\Zed\ModuleFinder\ModuleFinderConfig;

class ComposerReader implements ComposerReaderInterface
{
    /**
     * @var string
     */
    protected const COMPOSER_LOCK_FILE_NAME = 'composer.lock';

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $composerLock = null;

    public function __construct(protected ModuleFinderConfig $config)
    {
    }

    /**
     * @return array<string>
     */
    public function getInstalledPackageNames(): array
    {
        $packageNames = [];
        $composerLock = $this->getComposerLock();

        $packages = $composerLock['packages'] ?? [];
        $devPackages = $composerLock['packages-dev'] ?? [];

        $allPackages = array_merge($packages, $devPackages);

        foreach ($allPackages as $package) {
            if (isset($package['name'])) {
                $packageNames[] = $package['name'];
            }
        }

        return $packageNames;
    }

    /**
     * @return array<string>
     */
    public function getDevPackageNames(): array
    {
        $packageNames = [];
        $composerLock = $this->getComposerLock();

        if (!isset($composerLock['packages-dev'])) {
            return [];
        }

        foreach ($composerLock['packages-dev'] as $package) {
            if (isset($package['name'])) {
                $packageNames[] = $package['name'];
            }
        }

        return $packageNames;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getComposerLock(): array
    {
        if ($this->composerLock !== null) {
            return $this->composerLock;
        }

        $composerLockFilePath = $this->getComposerLockFilePath();
        if (!file_exists($composerLockFilePath)) {
            return [];
        }

        $composerLockFileContent = file_get_contents($composerLockFilePath);
        if (!$composerLockFileContent) {
            return [];
        }

        $this->composerLock = json_decode($composerLockFileContent, true);

        return $this->composerLock;
    }

    protected function getComposerLockFilePath(): string
    {
        return APPLICATION_ROOT_DIR . DIRECTORY_SEPARATOR . static::COMPOSER_LOCK_FILE_NAME;
    }
}
