<?php
namespace App\Helpers;

use App\Services\Settings\SettingsService;

class FormattingHelper
{
  protected static function getSettings()
  {
    // Get settings from the middleware-loaded settings object
    if (app()->bound('settings')) {
      return app('settings');
    }
    
    // Fallback to loading from service
    $settingsService = app(SettingsService::class);
    $settings = $settingsService->all();
    
    // Convert to object format for backward compatibility
    return (object) $settings->toArray();
  }

  public static function formatCurrency($amount): string
  {
    $settings = self::getSettings();
    $decimal = $settings->decimal_places ?? 2;
    $decSep = $settings->decimal_separator ?? '.';
    $thouSep = $settings->thousand_separator ?? ',';
    $formatted = number_format((float) $amount, $decimal, $decSep, $thouSep);
    
    // Get currency symbol from settings first, fallback to CurrencyHelper
    $symbol = $settings->currency_symbol ?? null;
    if (!$symbol) {
      $currencyCode = $settings->default_currency ?? 'USD';
      $symbol = CurrencyHelper::getSymbol($currencyCode);
    }
    
    if (($settings->currency_position ?? 'left') === 'left') {
      return $symbol . $formatted;
    }
    return $formatted . $symbol;
  }

  public static function formatCurrencyWithCurrency($amount, $currencyCode = null): string
  {
    // Check if MultiCurrency module is enabled
    $addonService = app(\App\Services\AddonService\IAddonService::class);
    if ($addonService->isAddonEnabled('MultiCurrency')) {
      try {
        $currency = null;

        if ($currencyCode) {
          $currency = \Modules\MultiCurrency\app\Models\Currency::where('code', $currencyCode)
            ->where('is_active', true)
            ->first();
        }

        if (!$currency) {
          $currency = \Modules\MultiCurrency\app\Models\Currency::getDefault();
        }

        if ($currency) {
          return $currency->formatAmount($amount);
        }
      } catch (\Exception $e) {
        // Fallback to regular formatting if MultiCurrency module has issues
      }
    }

    // Fallback to formatting with specific currency code if provided
    if ($currencyCode) {
      return self::formatWithSpecificCurrency($amount, $currencyCode);
    }
    
    // Fallback to default currency formatting
    return self::formatCurrency($amount);
  }
  
  public static function formatWithSpecificCurrency($amount, $currencyCode): string
  {
    $settings = self::getSettings();
    $decimal = $settings->decimal_places ?? 2;
    $decSep = $settings->decimal_separator ?? '.';
    $thouSep = $settings->thousand_separator ?? ',';
    $formatted = number_format((float) $amount, $decimal, $decSep, $thouSep);
    
    // Get symbol for the specific currency
    $symbol = CurrencyHelper::getSymbol($currencyCode);
    
    if (($settings->currency_position ?? 'left') === 'left') {
      return $symbol . $formatted;
    }
    return $formatted . $symbol;
  }

  public static function convertCurrency($amount, $fromCurrency, $toCurrency): float
  {
    // Check if MultiCurrency module is enabled
    $addonService = app(\App\Services\AddonService\IAddonService::class);
    if (!$addonService->isAddonEnabled('MultiCurrency')) {
      return $amount;
    }

    try {
      $fromCurrencyModel = \Modules\MultiCurrency\app\Models\Currency::where('code', $fromCurrency)
        ->where('is_active', true)
        ->first();

      $toCurrencyModel = \Modules\MultiCurrency\app\Models\Currency::where('code', $toCurrency)
        ->where('is_active', true)
        ->first();

      if (!$fromCurrencyModel || !$toCurrencyModel) {
        return $amount;
      }

      // Convert to default currency first, then to target currency
      $defaultAmount = $fromCurrencyModel->convertToDefault($amount);
      return $toCurrencyModel->convertFromDefault($defaultAmount);
    } catch (\Exception $e) {
      return $amount;
    }
  }

  public static function getDefaultCurrency()
  {
    // Check if MultiCurrency module is enabled
    $addonService = app(\App\Services\AddonService\IAddonService::class);
    if ($addonService->isAddonEnabled('MultiCurrency')) {
      try {
        return \Modules\MultiCurrency\app\Models\Currency::getDefault();
      } catch (\Exception $e) {
        // Fallback if module has issues
      }
    }

    return null;
  }
  
  public static function getCurrentCurrencyCode(): string
  {
    $settings = self::getSettings();
    return $settings->default_currency ?? 'USD';
  }
  
  public static function getCurrentCurrencySymbol(): string
  {
    $settings = self::getSettings();
    
    // Get currency symbol from settings first, fallback to CurrencyHelper
    $symbol = $settings->currency_symbol ?? null;
    if (!$symbol) {
      $currencyCode = $settings->default_currency ?? 'USD';
      $symbol = CurrencyHelper::getSymbol($currencyCode);
    }
    
    return $symbol;
  }

  public static function getActiveCurrencies()
  {
    // Check if MultiCurrency module is enabled
    $addonService = app(\App\Services\AddonService\IAddonService::class);
    if ($addonService->isAddonEnabled('MultiCurrency')) {
      try {
        return \Modules\MultiCurrency\app\Models\Currency::getActive();
      } catch (\Exception $e) {
        // Fallback if module has issues
      }
    }

    return collect();
  }

  public static function formatDate($date): ?string
  {
    if (!$date) {
      return null;
    }
    $format = self::getSettings()->date_format ?? 'Y-m-d';
    return $date->format($format);
  }

  public static function formatTime($time): ?string
  {
    if (!$time) {
      return null;
    }
    $format = self::getSettings()->time_format ?? 'H:i';
    return $time->format($format);
  }

  public static function formatDateTime($dateTime): ?string
  {
    if (!$dateTime) {
      return null;
    }
    $dateFormat = self::getSettings()->date_format ?? 'Y-m-d';
    $timeFormat = self::getSettings()->time_format ?? 'H:i';
    $format = $dateFormat . ' ' . $timeFormat;
    return $dateTime->format($format);
  }
}
