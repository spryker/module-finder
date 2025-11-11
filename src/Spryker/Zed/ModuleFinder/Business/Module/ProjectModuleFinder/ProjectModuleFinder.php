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
use Spryker\Shared\ModuleFinder\Transfer\Application;
use Spryker\Shared\ModuleFinder\Transfer\Module;
use Spryker\Shared\ModuleFinder\Transfer\ModuleFilter;
use Spryker\Shared\ModuleFinder\Transfer\Organization;
use Spryker\Zed\ModuleFinder\Business\Composer\ComposerReaderInterface;
use Spryker\Zed\ModuleFinder\Business\Module\ModuleMatcher\ModuleMatcherInterface;
use Spryker\Zed\ModuleFinder\ModuleFinderConfig;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ProjectModuleFinder implements ProjectModuleFinderInterface
{
    public function __construct(
        protected ModuleFinderConfig $config,
        protected ModuleMatcherInterface $moduleMatcher,
        protected ComposerReaderInterface $composerReader
    ) {
    }

    /**
     * @return array<\Generated\Shared\Transfer\ModuleTransfer|\Spryker\Shared\ModuleFinder\Transfer\Module>
     */
    public function getProjectModules(ModuleFilterTransfer|ModuleFilter|null $moduleFilterTransfer = null): array
    {
        $moduleCollection = [];

        $projectDirectories = $this->getProjectDirectories($moduleFilterTransfer);

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

            $this->addUsedCoreModules($moduleTransfer);

            $moduleCollection[$this->buildOrganizationModuleKey($moduleTransfer)] = $moduleTransfer;
        }

        ksort($moduleCollection);

        return $this->filterOutModulesWithDevDependencies($moduleCollection);
    }

    /**
     * @return array<string>
     */
    protected function getProjectDirectories(ModuleFilterTransfer|ModuleFilter|null $moduleFilterTransfer = null): array
    {
        $projectOrganizationModuleDirectories = [];

        $selectedOrganization = null;

        if ($moduleFilterTransfer !== null && $moduleFilterTransfer->getOrganization() !== null) {
            $selectedOrganization = $moduleFilterTransfer->getOrganization()->getName();
        }

        foreach ($this->config->getProjectOrganizations() as $organization) {
            if ($selectedOrganization && $selectedOrganization !== $organization) {
                continue;
            }

            foreach ($this->config->getApplications() as $application) {
                $projectOrganizationModuleDirectories[] = sprintf('%1$s/%2$s/*/src/%2$s/%3$s/', APPLICATION_SOURCE_DIR, $organization, $application);
                // BC: To not break projects prior to the Project Structure refactoring, we keep the former path variation here as well.
                $projectOrganizationModuleDirectories[] = sprintf('%s/*/%s/', APPLICATION_SOURCE_DIR, $application);
            }
        }

        return array_filter($projectOrganizationModuleDirectories, 'glob');
    }

    /**
     * @param array<string> $projectOrganizationModuleDirectories
     */
    protected function getProjectModuleFinder(array $projectOrganizationModuleDirectories): Finder
    {
        $finder = new Finder();
        $finder
            ->directories()
            ->depth('== 0')
            ->in($projectOrganizationModuleDirectories);

        // This filtering is required to filter out Generated and Orm files. If not filtered, the `/development/module-overview`
        // page breaks in Zed.
        return $finder->filter(fn ($file) => !(str_contains($file->getPath(), '/Generated/') || str_contains($file->getPath(), '/Orm/')));
    }

    protected function getModuleTransfer(SplFileInfo $directoryInfo): ModuleTransfer|Module
    {
        $moduleTransfer = $this->buildModuleTransferFromDirectoryInformation($directoryInfo);
        $moduleTransfer->setOrganization($this->buildOrganizationTransferFromDirectoryInformation($directoryInfo));

        return $moduleTransfer;
    }

    protected function buildModuleTransferFromDirectoryInformation(SplFileInfo $directoryInfo): ModuleTransfer|Module
    {
        $moduleName = $this->getModuleNameFromDirectory($directoryInfo);
        // Chicken egg problem for applications which are fresh and do not have Transfers generated.
        $moduleTransfer = class_exists(ModuleTransfer::class) ? new ModuleTransfer() : new Module();
        $moduleTransfer
            ->setName($moduleName)
            ->setPath(dirname(APPLICATION_SOURCE_DIR) . DIRECTORY_SEPARATOR);

        return $moduleTransfer;
    }

    protected function buildOrganizationTransferFromDirectoryInformation(SplFileInfo $directoryInfo): OrganizationTransfer|Organization
    {
        $organizationName = $this->getOrganizationNameFromDirectory($directoryInfo);
        // Chicken egg problem for applications which are fresh and do not have Transfers generated.
        $organizationTransfer = class_exists(OrganizationTransfer::class) ? new OrganizationTransfer() : new Organization();
        $organizationTransfer
            ->setName($organizationName)
            ->setIsProject(true);

        return $organizationTransfer;
    }

    protected function buildApplicationTransferFromDirectoryInformation(SplFileInfo $directoryInfo): ApplicationTransfer|Application
    {
        $applicationName = $this->getApplicationNameFromDirectory($directoryInfo);
        // Chicken egg problem for applications which are fresh and do not have Transfers generated.
        $applicationTransfer = class_exists(ApplicationTransfer::class) ? new ApplicationTransfer() : new Application();
        $applicationTransfer->setName($applicationName);

        return $applicationTransfer;
    }

    protected function getOrganizationNameFromDirectory(SplFileInfo $directoryInfo): string
    {
        $pathFragments = explode(DIRECTORY_SEPARATOR, (string)$directoryInfo->getRealPath());
        $srcKeys = array_keys($pathFragments, 'src');
        $srcPosition = end($srcKeys);

        return $pathFragments[(int)$srcPosition + 1];
    }

    protected function getApplicationNameFromDirectory(SplFileInfo $directoryInfo): string
    {
        $pathFragments = explode(DIRECTORY_SEPARATOR, (string)$directoryInfo->getRealPath());
        $srcKeys = array_keys($pathFragments, 'src');
        $srcPosition = end($srcKeys);

        return $pathFragments[(int)$srcPosition + 2];
    }

    protected function getModuleNameFromDirectory(SplFileInfo $directoryInfo): string
    {
        return $directoryInfo->getRelativePathname();
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\ModuleTransfer|\Spryker\Shared\ModuleFinder\Transfer\Module> $moduleCollection
     *
     * @return array<string, \Generated\Shared\Transfer\ModuleTransfer|\Spryker\Shared\ModuleFinder\Transfer\Module>
     */
    protected function filterOutModulesWithDevDependencies(array $moduleCollection): array
    {
        if ($this->config->isDevelopmentMode()) {
            return $moduleCollection;
        }

        $devPackages = $this->composerReader->getDevPackageNames();

        foreach ($moduleCollection as $key => $module) {
            if ($module->getIsProjectOnly()) {
                continue;
            }

            foreach ($module->getUsedModules() as $usedModule) {
                $composerName = sprintf(
                    '%s/%s',
                    $this->dasherize($usedModule->getOrganization()->getName()),
                    $this->dasherize($usedModule->getName()),
                );

                if (in_array($composerName, $devPackages, true)) {
                    unset($moduleCollection[$key]);
                }
            }
        }

        return $moduleCollection;
    }

    /**
     * @return void
     */
    protected function addUsedCoreModules(ModuleTransfer|Module $moduleTransfer): void
    {
        $usedModules = $this->findUsedCoreModules($moduleTransfer);

        if (count($usedModules) === 0) {
            return;
        }

        $moduleTransfer->setIsProjectOnly(false);
        foreach ($usedModules as $usedModule) {
            $moduleTransfer->addUsedModule($usedModule);
        }
    }

    /**
     * @return array<string, \Generated\Shared\Transfer\ModuleTransfer|\Spryker\Shared\ModuleFinder\Transfer\Module>
     */
    protected function findUsedCoreModules(ModuleTransfer|Module $moduleTransfer): array
    {
        $usedModules = [];
        $finder = new Finder();
        $finder->files()
            ->in($this->getModulePath($moduleTransfer))
            ->name('*.php')
            ->notName($this->config->getExcludedFileNames());

        foreach ($finder as $file) {
            $content = $file->getContents();
            preg_match_all('/^use\s+([a-zA-Z0-9_\\\]+?)(\s+as\s+[a-zA-Z0-9_]+)?;$/m', $content, $matches);

            if (empty($matches[1])) {
                continue;
            }

            foreach ($matches[1] as $fullNamespace) {
                $parts = explode('\\', $fullNamespace);

                if (count($parts) < 3) {
                    continue;
                }

                $organization = $parts[0];
                if (!in_array($organization, $this->config->getCoreNamespaces(), true)) {
                    continue;
                }

                $moduleName = $parts[2];
                $usedModule = $this->createModule($organization, $moduleName);
                $usedModules[$this->buildOrganizationModuleKey($usedModule)] = $usedModule;
            }
        }

        return $usedModules;
    }

    protected function getModulePath(ModuleTransfer|Module $moduleTransfer): string
    {
        $path = $moduleTransfer->getPath();
        $path .= sprintf(
            'src/%1$s/%2$s/src/%1$s',
            $moduleTransfer->getOrganization()->getName(),
            $moduleTransfer->getName(),
        );

        return $path;
    }

    protected function createModule(string $organization, string $moduleName): ModuleTransfer|Module
    {
        $module = class_exists(ModuleTransfer::class) ? new ModuleTransfer() : new Module();
        $module->setName($moduleName);

        $organizationTransfer = class_exists(OrganizationTransfer::class) ? new OrganizationTransfer() : new Organization();
        $organizationTransfer->setName($organization);
        $organizationTransfer->setIsProject(false);

        $module->setOrganization($organizationTransfer);

        return $module;
    }

    protected function buildOrganizationModuleKey(ModuleTransfer|Module $moduleTransfer): string
    {
        if ($moduleTransfer->getOrganization() === null || $moduleTransfer->getName() === null) {
            return '';
        }

        return sprintf('%s.%s', $moduleTransfer->getOrganization()->getName(), $moduleTransfer->getName());
    }

    protected function dasherize(string $string): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $string) ?? $string);
    }
}
