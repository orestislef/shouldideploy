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
                'Ελεύθερη χώρα είμαστε',
                'Προχώρα φίλε μου!',
                'Δώσ’ του!',
                'Πάμε, πάμε, πάμε!',
                'Πάμε γερά!',
                'Ανέβασέ το! 🚢',
                'Χαλαρά, θα πάει καλά 🌊',
                'Harder, Better, Faster, Stronger',
                'Σκίσ’ τα!',
                'Κάνε με να σε καμαρώνω',
                'Καλή επιτυχία, φτου φτου!',
                'This is the way',
                'Strike First, Strike Hard, No Mercy!'
            ],
            'REASONS_TO_NOT_DEPLOY' => [
                'Δεν θα το ’κανα αν ήμουν εσύ',
                'Όχι ρε, Παρασκευή είναι!',
                'Δευτέρα δεν σου κάνει;',
                'Όχι σήμερα',
                'Ούτε να το σκέφτεσαι',
                'Γιατί ρε φίλε;',
                'Τα tests πέρασαν; Μην απαντάς, ξέρω...',
                '¯\\_(ツ)_/¯',
                '😹',
                'Όχι',
                'Όχι. Πάρε μια ανάσα, μέτρα ως το 10 και ξανά απ’ την αρχή',
                'Καλύτερα ένα παγωτό 🍦',
                'Θα μου το κάνεις αυτό; 🥺',
                'Κάποιοι απλά θέλουν να δουν τον κόσμο να καίγεται 🔥',
                'Σ’ αρέσει να παίζεις με τη φωτιά, ε;',
                'Τα bugs σε περιμένουν στη γωνία'
            ],
            'REASONS_FOR_THURSDAY_AFTERNOON' => [
                'Θες να κοιμηθείς απόψε ή όχι;',
                'Πάρε ένα τηλέφωνο στο σπίτι!',
                'Θα μείνεις πάλι μέχρι αργά;',
                'Πες στο αφεντικό ότι βρήκες bug και την κάνεις για σπίτι',
                'Δευτέρα δεν σου κάνει;',
                'Δεν θα το ’κανα αν ήμουν εσύ',
                'Όχι σήμερα',
                'Ούτε να το σκέφτεσαι',
                'Όχι. Πάρε μια ανάσα, μέτρα ως το 10 και ξανά απ’ την αρχή'
            ],
            'REASONS_FOR_FRIDAY_AFTERNOON' => [
                'Ούτε κατά διάνοια',
                'Καλά, είσαι με τα καλά σου;',
                'Πού πας ρε Καραμήτρο;',
                'Όχι όχι όχι όχι όχι όχι όχι όχι',
                'Σ’ αρέσει να δουλεύεις βράδια και Σαββατοκύριακα;',
                '🔥 🚒 🚨 ⛔️ 🔥 🚒 🚨 ⛔️ 🔥 🚒 🚨 ⛔️',
                'Όχι! Θεέ μου! Σε παρακαλώ, όχι!',
                'Όχι όχι όχι όχι όχι όχι όχι!',
                'Στον ύπνο σου θα το δεις, χρυσό μου',
                'Γιατί ρε αδερφέ, γιατί;',
                'Μα... μα... μα γιατί;',
                'Τα deploy γίνονται Δευτέρα, για να έχεις όλη τη βδομάδα να τα φτιάξεις.',
                'YOLO! Μια ζωή την έχουμε!',
                "Error in line NaN Col -2 unexpected 'ↇ'"
            ],
            'REASONS_FOR_FRIDAY_13TH' => [
                'Σοβαρά τώρα; Παρασκευή και 13 είναι!',
                'Πιστεύεις στη γκαντεμιά;',
                'Ο Jason σε βλέπει...',
                'Θες να βγάλεις το Σαββατοκύριακο στο Crystal Lake; Προχώρα',
                'Καμία προσευχή δεν θα σε σώσει μετά από αυτό',
                'Είδες καθόλου ημερολόγιο σήμερα;',
                '📅 Παρασκευή και 13. Εσύ τι λες;',
                'Όχι και πολλά λέμε!',
                'Μα... μα... μα γιατί;',
                'Καλά που δεν είναι και Τρίτη και 13...'
            ],
            'REASONS_FOR_AFTERNOON' => [
                'Θες να κοιμηθείς απόψε ή όχι;',
                'Πάρε ένα τηλέφωνο στο σπίτι!',
                'Θα μείνεις πάλι μέχρι αργά;',
                'Αύριο;',
                'Όχι',
                'Πες στο αφεντικό ότι βρήκες bug και την κάνεις για σπίτι',
                'Αύριο έχεις όλη τη μέρα μπροστά σου!',
                'Πίστεψέ με, όλοι θα είναι πιο χαρούμενοι αν δεν μείνει χαλασμένο όλο το βράδυ',
                'Πόσο εμπιστεύεσαι τα logs σου;'
            ],
            'REASONS_FOR_WEEKEND' => [
                'Πήγαινε σπίτι σου, έχεις πιει',
                'Δευτέρα δεν σου κάνει;',
                'Μπυρίτσα;',
                'Coding με ποτά; Κακή ιδέα!',
                'Βλέπω έκανες deploy Παρασκευή...',
                'Σου ’λεγα εγώ, Δευτέρα ήταν καλύτερα!',
                'Υπάρχουν άλλες 2^1000 ιδέες για σήμερα.',
                'Σαββατοκύριακο είναι, πήγαινε για φρέντο ☕'
            ],
            'REASONS_FOR_DAY_BEFORE_CHRISTMAS' => [
                'Τι είσαι, ο Άγιος Βασίλης; 🧑‍🎄',
                '🎶🎵 Καλήν εσπέραν άρχοντες 🎵🎶',
                '🎄 Καλές γιορτές! 🎄',
                'Φάε άλλο ένα μελομακάρονο',
                'Δεν μπορείς να περιμένεις να ανοίξουν πρώτα τα δώρα;',
                'Ναι, κάνε deploy... η οικογένεια θα χαρεί πολύ να σε βλέπει να φτιάχνεις bugs απ’ το κινητό στο γιορτινό τραπέζι'
            ],
            'REASONS_FOR_CHRISTMAS' => [
                'Τι είσαι, ο Άγιος Βασίλης; 🧑‍🎄',
                '🎶🎵 Καλήν εσπέραν άρχοντες 🎵🎶',
                '🎄 Καλές γιορτές! 🎄',
                'Φάε άλλο ένα μελομακάρονο',
                'Δεν μπορείς να περιμένεις να ανοίξουν πρώτα τα δώρα;',
                'Ναι, κάνε deploy... η οικογένεια θα χαρεί πολύ να σε βλέπει να φτιάχνεις bugs απ’ το κινητό στο γιορτινό τραπέζι',
                'Όχι, θα σε κυνηγήσει ο Ρούντολφ 🦌',
                'Δες το «Μόνος στο σπίτι» και άσ’ το',
                'Δεν πρέπει να βοηθάς με τη γαλοπούλα;'
            ],
            'REASONS_NEW_YEAR' => [
                'Καλή χρονιά! Deploy στις 2 Ιανουαρίου',
                'Δεν έχεις πονοκέφαλο από χθες;',
                'Πιες άλλο ένα ποτήρι σαμπάνια 🥂',
                'Σήμερα γιορτάζουμε, αύριο κάνουμε deploy 🎇',
                'Κόψε πρώτα τη βασιλόπιτα και βλέπουμε'
            ]
        ],
        'bg' => [
            'REASONS_TO_DEPLOY' => [
                'Не виждам защо не',
                'Свободна държава сме',
                'Давай, приятел!',
                'Действай!',
                'Давай, давай, давай!',
                'Хайде да го направим!',
                'Пускай го! 🚢',
                'Отпусни се, ще стане 🌊',
                'Harder, Better, Faster, Stronger',
                'Разбивай!',
                'Накарай ме да се гордея с теб',
                'Успех! Стискам палци!',
                'This is the way',
                'Strike First, Strike Hard, No Mercy!'
            ],
            'REASONS_TO_NOT_DEPLOY' => [
                'Не бих го направил на твое място',
                'Не, петък е!',
                'А в понеделник?',
                'Не днес',
                'Няма начин',
                'Защо?',
                'Минаха ли тестовете? Едва ли',
                '¯\\_(ツ)_/¯',
                '😹',
                'Не',
                'Не. Поеми си дъх, преброй до 10 и почни отначало',
                'По-добре един сладолед 🍦',
                'Как можеш? 🥺',
                'Някои хора просто искат да гледат как светът гори 🔥',
                'Обичаш да си играеш с огъня, а?',
                'Бъговете само това чакат'
            ],
            'REASONS_FOR_THURSDAY_AFTERNOON' => [
                'Искаш ли да спиш тази нощ?',
                'Обади се вкъщи!',
                'Пак ли ще стоиш до късно?',
                'Кажи на шефа, че си намерил бъг, и си тръгвай',
                'А в понеделник?',
                'Не бих го направил на твое място',
                'Не днес',
                'Няма начин',
                'Не. Поеми си дъх, преброй до 10 и почни отначало'
            ],
            'REASONS_FOR_FRIDAY_AFTERNOON' => [
                'В никакъв случай',
                'Луд ли си?',
                'Какво си мислиш?',
                'Не не не не не не не не',
                'Как ти се струва да работиш нощем и през уикенда?',
                '🔥 🚒 🚨 ⛔️ 🔥 🚒 🚨 ⛔️ 🔥 🚒 🚨 ⛔️',
                'Не! Боже! Моля те! Не!',
                'Не не не не не не не!',
                'Мечтай си, сладурче',
                'Защо бе, брат, защо?',
                'Ама... ама... защо?',
                'Деплоят е за понеделник, за да имаш цялата седмица да го оправиш.',
                'YOLO! Живее се само веднъж!',
                "Error in line NaN Col -2 unexpected 'ↇ'"
            ],
            'REASONS_FOR_FRIDAY_13TH' => [
                'Сериозно ли, бе? Петък 13 е!',
                'Суеверен ли си?',
                'Джейсън те гледа...',
                'Ако искаш да прекараш уикенда на Кристъл Лейк, давай',
                'Никаква молитва няма да те спаси след това',
                'Поглеждал ли си календара днес?',
                '📅 Петък 13. Ти какво мислиш?',
                'Просто не!',
                'Ама... ама... защо?'
            ],
            'REASONS_FOR_AFTERNOON' => [
                'Искаш ли да спиш тази нощ?',
                'Обади се вкъщи!',
                'Пак ли ще стоиш до късно?',
                'Утре?',
                'Не',
                'Кажи на шефа, че си намерил бъг, и си тръгвай',
                'Утре имаш цял ден пред себе си!',
                'Повярвай ми, всички ще са по-щастливи, ако не стои счупено цяла нощ',
                'Колко вярваш на логовете си?'
            ],
            'REASONS_FOR_WEEKEND' => [
                'Прибирай се, пиян си',
                'А в понеделник?',
                'По бира?',
                'Код на пияна глава? Лоша идея!',
                'Виждам, че си деплойнал в петък',
                'Казах ти, че понеделник е по-добра идея!',
                'Има още 2^1000 други идеи.',
                'Уикенд е. Шопска салата и ракия, не деплой 🥗'
            ],
            'REASONS_FOR_DAY_BEFORE_CHRISTMAS' => [
                'Да не си Дядо Коледа? 🧑‍🎄',
                '🎶🎵 Ой, Коледо, мой Коледо 🎵🎶',
                '🎄 Весели празници! 🎄',
                'Пийни още една греяна ракия',
                'Не можеш ли да изчакаш поне да се отворят подаръците?',
                'Разбира се, деплойвай... семейството ще е във възторг да те гледа как оправяш бъгове от телефона на празничната трапеза',
                'Бъдни вечер е! Сядай на трапезата'
            ],
            'REASONS_FOR_CHRISTMAS' => [
                'Да не си Дядо Коледа? 🧑‍🎄',
                '🎶🎵 Ой, Коледо, мой Коледо 🎵🎶',
                '🎄 Весели празници! 🎄',
                'Пийни още една греяна ракия',
                'Не можеш ли да изчакаш поне да се отворят подаръците?',
                'Разбира се, деплойвай... семейството ще е във възторг да те гледа как оправяш бъгове от телефона на празничната трапеза',
                'Не, Рудолф ще те намери 🦌',
                'Днес просто гледай „Сам вкъщи“',
                'Не трябва ли да помагаш за празничната трапеза?'
            ],
            'REASONS_NEW_YEAR' => [
                'Честита Нова година! Деплой на 2 януари',
                'Не те ли мъчи махмурлук?',
                'Пийни още една чаша шампанско 🥂',
                'Днес празнуваме, утре деплойваме 🎇',
                'Първо сурвакницата, после деплоя 🌿'
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
    if (!in_array($lang, ['en', 'el', 'bg'])) {
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