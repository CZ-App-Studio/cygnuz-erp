<?php

namespace App\Services\AddonService;

use Nwidart\Modules\Facades\Module;

class AddonService implements IAddonService
{
    public function getAvailableAddons()
    {
        return [];
    }

    /*
     * Check if the addon is enabled
     * @param string $name
     * @param bool $isStandard
     * @return bool
     */

    public function isAddonEnabled(string $name, bool $isStandard = false): bool
    {
        $module = Module::find($name);

        $result = ($module != null && $module->isEnabled());

        return $result;
    }

    /**
     * Check if a module is available (either core module or enabled addon)
     */
    public function isModuleAvailable(string $name): bool
    {
        // Check if it's a core module first
        if ($this->isCoreModule($name)) {
            return true;
        }

        // Check if it's an enabled addon
        return $this->isAddonEnabled($name);
    }

    /**
     * Check if a module is a core module
     */
    public function isCoreModule(string $name): bool
    {
        $module = Module::find($name);
        if (! $module) {
            return false;
        }

        $moduleConfig = $module->json()->getAttributes();

        return isset($moduleConfig['isCoreModule']) && $moduleConfig['isCoreModule'] === true;
    }

    /**
     * Get all core modules
     */
    public function getCoreModules(): array
    {
        $coreModules = [];
        $allModules = Module::all();

        foreach ($allModules as $module) {
            if ($this->isCoreModule($module->getName())) {
                $coreModules[] = $module->getName();
            }
        }

        return $coreModules;
    }

    /**
     * Get all enabled addons (non-core modules)
     */
    public function getEnabledAddons(): array
    {
        $enabledAddons = [];
        $allModules = Module::all();

        foreach ($allModules as $module) {
            if ($module->isEnabled() && ! $this->isCoreModule($module->getName())) {
                $enabledAddons[] = $module->getName();
            }
        }

        return $enabledAddons;
    }

    /**
     * Get module type (core, addon, or unknown)
     */
    public function getModuleType(string $name): string
    {
        $module = Module::find($name);
        if (! $module) {
            return 'unknown';
        }

        return $this->isCoreModule($name) ? 'core' : 'addon';
    }
}
