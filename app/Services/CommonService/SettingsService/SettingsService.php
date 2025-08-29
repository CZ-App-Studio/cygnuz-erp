<?php

namespace App\Services\CommonService\SettingsService;

use App\Services\Settings\SettingsService as NewSettingsService;
use Illuminate\Support\Collection;

class SettingsService implements ISettings
{

  private Collection $settings;

  public function __construct()
  {
    $settingsService = app(NewSettingsService::class);
    $this->settings = $settingsService->all();
  }

  public function isDeviceVerificationEnabled(): bool
  {
    return (bool) $this->settings->get('is_device_verification_enabled', true);
  }


  public function getDocumentTypePrefix(): string
  {
    return $this->settings->get('document_type_code_prefix', 'DOC');
  }

  public function getAllSettings(): Collection
  {
    return $this->settings;
  }
}