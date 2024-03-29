<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ModuleFinder\Business\Module\ProjectModuleFinder;

use Generated\Shared\Transfer\ApplicationTransfer;
use Generated\Shared\Transfer\ModuleFilterTransfer;
use Generated\Shared\Transfer\ModuleTransfer;
use Generated\Shared\Transfer\OrganizationTransfer;
use Spryker\Zed\ModuleFinder\Business\Module\ModuleMatcher\ModuleMatcherInterface;
use Spryker\Zed\ModuleFinder\ModuleFinderConfig;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ProjectModuleFinder implements ProjectModuleFinderInterface
{
    /**
     * @var \Spryker\Zed\ModuleFinder\ModuleFinderConfig
     */
    protected $config;

    /**
     * @var \Spryker\Zed\ModuleFinder\Business\Module\ModuleMatcher\ModuleMatcherInterface
     */
    protected $moduleMatcher;

    /**
     * @param \Spryker\Zed\ModuleFinder\ModuleFinderConfig $config
     * @param \Spryker\Zed\ModuleFinder\Business\Module\ModuleMatcher\ModuleMatcherInterface $moduleMatcher
     */
    public function __construct(ModuleFinderConfig $config, ModuleMatcherInterface $moduleMatcher)
    {
        $this->config = $config;
        $this->moduleMatcher = $moduleMatcher;
    }

    /**
     * @param \Generated\Shared\Transfer\ModuleFilterTransfer|null $moduleFilterTransfer
     *
     * @return array<\Generated\Shared\Transfer\ModuleTransfer>
     */
    public function getProjectModules(?ModuleFilterTransfer $moduleFilterTransfer = null): array
    {
        $moduleCollection = [];

        $projectDirectories = $this->getProjectDirectories();

        if (count($projectDirectories) === 0) {
            return $moduleCollection;
        }

        foreach ($this->getProjectModuleFinder($projectDirectories) as $directoryInfo) {
            $moduleTransfer = $this->getModuleTransfer($directoryInfo);
            if (isset($moduleCollection[$this->buildOrganizationModuleKey($moduleTransfer)])) {
                $moduleTransfer = $moduleCollection[$this->buildOrganizationModuleKey($moduleTransfer)];
            }

            $applicationTransfer = $this->buildApplicationTransferFromDirectoryInformation($directoryInfo);
            $moduleTransfer->addApplication($applicationTransfer);

            if ($moduleFilterTransfer !== null && !$this->moduleMatcher->matches($moduleTransfer, $moduleFilterTransfer)) {
                continue;
            }

            $moduleCollection[$this->buildOrganizationModuleKey($moduleTransfer)] = $moduleTransfer;
        }

        ksort($moduleCollection);

        return $moduleCollection;
    }

    /**
     * @return array
     */
    protected function getProjectDirectories(): array
    {
        $projectOrganizationModuleDirectories = [];
        foreach ($this->config->getApplications() as $application) {
            $projectOrganizationModuleDirectories[] = sprintf('%s/*/%s/', APPLICATION_SOURCE_DIR, $application);
        }

        return array_filter($projectOrganizationModuleDirectories, 'glob');
    }

    /**
     * @param array $projectOrganizationModuleDirectories
     *
     * @return \Symfony\Component\Finder\Finder<\Symfony\Component\Finder\SplFileInfo>
     */
    protected function getProjectModuleFinder(array $projectOrganizationModuleDirectories): Finder
    {
        $finder = new Finder();
        $finder
            ->directories()
            ->depth('== 0')
            ->in($projectOrganizationModuleDirectories)
            ->sort($this->getFilenameSortCallback());

        return $finder;
    }

    /**
     * @return callable
     */
    protected function getFilenameSortCallback(): callable
    {
        return function (SplFileInfo $fileOne, SplFileInfo $fileTwo) {
            return strcmp((string)$fileOne->getRealpath(), (string)$fileTwo->getRealpath());
        };
    }

    /**
     * @param \Symfony\Component\Finder\SplFileInfo $directoryInfo
     *
     * @return \Generated\Shared\Transfer\ModuleTransfer
     */
    protected function getModuleTransfer(SplFileInfo $directoryInfo): ModuleTransfer
    {
        $moduleTransfer = $this->buildModuleTransferFromDirectoryInformation($directoryInfo);
        $moduleTransfer->setOrganization($this->buildOrganizationTransferFromDirectoryInformation($directoryInfo));

        return $moduleTransfer;
    }

    /**
     * @param \Symfony\Component\Finder\SplFileInfo $directoryInfo
     *
     * @return \Generated\Shared\Transfer\ModuleTransfer
     */
    protected function buildModuleTransferFromDirectoryInformation(SplFileInfo $directoryInfo): ModuleTransfer
    {
        $moduleName = $this->getModuleNameFromDirectory($directoryInfo);
        $moduleTransfer = new ModuleTransfer();
        $moduleTransfer
            ->setName($moduleName)
            ->setPath(dirname(APPLICATION_SOURCE_DIR) . DIRECTORY_SEPARATOR);

        return $moduleTransfer;
    }

    /**
     * @param \Symfony\Component\Finder\SplFileInfo $directoryInfo
     *
     * @return \Generated\Shared\Transfer\OrganizationTransfer
     */
    protected function buildOrganizationTransferFromDirectoryInformation(SplFileInfo $directoryInfo): OrganizationTransfer
    {
        $organizationName = $this->getOrganizationNameFromDirectory($directoryInfo);
        $organizationTransfer = new OrganizationTransfer();
        $organizationTransfer
            ->setName($organizationName)
            ->setIsProject(true);

        return $organizationTransfer;
    }

    /**
     * @param \Symfony\Component\Finder\SplFileInfo $directoryInfo
     *
     * @return \Generated\Shared\Transfer\ApplicationTransfer
     */
    protected function buildApplicationTransferFromDirectoryInformation(SplFileInfo $directoryInfo): ApplicationTransfer
    {
        $applicationName = $this->getApplicationNameFromDirectory($directoryInfo);
        $applicationTransfer = new ApplicationTransfer();
        $applicationTransfer->setName($applicationName);

        return $applicationTransfer;
    }

    /**
     * @param \Symfony\Component\Finder\SplFileInfo $directoryInfo
     *
     * @return string
     */
    protected function getOrganizationNameFromDirectory(SplFileInfo $directoryInfo): string
    {
        $pathFragments = explode(DIRECTORY_SEPARATOR, (string)$directoryInfo->getRealPath());
        $srcPosition = array_search('src', $pathFragments);

        $organizationName = $pathFragments[$srcPosition + 1];

        return $organizationName;
    }

    /**
     * @param \Symfony\Component\Finder\SplFileInfo $directoryInfo
     *
     * @return string
     */
    protected function getApplicationNameFromDirectory(SplFileInfo $directoryInfo): string
    {
        $pathFragments = explode(DIRECTORY_SEPARATOR, (string)$directoryInfo->getRealPath());
        $srcPosition = array_search('src', $pathFragments);

        $organizationName = $pathFragments[$srcPosition + 2];

        return $organizationName;
    }

    /**
     * @param \Symfony\Component\Finder\SplFileInfo $directoryInfo
     *
     * @return string
     */
    protected function getModuleNameFromDirectory(SplFileInfo $directoryInfo): string
    {
        return $directoryInfo->getRelativePathname();
    }

    /**
     * @param \Generated\Shared\Transfer\ModuleTransfer $moduleTransfer
     *
     * @return string
     */
    protected function buildOrganizationModuleKey(ModuleTransfer $moduleTransfer): string
    {
        return sprintf('%s.%s', $moduleTransfer->getOrganization()->getName(), $moduleTransfer->getName());
    }
}
