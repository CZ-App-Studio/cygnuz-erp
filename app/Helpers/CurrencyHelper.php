<?php

namespace App\Helpers;

class CurrencyHelper
{
  /**
   * Get all world currencies with their details
   *
   * @return array
   */
  public static function getAllCurrencies(): array
  {
    return [
      'AED' => ['name' => 'UAE Dirham', 'symbol' => 'د.إ'],
      'AFN' => ['name' => 'Afghan Afghani', 'symbol' => '؋'],
      'ALL' => ['name' => 'Albanian Lek', 'symbol' => 'L'],
      'AMD' => ['name' => 'Armenian Dram', 'symbol' => '֏'],
      'ANG' => ['name' => 'Netherlands Antillean Guilder', 'symbol' => 'ƒ'],
      'AOA' => ['name' => 'Angolan Kwanza', 'symbol' => 'Kz'],
      'ARS' => ['name' => 'Argentine Peso', 'symbol' => '$'],
      'AUD' => ['name' => 'Australian Dollar', 'symbol' => 'A$'],
      'AWG' => ['name' => 'Aruban Florin', 'symbol' => 'ƒ'],
      'AZN' => ['name' => 'Azerbaijani Manat', 'symbol' => '₼'],
      'BAM' => ['name' => 'Bosnia-Herzegovina Convertible Mark', 'symbol' => 'KM'],
      'BBD' => ['name' => 'Barbadian Dollar', 'symbol' => '$'],
      'BDT' => ['name' => 'Bangladeshi Taka', 'symbol' => '৳'],
      'BGN' => ['name' => 'Bulgarian Lev', 'symbol' => 'лв'],
      'BHD' => ['name' => 'Bahraini Dinar', 'symbol' => '.د.ب'],
      'BIF' => ['name' => 'Burundian Franc', 'symbol' => 'FBu'],
      'BMD' => ['name' => 'Bermudan Dollar', 'symbol' => '$'],
      'BND' => ['name' => 'Brunei Dollar', 'symbol' => '$'],
      'BOB' => ['name' => 'Bolivian Boliviano', 'symbol' => '$b'],
      'BRL' => ['name' => 'Brazilian Real', 'symbol' => 'R$'],
      'BSD' => ['name' => 'Bahamian Dollar', 'symbol' => '$'],
      'BTC' => ['name' => 'Bitcoin', 'symbol' => '₿'],
      'BTN' => ['name' => 'Bhutanese Ngultrum', 'symbol' => 'Nu.'],
      'BWP' => ['name' => 'Botswanan Pula', 'symbol' => 'P'],
      'BYN' => ['name' => 'Belarusian Ruble', 'symbol' => 'Br'],
      'BZD' => ['name' => 'Belize Dollar', 'symbol' => 'BZ$'],
      'CAD' => ['name' => 'Canadian Dollar', 'symbol' => 'C$'],
      'CDF' => ['name' => 'Congolese Franc', 'symbol' => 'FC'],
      'CHF' => ['name' => 'Swiss Franc', 'symbol' => 'CHF'],
      'CLP' => ['name' => 'Chilean Peso', 'symbol' => '$'],
      'CNY' => ['name' => 'Chinese Yuan', 'symbol' => '¥'],
      'COP' => ['name' => 'Colombian Peso', 'symbol' => '$'],
      'CRC' => ['name' => 'Costa Rican Colón', 'symbol' => '₡'],
      'CUC' => ['name' => 'Cuban Convertible Peso', 'symbol' => '$'],
      'CUP' => ['name' => 'Cuban Peso', 'symbol' => '₱'],
      'CVE' => ['name' => 'Cape Verdean Escudo', 'symbol' => '$'],
      'CZK' => ['name' => 'Czech Republic Koruna', 'symbol' => 'Kč'],
      'DJF' => ['name' => 'Djiboutian Franc', 'symbol' => 'Fdj'],
      'DKK' => ['name' => 'Danish Krone', 'symbol' => 'kr'],
      'DOP' => ['name' => 'Dominican Peso', 'symbol' => 'RD$'],
      'DZD' => ['name' => 'Algerian Dinar', 'symbol' => 'د.ج'],
      'EGP' => ['name' => 'Egyptian Pound', 'symbol' => '£'],
      'ERN' => ['name' => 'Eritrean Nakfa', 'symbol' => 'Nfk'],
      'ETB' => ['name' => 'Ethiopian Birr', 'symbol' => 'Br'],
      'EUR' => ['name' => 'Euro', 'symbol' => '€'],
      'FJD' => ['name' => 'Fijian Dollar', 'symbol' => '$'],
      'FKP' => ['name' => 'Falkland Islands Pound', 'symbol' => '£'],
      'GBP' => ['name' => 'British Pound Sterling', 'symbol' => '£'],
      'GEL' => ['name' => 'Georgian Lari', 'symbol' => '₾'],
      'GGP' => ['name' => 'Guernsey Pound', 'symbol' => '£'],
      'GHS' => ['name' => 'Ghanaian Cedi', 'symbol' => '¢'],
      'GIP' => ['name' => 'Gibraltar Pound', 'symbol' => '£'],
      'GMD' => ['name' => 'Gambian Dalasi', 'symbol' => 'D'],
      'GNF' => ['name' => 'Guinean Franc', 'symbol' => 'FG'],
      'GTQ' => ['name' => 'Guatemalan Quetzal', 'symbol' => 'Q'],
      'GYD' => ['name' => 'Guyanaese Dollar', 'symbol' => '$'],
      'HKD' => ['name' => 'Hong Kong Dollar', 'symbol' => 'HK$'],
      'HNL' => ['name' => 'Honduran Lempira', 'symbol' => 'L'],
      'HRK' => ['name' => 'Croatian Kuna', 'symbol' => 'kn'],
      'HTG' => ['name' => 'Haitian Gourde', 'symbol' => 'G'],
      'HUF' => ['name' => 'Hungarian Forint', 'symbol' => 'Ft'],
      'IDR' => ['name' => 'Indonesian Rupiah', 'symbol' => 'Rp'],
      'ILS' => ['name' => 'Israeli New Sheqel', 'symbol' => '₪'],
      'IMP' => ['name' => 'Manx pound', 'symbol' => '£'],
      'INR' => ['name' => 'Indian Rupee', 'symbol' => '₹'],
      'IQD' => ['name' => 'Iraqi Dinar', 'symbol' => 'ع.د'],
      'IRR' => ['name' => 'Iranian Rial', 'symbol' => '﷼'],
      'ISK' => ['name' => 'Icelandic Króna', 'symbol' => 'kr'],
      'JEP' => ['name' => 'Jersey Pound', 'symbol' => '£'],
      'JMD' => ['name' => 'Jamaican Dollar', 'symbol' => 'J$'],
      'JOD' => ['name' => 'Jordanian Dinar', 'symbol' => 'د.أ'],
      'JPY' => ['name' => 'Japanese Yen', 'symbol' => '¥'],
      'KES' => ['name' => 'Kenyan Shilling', 'symbol' => 'KSh'],
      'KGS' => ['name' => 'Kyrgystani Som', 'symbol' => 'лв'],
      'KHR' => ['name' => 'Cambodian Riel', 'symbol' => '៛'],
      'KMF' => ['name' => 'Comorian Franc', 'symbol' => 'CF'],
      'KPW' => ['name' => 'North Korean Won', 'symbol' => '₩'],
      'KRW' => ['name' => 'South Korean Won', 'symbol' => '₩'],
      'KWD' => ['name' => 'Kuwaiti Dinar', 'symbol' => 'د.ك'],
      'KYD' => ['name' => 'Cayman Islands Dollar', 'symbol' => '$'],
      'KZT' => ['name' => 'Kazakhstani Tenge', 'symbol' => '₸'],
      'LAK' => ['name' => 'Laotian Kip', 'symbol' => '₭'],
      'LBP' => ['name' => 'Lebanese Pound', 'symbol' => '£'],
      'LKR' => ['name' => 'Sri Lankan Rupee', 'symbol' => '₨'],
      'LRD' => ['name' => 'Liberian Dollar', 'symbol' => '$'],
      'LSL' => ['name' => 'Lesotho Loti', 'symbol' => 'L'],
      'LYD' => ['name' => 'Libyan Dinar', 'symbol' => 'ل.د'],
      'MAD' => ['name' => 'Moroccan Dirham', 'symbol' => 'د.م.'],
      'MDL' => ['name' => 'Moldovan Leu', 'symbol' => 'L'],
      'MGA' => ['name' => 'Malagasy Ariary', 'symbol' => 'Ar'],
      'MKD' => ['name' => 'Macedonian Denar', 'symbol' => 'ден'],
      'MMK' => ['name' => 'Myanma Kyat', 'symbol' => 'K'],
      'MNT' => ['name' => 'Mongolian Tugrik', 'symbol' => '₮'],
      'MOP' => ['name' => 'Macanese Pataca', 'symbol' => 'MOP$'],
      'MRU' => ['name' => 'Mauritanian Ouguiya', 'symbol' => 'UM'],
      'MUR' => ['name' => 'Mauritian Rupee', 'symbol' => '₨'],
      'MVR' => ['name' => 'Maldivian Rufiyaa', 'symbol' => '.ރ'],
      'MWK' => ['name' => 'Malawian Kwacha', 'symbol' => 'MK'],
      'MXN' => ['name' => 'Mexican Peso', 'symbol' => '$'],
      'MYR' => ['name' => 'Malaysian Ringgit', 'symbol' => 'RM'],
      'MZN' => ['name' => 'Mozambican Metical', 'symbol' => 'MT'],
      'NAD' => ['name' => 'Namibian Dollar', 'symbol' => '$'],
      'NGN' => ['name' => 'Nigerian Naira', 'symbol' => '₦'],
      'NIO' => ['name' => 'Nicaraguan Córdoba', 'symbol' => 'C$'],
      'NOK' => ['name' => 'Norwegian Krone', 'symbol' => 'kr'],
      'NPR' => ['name' => 'Nepalese Rupee', 'symbol' => '₨'],
      'NZD' => ['name' => 'New Zealand Dollar', 'symbol' => 'NZ$'],
      'OMR' => ['name' => 'Omani Rial', 'symbol' => 'ر.ع.'],
      'PAB' => ['name' => 'Panamanian Balboa', 'symbol' => 'B/.'],
      'PEN' => ['name' => 'Peruvian Nuevo Sol', 'symbol' => 'S/.'],
      'PGK' => ['name' => 'Papua New Guinean Kina', 'symbol' => 'K'],
      'PHP' => ['name' => 'Philippine Peso', 'symbol' => '₱'],
      'PKR' => ['name' => 'Pakistani Rupee', 'symbol' => '₨'],
      'PLN' => ['name' => 'Polish Zloty', 'symbol' => 'zł'],
      'PYG' => ['name' => 'Paraguayan Guarani', 'symbol' => 'Gs'],
      'QAR' => ['name' => 'Qatari Rial', 'symbol' => 'ر.ق'],
      'RON' => ['name' => 'Romanian Leu', 'symbol' => 'lei'],
      'RSD' => ['name' => 'Serbian Dinar', 'symbol' => 'Дин.'],
      'RUB' => ['name' => 'Russian Ruble', 'symbol' => '₽'],
      'RWF' => ['name' => 'Rwandan Franc', 'symbol' => 'FRw'],
      'SAR' => ['name' => 'Saudi Riyal', 'symbol' => 'ر.س'],
      'SBD' => ['name' => 'Solomon Islands Dollar', 'symbol' => '$'],
      'SCR' => ['name' => 'Seychellois Rupee', 'symbol' => '₨'],
      'SDG' => ['name' => 'Sudanese Pound', 'symbol' => '£'],
      'SEK' => ['name' => 'Swedish Krona', 'symbol' => 'kr'],
      'SGD' => ['name' => 'Singapore Dollar', 'symbol' => 'S$'],
      'SHP' => ['name' => 'Saint Helena Pound', 'symbol' => '£'],
      'SLL' => ['name' => 'Sierra Leonean Leone', 'symbol' => 'Le'],
      'SOS' => ['name' => 'Somali Shilling', 'symbol' => 'S'],
      'SRD' => ['name' => 'Surinamese Dollar', 'symbol' => '$'],
      'SSP' => ['name' => 'South Sudanese Pound', 'symbol' => '£'],
      'STN' => ['name' => 'São Tomé and Príncipe Dobra', 'symbol' => 'Db'],
      'SYP' => ['name' => 'Syrian Pound', 'symbol' => '£'],
      'SZL' => ['name' => 'Swazi Lilangeni', 'symbol' => 'L'],
      'THB' => ['name' => 'Thai Baht', 'symbol' => '฿'],
      'TJS' => ['name' => 'Tajikistani Somoni', 'symbol' => 'ЅМ'],
      'TMT' => ['name' => 'Turkmenistani Manat', 'symbol' => 'T'],
      'TND' => ['name' => 'Tunisian Dinar', 'symbol' => 'د.ت'],
      'TOP' => ['name' => 'Tongan Paʻanga', 'symbol' => 'T$'],
      'TRY' => ['name' => 'Turkish Lira', 'symbol' => '₺'],
      'TTD' => ['name' => 'Trinidad and Tobago Dollar', 'symbol' => 'TT$'],
      'TVD' => ['name' => 'Tuvaluan Dollar', 'symbol' => '$'],
      'TWD' => ['name' => 'New Taiwan Dollar', 'symbol' => 'NT$'],
      'TZS' => ['name' => 'Tanzanian Shilling', 'symbol' => 'TSh'],
      'UAH' => ['name' => 'Ukrainian Hryvnia', 'symbol' => '₴'],
      'UGX' => ['name' => 'Ugandan Shilling', 'symbol' => 'USh'],
      'USD' => ['name' => 'United States Dollar', 'symbol' => '$'],
      'UYU' => ['name' => 'Uruguayan Peso', 'symbol' => '$U'],
      'UZS' => ['name' => 'Uzbekistan Som', 'symbol' => 'лв'],
      'VES' => ['name' => 'Venezuelan Bolívar', 'symbol' => 'Bs.'],
      'VND' => ['name' => 'Vietnamese Dong', 'symbol' => '₫'],
      'VUV' => ['name' => 'Vanuatu Vatu', 'symbol' => 'VT'],
      'WST' => ['name' => 'Samoan Tala', 'symbol' => 'WS$'],
      'XAF' => ['name' => 'CFA Franc BEAC', 'symbol' => 'FCFA'],
      'XCD' => ['name' => 'East Caribbean Dollar', 'symbol' => '$'],
      'XOF' => ['name' => 'CFA Franc BCEAO', 'symbol' => 'CFA'],
      'XPF' => ['name' => 'CFP Franc', 'symbol' => '₣'],
      'YER' => ['name' => 'Yemeni Rial', 'symbol' => '﷼'],
      'ZAR' => ['name' => 'South African Rand', 'symbol' => 'R'],
      'ZMW' => ['name' => 'Zambian Kwacha', 'symbol' => 'ZK'],
      'ZWL' => ['name' => 'Zimbabwean Dollar', 'symbol' => '$']
    ];
  }

