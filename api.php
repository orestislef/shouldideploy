<?php
// Only set headers if we're running as a web request
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

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

class ShouldIDeploy {
    
    private static $reasons = [
        'en' => [
            'REASONS_TO_DEPLOY' => [
                "I don't see why not",
                "It's a free country",
                'Go ahead my friend!',
                'Go for it',
                'Go go go go!',
                "Let's do it!",
                'Ship it! 🚢',
                'Go with the flow 🌊',
                'Harder better faster stronger',
                'Rock on!',
                'Make me proud',
                'Break a leg!',
                'This Is the Way',
                'Strike First, Strike Hard, No Mercy!'
            ],
            'REASONS_TO_NOT_DEPLOY' => [
                "I wouldn't recommend it",
                "No, it's Friday",
                'What about Monday?',
                'Not today',
                'Nope',
                'Why?',
                'Did the tests pass? Probably not',
                '¯\\_(ツ)_/¯',
                '😹',
                'No',
                'No. Breathe and count to 10, start again',
                "I'd rather have ice-cream 🍦",
                'How could you? 🥺',
                'Some people just want to watch the world burn 🔥',
                "You like fire don't you?",
                'The bugs are just waiting for you'
            ],
            'REASONS_FOR_THURSDAY_AFTERNOON' => [
                'You still want to sleep?',
                'Call your partner!',
                'Gonna stay late today?',
                'Tell your boss that you found a bug and go home',
                'What about Monday?',
                "I wouldn't recommend it",
                'Not today',
                'Nope',
                'No. Breathe and count to 10, start again'
            ],
            'REASONS_FOR_FRIDAY_AFTERNOON' => [
                'Not by any chance',
                'U mad?',
                'What you are thinking?',
                'No no no no no no no no',
                'How do you feel about working nights and weekends?',
                '🔥 🚒 🚨 ⛔️ 🔥 🚒 🚨 ⛔️ 🔥 🚒 🚨 ⛔️',
                'No! God! Please! No',
                'No no no no no no no!',
                'Keep dreaming darling',
                'Why why Bro why?',
                'But but but... why?',
                'Deploys are for Monday, so you can fix them till Friday.',
                'YOLO ! You only live once !',
                "Error in line NaN Col -2 unexpected 'ↇ'"
            ],
            'REASONS_FOR_FRIDAY_13TH' => [
                "Man, really? It's friday the 13th!",
                'Do you believe in bad luck?',
                'Jason is watching you',
                'If you want to spend your weekend in Crystal Lake, go ahead',
                'To pray is no help if you take this bad decision',
                'Did you look at the calendar today?',
                '📅 Friday the 13th. What do you think about it?',
                'Just no!',
                'But but but... why?'
            ],
            'REASONS_FOR_AFTERNOON' => [
                'You still want to sleep?',
                'Call your partner!',
                'Gonna stay late today?',
                'Tomorrow?',
                'No',
                'Tell your boss that you found a bug and go home',
                'You have full day ahead of you tomorrow!',
                "Trust me, they will be much happier if it wasn't broken for a night",
                'How much do you trust your logging tools?'
            ],
            'REASONS_FOR_WEEKEND' => [
                "Go home, you're drunk",
                'How about Monday?',
                'Beer?',
                'Drunk development is not a good idea!',
                'I see you deployed on Friday',
                'Told you that Monday would be a better idea!',
                'There are 2^1000 other ideas.'
            ],
            'REASONS_FOR_DAY_BEFORE_CHRISTMAS' => [
                'Are you Santa 🧑‍🎄 or what?',
                '🎶🎵 You better watch out 🎵🎶',
                '🎄 Enjoy the holiday season! 🎄 ',
                'Just take another glass of eggnog',
                "Can't you just wait after present unwrapping?",
                'Sure, deploy... \n your family will appreciate you fixing things on your phone during dinner'
            ],
            'REASONS_FOR_CHRISTMAS' => [
                'Are you Santa 🧑‍🎄 or what?',
                '🎶🎵 You better watch out 🎵🎶',
                '🎄 Enjoy the holiday season! 🎄 ',
                'Just take another glass of eggnog',
                "Can't you just wait after present unwrapping?",
                'Sure, deploy... \n your family will appreciate you fixing things on your phone during dinner',
                'No, Rudolf will hunt you down 🦌 ',
                'Just watch Home Alone today',
                "Shouldn't you be preparing a christmas dinner?"
            ],
            'REASONS_NEW_YEAR' => [
                'Happy New Year! \n deploy the 2nd of january',
                "Aren't you hungover?",
                'Take another glass of champagne 🥂',
                'Celebrate today, deploy tomorrow 🎇'
            ]
        ],
        'el' => [
            'REASONS_TO_DEPLOY' => [
                'Γιατί όχι;',
                'Ελεύθερη χώρα είμαστε!',
                'Πάμε φίλε!',
                'Δώσε!',
                'Πάμε πάμε πάμε!',
                'Ας το κάνουμε!',
                'Στείλε το! 🚢',
                'Με τη ροή 🌊',
                'Σκληρά, γρήγορα, δυνατά!',
                'Γκάζι!',
                'Κάνε με περήφανο',
                'Καλή τύχη!',
                'Αυτό είναι το στυλ',
                'Πρώτος και καλύτερος!'
            ],
            'REASONS_TO_NOT_DEPLOY' => [
                'Μη το κάνεις',
                'Όχι ρε, Παρασκευή είναι!',
                'Καλύτερα Δευτέρα;',
                'Σήμερα όχι',
                'Άστο',
                'Γιατί ρε;',
                'Τα tests πέρασαν; Ας μην ονειρευόμαστε...',
                '¯\\_(ツ)_/¯',
                '😹',
                'Όχι!',
                'Σταμάτα. Μέτρησε μέχρι το 10 και ξανασκέψου το',
                'Καλύτερα ένα παγωτό 🍦',
                'Σοβαρά τώρα; 🥺',
                'Κάποιοι απλά θέλουν να καούν 🔥',
                'Σου αρέσουν τα προβλήματα;',
                'Τα bugs σε περιμένουν στη γωνία'
            ],
            'REASONS_FOR_THURSDAY_AFTERNOON' => [
                'Θες ακόμη ύπνο;',
                'Πάρε τη γυναίκα σου τηλέφωνο!',
                'Θα κάτσεις μέχρι αργά;',
                'Πες στο αφεντικό ότι βρήκες bug και φύγε',
                'Καλύτερα Δευτέρα',
                'Μη το κάνεις',
                'Σήμερα όχι',
                'Άστο',
                'Σταμάτα. Μέτρησε μέχρι το 10'
            ],
            'REASONS_FOR_FRIDAY_AFTERNOON' => [
                'Ούτε να το σκεφτείς!',
                'Έχασες τα μυαλά σου;',
                'Τι περνάει από το κεφάλι σου;',
                'Όχι όχι όχι όχι όχι όχι όχι!',
                'Έτοιμος για βάρδιες το Σάββατο;',
                '🔥 🚒 🚨 ⛔️ 🔥 🚒 🚨 ⛔️ 🔥 🚒 🚨 ⛔️',
                'Όχι! Θεέ μου! Όχι!',
                'ΟΧΙΙΙΙ!',
                'Όνειρα γλυκά...',
                'Μα γιατί ρε φίλε;',
                'Μα... γιατί;',
                'Deploy Δευτέρα, debug μέχρι Παρασκευή!',
                'YOLO! Μόνο μια φορά ζούμε!',
                "Σφάλμα στη γραμμή ∞ Col -∞"
            ],
            'REASONS_FOR_FRIDAY_13TH' => [
                'Ρε φίλε, σοβαρά; Είναι Παρασκευή και 13;',
                'Πιστεύεις στην κακή τύχη;',
                'Ο Jason σε παρακολουθεί',
                'Αν θέλεις να περάσεις το σαββατοκύριακό σου στη Crystal Lake, προχώρα',
                'Το να προσεύχεσαι δεν βοηθάει αν πάρεις αυτή την κακή απόφαση',
                'Κοίταξες το ημερολόγιο σήμερα;',
                '📅 Παρασκευή και 13. Τι πιστεύεις γι\' αυτό;',
                'Απλά όχι!',
                'Αλλά αλλά αλλά... γιατί;'
            ],
            'REASONS_FOR_AFTERNOON' => [
                'Θέλεις ακόμα να κοιμηθείς;',
                'Κάλεσε τον/την σύντροφό σου!',
                'Θα μείνεις αργά σήμερα;',
                'Αύριο;',
                'Όχι',
                'Πες στον αφεντικό ότι βρήκες bug και πήγαινε σπίτι',
                'Έχεις ολόκληρη μέρα μπροστά σου αύριο!',
                'Πίστεψέ με, θα είναι πολύ πιο ευχαριστημένοι αν δεν ήταν χαλασμένο για μία νύχτα',
                'Πόσο εμπιστεύεσαι τα logging tools σου;'
            ],
            'REASONS_FOR_WEEKEND' => [
                'Πήγαινε σπίτι, είσαι μεθυσμένος',
                'Τι λες για Δευτέρα;',
                'Μπίρα;',
                'Το μεθυσμένο development δεν είναι καλή ιδέα!',
                'Βλέπω ότι έκανες deploy την Παρασκευή',
                'Σου είπα ότι η Δευτέρα θα ήταν καλύτερη ιδέα!',
                'Υπάρχουν 2^1000 άλλες ιδέες.'
            ],
            'REASONS_FOR_DAY_BEFORE_CHRISTMAS' => [
                'Είσαι ο Άγιος Βασίλης 🧑‍🎄 ή τι;',
                '🎶🎵 Καλύτερα να προσέχεις 🎵🎶',
                '🎄 Απόλαυσε την εορταστική περίοδο! 🎄 ',
                'Πάρε άλλο ένα ποτήρι eggnog',
                'Δεν μπορείς να περιμένεις μετά το άνοιγμα των δώρων;',
                'Βεβαίως, κάνε deploy... \n η οικογένειά σου θα εκτιμήσει που θα διορθώνεις πράγματα στο τηλέφωνό σου κατά τη διάρκεια του δείπνου'
            ],
            'REASONS_FOR_CHRISTMAS' => [
                'Είσαι ο Άγιος Βασίλης 🧑‍🎄 ή τι;',
                '🎶🎵 Καλύτερα να προσέχεις 🎵🎶',
                '🎄 Απόλαυσε την εορταστική περίοδο! 🎄 ',
                'Πάρε άλλο ένα ποτήρι eggnog',
                'Δεν μπορείς να περιμένεις μετά το άνοιγμα των δώρων;',
                'Βεβαίως, κάνε deploy... \n η οικογένειά σου θα εκτιμήσει που θα διορθώνεις πράγματα στο τηλέφωνό σου κατά τη διάρκεια του δείπνου',
                'Όχι, ο Rudolf θα σε κυνηγήσει 🦌 ',
                'Απλά δες το Home Alone σήμερα',
                'Δεν πρέπει να ετοιμάζεις χριστουγεννιάτικο δείπνο;'
            ],
            'REASONS_NEW_YEAR' => [
                'Καλή Χρονιά! \n κάνε deploy στις 2 Ιανουαρίου',
                'Δεν έχεις hangover;',
                'Πάρε άλλο ένα ποτήρι σαμπάνια 🥂',
                'Γιόρτασε σήμερα, κάνε deploy αύριο 🎇'
            ]
        ]
    ];
    
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
        $reasons = self::$reasons[$lang] ?? self::$reasons['en'];
        
