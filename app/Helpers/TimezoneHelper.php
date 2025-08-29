<?php

namespace App\Helpers;

use DateTimeZone;
use Carbon\Carbon;

class TimezoneHelper
{
    /**
     * Get list of timezones grouped by region
     */
    public static function getTimezones(): array
    {
        $timezones = [];
        $regions = [
            'Africa' => DateTimeZone::AFRICA,
            'America' => DateTimeZone::AMERICA,
            'Antarctica' => DateTimeZone::ANTARCTICA,
            'Asia' => DateTimeZone::ASIA,
            'Atlantic' => DateTimeZone::ATLANTIC,
            'Australia' => DateTimeZone::AUSTRALIA,
            'Europe' => DateTimeZone::EUROPE,
            'Indian' => DateTimeZone::INDIAN,
            'Pacific' => DateTimeZone::PACIFIC,
        ];

        foreach ($regions as $name => $mask) {
            $zones = DateTimeZone::listIdentifiers($mask);
            foreach ($zones as $timezone) {
                // Format: "America/New_York" => "(UTC-05:00) America/New_York"
                $time = new \DateTime('now', new DateTimeZone($timezone));
                $offset = $time->format('P');
                $timezones[$timezone] = "(UTC{$offset}) {$timezone}";
            }
        }

        // Add UTC
        $timezones = ['UTC' => '(UTC+00:00) UTC'] + $timezones;

        return $timezones;
    }
    
    /**
     * Get a user-friendly list of timezones for dropdown
     */
    public static function getTimezoneList(): array
    {
        $timezones = [];
        
        // Popular timezones at the top
        $popular = [
            'UTC' => 'UTC - Coordinated Universal Time',
            'America/New_York' => 'America/New York (EST/EDT) UTC-5/-4',
            'America/Chicago' => 'America/Chicago (CST/CDT) UTC-6/-5',
            'America/Denver' => 'America/Denver (MST/MDT) UTC-7/-6',
            'America/Los_Angeles' => 'America/Los Angeles (PST/PDT) UTC-8/-7',
            'America/Mexico_City' => 'America/Mexico City UTC-6',
            'America/Sao_Paulo' => 'America/São Paulo UTC-3',
            'America/Toronto' => 'America/Toronto (EST/EDT) UTC-5/-4',
            'Europe/London' => 'Europe/London (GMT/BST) UTC+0/+1',
            'Europe/Paris' => 'Europe/Paris (CET/CEST) UTC+1/+2',
            'Europe/Berlin' => 'Europe/Berlin (CET/CEST) UTC+1/+2',
            'Europe/Moscow' => 'Europe/Moscow (MSK) UTC+3',
            'Africa/Cairo' => 'Africa/Cairo (EET) UTC+2',
            'Africa/Johannesburg' => 'Africa/Johannesburg (SAST) UTC+2',
            'Asia/Kolkata' => 'Asia/Kolkata (IST) UTC+5:30',
            'Asia/Dubai' => 'Asia/Dubai (GST) UTC+4',
            'Asia/Riyadh' => 'Asia/Riyadh (AST) UTC+3',
            'Asia/Shanghai' => 'Asia/Shanghai (CST) UTC+8',
            'Asia/Singapore' => 'Asia/Singapore (SGT) UTC+8',
            'Asia/Tokyo' => 'Asia/Tokyo (JST) UTC+9',
            'Asia/Seoul' => 'Asia/Seoul (KST) UTC+9',
            'Asia/Jakarta' => 'Asia/Jakarta (WIB) UTC+7',
            'Asia/Bangkok' => 'Asia/Bangkok (ICT) UTC+7',
            'Australia/Sydney' => 'Australia/Sydney (AEDT/AEST) UTC+11/+10',
            'Australia/Melbourne' => 'Australia/Melbourne (AEDT/AEST) UTC+11/+10',
            'Pacific/Auckland' => 'Pacific/Auckland (NZDT/NZST) UTC+13/+12',
        ];
        
        // Get all timezones
        $allTimezones = self::getTimezones();
        
        // Start with popular ones
        $timezones['-- Popular Timezones --'] = '-- Popular Timezones --';
        foreach ($popular as $key => $value) {
            $timezones[$key] = $value;
        }
        
        // Add separator
        $timezones['-- All Timezones --'] = '-- All Timezones --';
        
        // Add all remaining timezones
        foreach ($allTimezones as $key => $value) {
            if (!isset($popular[$key])) {
                $timezones[$key] = $value;
            }
        }
        
        return $timezones;
    }
    
    /**
     * Set the application timezone dynamically
     */
    public static function setApplicationTimezone(string $timezone): void
    {
        // Validate timezone
        if (!in_array($timezone, timezone_identifiers_list())) {
            throw new \InvalidArgumentException("Invalid timezone: {$timezone}");
        }
        
        // Update Laravel config
        config(['app.timezone' => $timezone]);
        
        // Update PHP default timezone
        date_default_timezone_set($timezone);
        
        // Reset Carbon to use new timezone
        Carbon::setTestNow();
    }

    /**
     * Get timezone offset in hours
     */
    public static function getOffset(string $timezone): float
    {
        try {
            $tz = new DateTimeZone($timezone);
            $time = new \DateTime('now', $tz);
            return $time->getOffset() / 3600;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Convert time between timezones
     */
    public static function convert(\DateTime $datetime, string $fromTimezone, string $toTimezone): \DateTime
    {
        $datetime->setTimezone(new DateTimeZone($fromTimezone));
        $datetime->setTimezone(new DateTimeZone($toTimezone));
        return $datetime;
    }
}