  /**
   * Get currency options for select dropdown
   *
   * @return array
   */
  public static function getCurrencyOptions(): array
  {
    $currencies = self::getAllCurrencies();
    $options = [];

    foreach ($currencies as $code => $currency) {
      $options[$code] = $code . ' - ' . $currency['name'];
    }

    return $options;
  }

  /**
   * Get currency symbol by code
   *
   * @param string $code
   * @return string
   */
  public static function getCurrencySymbol(string $code): string
  {
    $currencies = self::getAllCurrencies();
    return $currencies[$code]['symbol'] ?? $code;
  }

  /**
   * Get currency name by code
   *
   * @param string $code
   * @return string
   */
  public static function getCurrencyName(string $code): string
  {
    $currencies = self::getAllCurrencies();
    return $currencies[$code]['name'] ?? $code;
  }

  /**
   * Get currency symbol for a given currency code
   */
  public static function getSymbol(string $currencyCode): string
  {
    return self::getCurrencySymbol($currencyCode);
  }

  /**
   * Format amount with currency
   */
  public static function formatCurrency($amount, ?string $currencyCode = null, ?string $position = null): string
  {
    $settingsService = app(\App\Services\Settings\SettingsService::class);

    $currencyCode = $currencyCode ?? $settingsService->get('default_currency', 'USD');
    $position = $position ?? $settingsService->get('currency_position', 'left');
    $decimalPlaces = $settingsService->get('decimal_places', 2);
    $thousandSeparator = $settingsService->get('thousand_separator', ',');
    $decimalSeparator = $settingsService->get('decimal_separator', '.');

    $symbol = self::getSymbol($currencyCode);
    $formattedAmount = number_format($amount, $decimalPlaces, $decimalSeparator, $thousandSeparator);

    if ($position === 'left') {
      return $symbol . $formattedAmount;
    } else {
      return $formattedAmount . $symbol;
    }
  }
}