        if ($time->isDayBeforeChristmas()) {
            return $reasons['REASONS_FOR_DAY_BEFORE_CHRISTMAS'];
        }
        
        if ($time->isChristmas()) {
            return $reasons['REASONS_FOR_CHRISTMAS'];
        }
        
        if ($time->isNewYear()) {
            return $reasons['REASONS_NEW_YEAR'];
        }
        
        if ($time->isFriday13th()) {
            return $reasons['REASONS_FOR_FRIDAY_13TH'];
        }
        
        if ($time->isFridayAfternoon()) {
            return $reasons['REASONS_FOR_FRIDAY_AFTERNOON'];
        }
        
        if ($time->isFriday()) {
            return $reasons['REASONS_TO_NOT_DEPLOY'];
        }
        
        if ($time->isThursdayAfternoon()) {
            return $reasons['REASONS_FOR_THURSDAY_AFTERNOON'];
        }
        
        if ($time->isWeekend()) {
            return $reasons['REASONS_FOR_WEEKEND'];
        }
        
        if ($time->isAfternoon()) {
            return $reasons['REASONS_FOR_AFTERNOON'];
        }
        
        return $reasons['REASONS_TO_DEPLOY'];
    }
}

// Main API logic - only run if this file is accessed directly
if (basename($_SERVER['PHP_SELF']) === 'api.php' || php_sapi_name() !== 'cli') {
    $timezone = $_GET['tz'] ?? Time::DEFAULT_TIMEZONE;
    $customDate = $_GET['date'] ?? null;
    $lang = $_GET['lang'] ?? 'en';

    // Validate timezone
    if (!Time::zoneExists($timezone)) {
        if (php_sapi_name() !== 'cli') {
            http_response_code(400);
        }
        echo json_encode([
            'error' => [
                'message' => "Timezone `{$timezone}` does not exist",
                'type' => 'Bad Request',
                'code' => 400
            ]
        ]);
        exit();
    }

    // Validate language
    if (!in_array($lang, ['en', 'el'])) {
        $lang = 'en';
    }

    // Create time instance
    $parsedDate = $customDate ? date('Y-m-d', strtotime($customDate)) : null;
    $time = new Time($timezone, $parsedDate);

    // Generate response
    $response = [
        'timezone' => $timezone,
        'date' => $customDate 
            ? (new DateTime($customDate))->format('c')
            : $time->now()->format('c'),
        'shouldideploy' => ShouldIDeploy::shouldIDeploy($time),
        'message' => ShouldIDeploy::getRandom(ShouldIDeploy::dayHelper($time, $lang))
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>