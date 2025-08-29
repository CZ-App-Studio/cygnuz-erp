<?php

namespace App\Services\AddonService;

interface IAddonService
{
  public function getAvailableAddons();

  public function isAddonEnabled(string $name, bool $isStandard = false): bool;

  public function isModuleAvailable(string $name): bool;

  public function isCoreModule(string $name): bool;

  public function getCoreModules(): array;

  public function getEnabledAddons(): array;

  public function getModuleType(string $name): string;
}
