<?php
// Shared logic for api.php, badge.php and cli.php

class Time {
    const DEFAULT_TIMEZONE = 'UTC';
    
    private $timezone;
    private $customDate;
    
    public function __construct($timezone = null, $customDate = null) {
        $this->timezone = $timezone ?: 'UTC';
        
        if ($customDate) {
            try {
                $this->customDate = new DateTime($customDate . 'T00:00:00Z');
            } catch (Exception $e) {
                $this->customDate = null;
            }
        } else {
            $this->customDate = null;
        }
    }
    
    public static function zoneExists($timezone) {
        try {
            new DateTimeZone($timezone);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function getDate() {
        if ($this->customDate) {
            return $this->customDate;
        }
        
        try {
            $date = new DateTime('now', new DateTimeZone($this->timezone));
            return $date;
        } catch (Exception $e) {
            return new DateTime();
        }
    }
    
    public function now() {
        return $this->getDate();
    }
    
    public function isThursday() {
        return $this->getDate()->format('N') == 4;
    }
    
    public function isFriday() {
        return $this->getDate()->format('N') == 5;
    }
    
    public function is13th() {
        return $this->getDate()->format('j') == 13;
    }
    
    public function isAfternoon() {
        return $this->getDate()->format('G') >= 16;
    }
    
    public function isThursdayAfternoon() {
        return $this->isThursday() && $this->isAfternoon();
    }
    
    public function isFridayAfternoon() {
        return $this->isFriday() && $this->isAfternoon();
    }
    
    public function isFriday13th() {
        return $this->isFriday() && $this->is13th();
    }
    
    public function isWeekend() {
        $day = $this->getDate()->format('N');
        return $day == 6 || $day == 7;
    }
    
    public function isDayBeforeChristmas() {
        $date = $this->getDate();
        return $date->format('n') == 12 && 
               $date->format('j') == 24 && 
               $date->format('G') >= 16;
    }
    
    public function isChristmas() {
        $date = $this->getDate();
        return $date->format('n') == 12 && $date->format('j') == 25;
    }
    
    public function isNewYear() {
        $date = $this->now();
        return ($date->format('n') == 12 && 
                $date->format('j') == 31 && 
                $date->format('G') >= 16) ||
               ($date->format('n') == 1 && $date->format('j') == 1);
    }
    
    public function isHolidays() {
        return $this->isDayBeforeChristmas() || 
               $this->isChristmas() || 
               $this->isNewYear();
    }
}

class Locales {
    const DEFAULT_LANG = 'en';

    private static $cache = [];

    public static function available() {
        $langs = [];
        foreach (glob(__DIR__ . '/locales/*.json') as $file) {
            $langs[] = basename($file, '.json');
        }
        return $langs;
    }

    // Same fallback as upstream: exact match (es-AR), then base language (es), then English
    public static function resolve($lang) {
        $available = self::available();
        if ($lang && in_array($lang, $available, true)) {
            return $lang;
        }

        $base = $lang ? explode('-', $lang)[0] : '';
        if ($base && in_array($base, $available, true)) {
            return $base;
        }

        return self::DEFAULT_LANG;
    }

    public static function load($lang) {
        if (!isset(self::$cache[$lang])) {
            $json = file_get_contents(__DIR__ . '/locales/' . $lang . '.json');
            self::$cache[$lang] = json_decode($json, true);
        }
        return self::$cache[$lang];
    }

    public static function reasons($type, $lang) {
        $data = self::load(self::resolve($lang));
        if (!empty($data['reasons'][$type])) {
            return $data['reasons'][$type];
        }
        return self::load(self::DEFAULT_LANG)['reasons'][$type];
    }
}

class ShouldIDeploy {

    public static function shouldIDeploy(Time $time) {
        return !$time->isFriday() &&
               !$time->isWeekend() &&
               !$time->isHolidays() &&
               !$time->isAfternoon();
    }

    public static function getRandom($list) {
        return $list[array_rand($list)];
    }

    public static function dayHelper(Time $time, $lang = 'en') {
        if ($time->isDayBeforeChristmas()) {
            return Locales::reasons('day_before_christmas', $lang);
        }

        if ($time->isChristmas()) {
            return Locales::reasons('christmas', $lang);
        }

        if ($time->isNewYear()) {
            return Locales::reasons('new_year', $lang);
        }

        if ($time->isFriday13th()) {
            return Locales::reasons('friday_13th', $lang);
        }

        if ($time->isFridayAfternoon()) {
            return Locales::reasons('friday_afternoon', $lang);
        }

        if ($time->isFriday()) {
            return Locales::reasons('to_not_deploy', $lang);
        }

        if ($time->isThursdayAfternoon()) {
            return Locales::reasons('thursday_afternoon', $lang);
        }

        if ($time->isWeekend()) {
            return Locales::reasons('weekend', $lang);
        }

        if ($time->isAfternoon()) {
            return Locales::reasons('afternoon', $lang);
        }

        return Locales::reasons('to_deploy', $lang);
    }

    // Used by badge.php and cli.php, returns null when the timezone is invalid
    public static function resolveContext(array $params) {
        $timezone = ($params['tz'] ?? '') ?: Time::DEFAULT_TIMEZONE;
        if (!Time::zoneExists($timezone)) {
            return null;
        }

        $time = new Time($timezone);
        $canDeploy = self::shouldIDeploy($time);

        return [
            'timezone' => $timezone,
            'canDeploy' => $canDeploy,
            'reason' => self::getRandom(self::dayHelper($time, $params['lang'] ?? null)),
            'verdict' => $canDeploy ? 'YES' : 'NO'
        ];
    }
}
