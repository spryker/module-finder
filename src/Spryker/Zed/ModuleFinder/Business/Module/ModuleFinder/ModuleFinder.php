<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ModuleFinder\Business\Module\ModuleFinder;

use Generated\Shared\Transfer\ApplicationTransfer;
use Generated\Shared\Transfer\ModuleFilterTransfer;
use Generated\Shared\Transfer\ModuleTransfer;
use Generated\Shared\Transfer\OrganizationTransfer;
use Laminas\Filter\FilterChain;
use Laminas\Filter\StringToLower;
use Laminas\Filter\Word\CamelCaseToDash;
use Laminas\Filter\Word\DashToCamelCase;
use RuntimeException;
use Spryker\Shared\ModuleFinder\Transfer\Application;
use Spryker\Shared\ModuleFinder\Transfer\Module;
use Spryker\Shared\ModuleFinder\Transfer\ModuleFilter;
use Spryker\Shared\ModuleFinder\Transfer\Organization;
use Spryker\Zed\ModuleFinder\Business\Module\ModuleMatcher\ModuleMatcherInterface;
use Spryker\Zed\ModuleFinder\ModuleFinderConfig;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ModuleFinder implements ModuleFinderInterface
{
    /**
     * @var array<\Generated\Shared\Transfer\ModuleTransfer|\Spryker\Shared\ModuleFinder\Transfer\Module>
     */
    protected static $moduleTransferCollection;

    public function __construct(protected ModuleFinderConfig $config, protected ModuleMatcherInterface $moduleMatcher)
    {
    }

    /**
     * @return array<\Generated\Shared\Transfer\ModuleTransfer|\Spryker\Shared\ModuleFinder\Transfer\Module>
     */
    public function getModules(ModuleFilterTransfer|ModuleFilter|null $moduleFilterTransfer = null): array
    {
        if ($moduleFilterTransfer === null && static::$moduleTransferCollection !== null) {
            return static::$moduleTransferCollection;
        }

        $moduleTransferCollection = [];

        $moduleTransferCollection = $this->addStandaloneModulesToCollection($moduleTransferCollection, $moduleFilterTransfer);
        $moduleTransferCollection = $this->addModulesToCollection($moduleTransferCollection, $moduleFilterTransfer);

        ksort($moduleTransferCollection);

        if ($moduleFilterTransfer === null) {
            static::$moduleTransferCollection = $moduleTransferCollection;
        }

        return $moduleTransferCollection;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ModuleTransfer|\Spryker\Shared\ModuleFinder\Transfer\Module> $moduleTransferCollection
     *
     * @return array<\Generated\Shared\Transfer\ModuleTransfer|\Spryker\Shared\ModuleFinder\Transfer\Module>
     */
    protected function addStandaloneModulesToCollection(
        array $moduleTransferCollection,
        ModuleFilterTransfer|ModuleFilter|null $moduleFilterTransfer = null
    ): array {
        foreach ($this->getStandaloneModuleFinder() as $directoryInfo) {
            if (in_array($this->camelCase($directoryInfo->getFilename()), $this->config->getInternalOrganizations(), true)) {
                continue;
            }
            $moduleTransfer = $this->getModuleTransfer($directoryInfo);
            $moduleTransfer->setIsStandalone(true);

            if (!$this->isModule($moduleTransfer)) {
                continue;
            }

            $moduleTransferCollection = $this->addModuleToCollection($moduleTransfer, $moduleTransferCollection, $moduleFilterTransfer);
        }

        return $moduleTransferCollection;
    }

    protected function getStandaloneModuleFinder(): Finder
    {
        return (new Finder())->directories()->depth('== 0')->in(APPLICATION_VENDOR_DIR . '/spryker/');
    }

    /**
     * @param array<\Generated\Shared\Transfer\ModuleTransfer|\Spryker\Shared\ModuleFinder\Transfer\Module> $moduleTransferCollection
     *
     * @return array<\Generated\Shared\Transfer\ModuleTransfer|\Spryker\Shared\ModuleFinder\Transfer\Module>
     */
    protected function addModuleToCollection(
        ModuleTransfer|Module $moduleTransfer,
        array $moduleTransferCollection,
        ModuleFilterTransfer|ModuleFilter|null $moduleFilterTransfer = null
    ): array {
        if ($moduleFilterTransfer !== null && !$this->moduleMatcher->matches($moduleTransfer, $moduleFilterTransfer)) {
            return $moduleTransferCollection;
        }

        $moduleTransferCollection[$this->buildCollectionKey($moduleTransfer)] = $moduleTransfer;

        return $moduleTransferCollection;
    }

    /**
     * Modules which are standalone, can also be normal modules. This can be detected by the composer.json description
     * which contains `module` at the end of the description.
     */
    protected function isModule(ModuleTransfer|Module $moduleTransfer): bool
    {
        $composerJsonAsArray = $this->getComposerJsonAsArray($moduleTransfer->getPath());

        if (!isset($composerJsonAsArray['description'])) {
            return false;
        }

        $description = $composerJsonAsArray['description'];

        return (bool)preg_match('/\smodule$/', $description);
    }

    /**
     * @param array<\Generated\Shared\Transfer\ModuleTransfer|\Spryker\Shared\ModuleFinder\Transfer\Module> $moduleTransferCollection
     *
     * @return array<\Generated\Shared\Transfer\ModuleTransfer|\Spryker\Shared\ModuleFinder\Transfer\Module>
     */
    protected function addModulesToCollection(array $moduleTransferCollection, ModuleFilterTransfer|ModuleFilter|null $moduleFilterTransfer = null): array
    {
        foreach ($this->getModuleFinder() as $directoryInfo) {
            $moduleTransfer = $this->getModuleTransfer($directoryInfo);

            if (!$this->isModule($moduleTransfer)) {
                continue;
            }
            $moduleTransferCollection = $this->addModuleToCollection($moduleTransfer, $moduleTransferCollection, $moduleFilterTransfer);
        }

        return $moduleTransferCollection;
    }

    protected function getModuleFinder(): Finder
    {
        return (new Finder())->directories()->depth('== 0')->in($this->getModuleDirectories());
    }

    /**
     * @return array
     */
    protected function getModuleDirectories(): array
    {
        return $this->config->getPathsToInternalOrganizations();
    }

    protected function getModuleTransfer(SplFileInfo $directoryInfo): ModuleTransfer|Module
    {
        if ($this->existComposerJson($directoryInfo->getPathname())) {
            return $this->buildModuleTransferFromComposerJsonInformation($directoryInfo);
        }

        return $this->buildModuleTransferFromDirectoryInformation($directoryInfo);
    }

    protected function buildCollectionKey(ModuleTransfer|Module $moduleTransfer): string
    {
        return sprintf('%s.%s', $moduleTransfer->getOrganization()->getName(), $moduleTransfer->getName());
    }

    protected function existComposerJson(string $path): bool
    {
        $pathToComposerJson = sprintf('%s/composer.json', $path);

        return file_exists($pathToComposerJson);
    }

    protected function buildModuleTransferFromDirectoryInformation(SplFileInfo $directoryInfo): ModuleTransfer|Module
    {
        $organizationNameDashed = $this->getOrganizationNameFromDirectory($directoryInfo);
        $organizationName = $this->camelCase($organizationNameDashed);

        $moduleName = $this->camelCase($this->getModuleNameFromDirectory($directoryInfo));
        $moduleNameDashed = $this->dasherize($moduleName);

        $organizationTransfer = $this->buildOrganizationTransfer($organizationName, $organizationNameDashed);

        $moduleTransfer = $this->buildModuleTransfer($moduleName, $moduleNameDashed, $directoryInfo);
        $moduleTransfer
            ->setOrganization($organizationTransfer);

        $moduleTransfer = $this->addApplications($moduleTransfer);

        return $moduleTransfer;
    }

    protected function buildModuleTransferFromComposerJsonInformation(SplFileInfo $directoryInfo): ModuleTransfer|Module
    {
        $composerJsonAsArray = $this->getComposerJsonAsArray($directoryInfo->getPathname());

        $organizationNameDashed = $this->getOrganizationNameFromComposer($composerJsonAsArray);
        $organizationName = $this->camelCase($organizationNameDashed);

        $moduleNameDashed = $this->getModuleNameFromComposer($composerJsonAsArray);
        $moduleName = $this->camelCase($moduleNameDashed);

        $organizationTransfer = $this->buildOrganizationTransfer($organizationName, $organizationNameDashed);

        $moduleTransfer = $this->buildModuleTransfer($moduleName, $moduleNameDashed, $directoryInfo);
        $moduleTransfer
            ->setOrganization($organizationTransfer);

        $moduleTransfer = $this->addApplications($moduleTransfer);

        return $moduleTransfer;
    }

    protected function addApplications(ModuleTransfer|Module $moduleTransfer): ModuleTransfer|Module
    {
        $lookupDirectory = sprintf('%s/src/%s/', $moduleTransfer->getPath(), $moduleTransfer->getOrganization()->getName());
        if (!is_dir($lookupDirectory)) {
            return $moduleTransfer;
        }
        $applicationFinder = new Finder();
        $applicationFinder->in($lookupDirectory)->depth('== 0');

        foreach ($applicationFinder as $applicationDirectoryInfo) {
            $applicationTransfer = class_exists(ApplicationTransfer::class) ? new ApplicationTransfer() : new Application();
            $applicationTransfer->setName($applicationDirectoryInfo->getRelativePathname());
            $moduleTransfer->addApplication($applicationTransfer);
        }

        return $moduleTransfer;
    }

    protected function buildOrganizationTransfer(string $organizationName, string $organizationNameDashed): OrganizationTransfer|Organization
    {
        $organizationTransfer = class_exists(OrganizationTransfer::class) ? new OrganizationTransfer() : new Organization();
        $organizationTransfer
            ->setName($organizationName)
            ->setNameDashed($organizationNameDashed);

        return $organizationTransfer;
    }

    protected function buildModuleTransfer(string $moduleName, string $moduleNameDashed, SplFileInfo $directoryInfo): ModuleTransfer|Module
    {
        $moduleTransfer = class_exists(ModuleTransfer::class) ? new ModuleTransfer() : new Module();
        $moduleTransfer
            ->setName($moduleName)
            ->setNameDashed($moduleNameDashed)
            ->setPath($directoryInfo->getRealPath() . DIRECTORY_SEPARATOR)
            ->setIsStandalone(false);

        return $moduleTransfer;
    }

    /**
     * @throws \RuntimeException
     *
     * @return array
     */
    protected function getComposerJsonAsArray(string $path): array
    {
        $pathToComposerJson = sprintf('%s/composer.json', $path);
        if (!is_file($pathToComposerJson)) {
            return [];
        }
        $fileContent = file_get_contents($pathToComposerJson);
        if ($fileContent === false) {
            throw new RuntimeException('Cannot read file content: ' . $pathToComposerJson);
        }

        $composerJsonAsArray = json_decode($fileContent, true);
        if ($composerJsonAsArray === false || $composerJsonAsArray === null) {
            throw new RuntimeException('Invalid file content: ' . $pathToComposerJson);
        }

        return $composerJsonAsArray;
    }

    protected function getOrganizationNameFromComposer(array $composerJsonAsArray): string
    {
        $nameFragments = explode('/', $composerJsonAsArray['name']);
        $organizationName = $nameFragments[0];

        return $organizationName;
    }

    protected function getOrganizationNameFromDirectory(SplFileInfo $directoryInfo): string
    {
        $pathFragments = explode(DIRECTORY_SEPARATOR, (string)$directoryInfo->getRealPath());
        $vendorPosition = array_search('vendor', $pathFragments);

        $organizationName = $pathFragments[$vendorPosition + 1];

        return $organizationName;
    }

    protected function getApplicationNameFromDirectory(SplFileInfo $directoryInfo): string
    {
        $pathFragments = explode(DIRECTORY_SEPARATOR, (string)$directoryInfo->getRealPath());
        $vendorPosition = array_search('vendor', $pathFragments);

        $applicationName = $pathFragments[$vendorPosition + 2];

        return $applicationName;
    }

    protected function getModuleNameFromComposer(array $composerJsonAsArray): string
    {
        $nameFragments = explode('/', $composerJsonAsArray['name']);
        $moduleName = $nameFragments[1];

        return $moduleName;
    }

    protected function getModuleNameFromDirectory(SplFileInfo $directoryInfo): string
    {
        return $directoryInfo->getRelativePathname();
    }

    protected function camelCase(string $value): string
    {
        $filterChain = new FilterChain();
        $filterChain->attach(new DashToCamelCase());

        return ucfirst($filterChain->filter($value));
    }

    protected function dasherize(string $value): string
    {
        $filterChain = new FilterChain();
        $filterChain
            ->attach(new CamelCaseToDash())
            ->attach(new StringToLower());

        return $filterChain->filter($value);
    }
}
