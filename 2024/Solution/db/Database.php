<?php
/**
 * Database class - wrapper for SQLite (PDO) operations.
 *
 * Provides initialization, schema creation/migrations, and helper methods
 * to access application-specific data such as animals and settings.
 *
 * @package RigetZoo
 * @author Snat
 * @link https://snat.co.uk
 */
// Ensure a reasonable ROOT_DIR when Database is included directly
if (!defined('ROOT_DIR')) { define('ROOT_DIR', dirname(__DIR__)); }

class Database
{
    private static $pdo;

    /**
     * Initialize the database connection and ensure the schema exists.
     *
     * This bootstraps the SQLite database, sets up tables if they do not
     * exist, and applies lightweight schema migrations (ALTER TABLE
     * when columns are missing). It also seeds example data when the
     * animals table is empty.
     *
     * @return void
     */
    public static function initialize()
    {
        if (self::$pdo) {
            return;
        }

        /**
         * Ensures the database directory exists and creates the directory path if necessary.
         * 
         * Constructs the full path to the SQLite database file by combining the root directory
         * with the relative database path. Checks if the parent directory exists, and if not,
         * creates it recursively with 0755 permissions (owner: read/write/execute, 
         * group and others: read/execute).
         * 
         * @var string $dbPath The full path to the SQLite database file
         * @var string $dir The directory path containing the database file
         */
        $dbPath = ROOT_DIR . '/db/rza.db';
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $dsn = 'sqlite:' . $dbPath;
        self::$pdo = new PDO($dsn);
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create animals table if not exists
        $create = "CREATE TABLE IF NOT EXISTS animals (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            category TEXT,
            name TEXT,
            habitat TEXT,
            diet TEXT,
            status TEXT,
            description TEXT,
            display_order INTEGER DEFAULT 0
        );";
        self::$pdo->exec($create);

        // Create users table if not exists
        $createUsers = "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE,
            password_hash TEXT,
            first_name TEXT,
            surname TEXT,
            address1 TEXT,
            postcode TEXT,
            is_admin INTEGER DEFAULT 0,
            created_at TEXT
        );";
        self::$pdo->exec($createUsers);

        // Create bookings table if not exists
        $createBookings = "CREATE TABLE IF NOT EXISTS bookings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            type TEXT,
            name TEXT,
            email TEXT,
            checkin TEXT,
            nights INTEGER,
            room TEXT,
            ticket_date TEXT,
            tickets INTEGER,
            unit_price REAL,
            total_price REAL,
            meta TEXT,
            created_at TEXT
        );";
        self::$pdo->exec($createBookings);

        // If is_admin missing, add it
        $usersCols = self::$pdo->query("PRAGMA table_info(users);")->fetchAll(PDO::FETCH_ASSOC);
        $hasIsAdmin = false;
        foreach ($usersCols as $c) { if (isset($c['name']) && $c['name'] === 'is_admin') { $hasIsAdmin = true; break; } }
        if (!$hasIsAdmin) {
            try { self::$pdo->exec('ALTER TABLE users ADD COLUMN is_admin INTEGER DEFAULT 0'); } catch (Exception $e) { }
        }

        // Ensure there's a default admin user with email 'admin' and password 'admin'
        try {
            // Normalize any legacy 'admin' email to the new 'admin@test.com'. This avoids a login UX problem when browsers enforce email inputs.
            $legacy = self::$pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $legacy->execute(['admin']);
            $legacyRow = $legacy->fetch(PDO::FETCH_ASSOC);
            if ($legacyRow) {
                $updateLegacy = self::$pdo->prepare('UPDATE users SET email = ? WHERE email = ?');
                $updateLegacy->execute(['admin@test.com', 'admin']);
            }
            $stmt = self::$pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute(['admin@test.com']);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $hash = password_hash('admin', PASSWORD_DEFAULT);
                $now = date('c');
                $ins = self::$pdo->prepare('INSERT INTO users (email,password_hash,first_name,surname,address1,postcode,is_admin,created_at) VALUES (?,?,?,?,?,?,?,?)');
                $ins->execute(['admin@test.com', $hash, 'Admin', 'User', '', '', 1, $now]);
            } else {
                // ensure the existing one is set as admin
                $update = self::$pdo->prepare('UPDATE users SET is_admin = 1 WHERE email = ?');
                $update->execute(['admin@test.com']);
            }
        } catch (Exception $e) { }

        // If user_id column missing, add it and try to backfill using email -> users.id
        $cols = self::$pdo->query("PRAGMA table_info(bookings);")->fetchAll(PDO::FETCH_ASSOC);
        $hasUserId = false;
        foreach ($cols as $c) {
            if (isset($c['name']) && $c['name'] === 'user_id') { $hasUserId = true; break; }
        }
        if (!$hasUserId) {
            // Add user_id column
            try {
                self::$pdo->exec('ALTER TABLE bookings ADD COLUMN user_id INTEGER;');
            } catch (Exception $e) {
                // ignore ALTER errors
            }
            // Backfill: update bookings.user_id from users table where email matches
            try {
                $users = self::$pdo->query('SELECT id, email FROM users')->fetchAll(PDO::FETCH_ASSOC);
                $map = [];
                foreach ($users as $user) { $map[$user['email']] = $user['id']; }
                $update = self::$pdo->prepare('UPDATE bookings SET user_id = ? WHERE user_id IS NULL AND email = ?');
                foreach ($map as $email => $uid) { $update->execute([$uid, $email]); }
            } catch (Exception $e) {
                // ignore backfill errors
            }
        }
        // Add unit_price and total_price columns if missing
        $cols = self::$pdo->query("PRAGMA table_info(bookings);")->fetchAll(PDO::FETCH_ASSOC);
        $hasUnitPrice = false;
        $hasTotalPrice = false;
        $hasStatus = false;
        $hasCancelledAt = false;
        foreach ($cols as $c) {
            if (isset($c['name']) && $c['name'] === 'unit_price') { $hasUnitPrice = true; }
            if (isset($c['name']) && $c['name'] === 'total_price') { $hasTotalPrice = true; }
            if (isset($c['name']) && $c['name'] === 'status') { $hasStatus = true; }
            if (isset($c['name']) && $c['name'] === 'cancelled_at') { $hasCancelledAt = true; }
        }
        if (!$hasUnitPrice) { try { self::$pdo->exec("ALTER TABLE bookings ADD COLUMN unit_price REAL;"); } catch (Exception $e) {} }
        if (!$hasTotalPrice) { try { self::$pdo->exec("ALTER TABLE bookings ADD COLUMN total_price REAL;"); } catch (Exception $e) {} }
            // Add loyalty columns: tier, discount_pct, discount_amount, perks
            $hasLoyaltyTier = false;
            $hasLoyaltyDiscountPct = false;
            $hasLoyaltyDiscountAmt = false;
            $hasLoyaltyPerks = false;
            foreach ($cols as $c) {
                if (isset($c['name']) && $c['name'] === 'loyalty_tier') { $hasLoyaltyTier = true; }
                if (isset($c['name']) && $c['name'] === 'loyalty_discount_pct') { $hasLoyaltyDiscountPct = true; }
                if (isset($c['name']) && $c['name'] === 'loyalty_discount_amount') { $hasLoyaltyDiscountAmt = true; }
                if (isset($c['name']) && $c['name'] === 'loyalty_perks') { $hasLoyaltyPerks = true; }
            }
            if (!$hasLoyaltyTier) { try { self::$pdo->exec("ALTER TABLE bookings ADD COLUMN loyalty_tier TEXT;"); } catch (Exception $e) {} }
            if (!$hasLoyaltyDiscountPct) { try { self::$pdo->exec("ALTER TABLE bookings ADD COLUMN loyalty_discount_pct REAL;"); } catch (Exception $e) {} }
            if (!$hasLoyaltyDiscountAmt) { try { self::$pdo->exec("ALTER TABLE bookings ADD COLUMN loyalty_discount_amount REAL;"); } catch (Exception $e) {} }
            if (!$hasLoyaltyPerks) { try { self::$pdo->exec("ALTER TABLE bookings ADD COLUMN loyalty_perks TEXT;"); } catch (Exception $e) {} }

        // Create a simple settings table to hold configuration like prices
        $createSettings = "CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT
        );";
        self::$pdo->exec($createSettings);

        // Create a table to store education tour requests
        $createEdu = "CREATE TABLE IF NOT EXISTS education_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            school TEXT,
            contact TEXT,
            email TEXT,
            phone TEXT,
            date TEXT,
            group_size INTEGER,
            age_range TEXT,
            mobility TEXT,
            allergies TEXT,
            behaviour TEXT,
            length TEXT,
            notes TEXT,
            status TEXT DEFAULT 'pending',
            meta TEXT,
            created_at TEXT
        );";
        self::$pdo->exec($createEdu);

        // Ensure defaults exist
        try {
            $defaults = [
                'ticket_price' => '10.0',
                'hotel_single' => '50.0',
                'hotel_double' => '90.0',
                'hotel_suite' => '150.0'
            ];
            $ins = self::$pdo->prepare('INSERT OR IGNORE INTO settings (key,value) VALUES (?,?)');
            foreach ($defaults as $k => $v) { $ins->execute([$k,$v]); }
        } catch (Exception $e) { }

        // Backfill pricing values where possible
        if (!$hasUnitPrice || !$hasTotalPrice) {
            try {
                // Ticket price default - backfill based on tickets
                $ticketPrice = 10.0;
                $updateTicket = self::$pdo->prepare('UPDATE bookings SET unit_price = ?, total_price = COALESCE(tickets,0) * ? WHERE unit_price IS NULL AND type = ?');
                $updateTicket->execute([$ticketPrice,$ticketPrice,'tickets']);
                // Hotel default pricing by room (single:50, double:90, suite:150)
                $roomPrices = ['single'=>50.0,'double'=>90.0,'suite'=>150.0];
                foreach ($roomPrices as $room => $price) {
                    $stmt = self::$pdo->prepare('UPDATE bookings SET unit_price = ?, total_price = COALESCE(nights,1) * ?, unit_price = ? WHERE unit_price IS NULL AND type = ? AND room = ?');
                    $stmt->execute([$price,$price,$price,'hotel',$room]);
                }
            } catch (Exception $e) { }
        }

        // Add status and cancelled_at if missing
        $cols = self::$pdo->query("PRAGMA table_info(bookings);")->fetchAll(PDO::FETCH_ASSOC);
        $hasStatus = false;
        $hasCancelledAt = false;
        foreach ($cols as $c) {
            if (isset($c['name']) && $c['name'] === 'status') { $hasStatus = true; }
            if (isset($c['name']) && $c['name'] === 'cancelled_at') { $hasCancelledAt = true; }
        }
        if (!$hasStatus) {
            try { self::$pdo->exec("ALTER TABLE bookings ADD COLUMN status TEXT DEFAULT 'active';"); } catch (Exception $e) {}
            try { self::$pdo->exec("UPDATE bookings SET status = 'active' WHERE status IS NULL;"); } catch (Exception $e) {}
        }
        if (!$hasCancelledAt) {
            try { self::$pdo->exec("ALTER TABLE bookings ADD COLUMN cancelled_at TEXT;"); } catch (Exception $e) {}
        }

        // seed with data if empty
        $count = self::$pdo->query('SELECT COUNT(*) FROM animals')->fetchColumn();
        if ($count == 0) {
            $insert = self::$pdo->prepare('INSERT INTO animals (category,name,habitat,diet,status,description,display_order) VALUES (?,?,?,?,?,?,?)');
            $items = [
                ['Big Cats','Lions','African Savanna','Carnivorous','Vulnerable','The king of the jungle, lions are majestic apex predators. Our pride includes two adult males and three females. Lions are highly social animals and spend most of their time resting, hunting together as a team.',0],
                ['Big Cats','Tigers','Asian Jungle','Carnivorous','Endangered','The world\'s largest cat, tigers are solitary and incredibly powerful hunters. We have two Bengal tigers who can be seen during their active periods in early morning and late afternoon.',1],
                ['Big Cats','Leopards','African Savanna & Asian Forests','Carnivorous','Vulnerable','Known for their spotted coat and climbing ability, leopards are masters of stealth. They often rest in trees during the day and hunt at night. Observe them during morning hours for best viewing.',2],
                ['Primates','Gorillas','Central African Rainforests','Herbivorous','Critically Endangered','Gorillas are our closest living relatives, sharing 98% of our DNA. Our troop of four males and five females live in a spacious naturalistic enclosure. Watch them demonstrate their incredible intelligence and gentle nature.',0],
                ['Primates','Chimpanzees','African Rainforests & Savannas','Omnivorous','Endangered','Highly intelligent and social, chimpanzees use tools and communicate with complex gestures. Our group is actively engaged throughout the day with enrichment activities and keeper interactions.',1],
                ['Primates','Ring-tailed Lemurs','Madagascar Rainforests','Omnivorous','Endangered','With their distinctive black and white striped tails, ring-tailed lemurs are highly social and vocal. Our troop is known for their playful antics and can often be heard throughout the primate section.',2],
                ['Birds','African Penguins','South African Coasts','Piscivorous (Fish)','Endangered','Penguins are flightless seabirds perfectly adapted to aquatic life. Watch them dive and swim in our specially designed penguin pool. Keeper talks are held daily at 11:00 AM and 3:00 PM.',0],
                ['Birds','Flamingos','African Lakes & Caribbean Islands','Herbivorous','Least Concern','Famous for their vibrant pink plumage, flamingos are highly social birds that live in large colonies. Their colour comes from pigments in their food. Our flock numbers over 50 birds.',1],
                ['Birds','Harpy Eagles','Central & South American Rainforests','Carnivorous','Near Threatened','One of the world\'s most powerful eagles, harpy eagles hunt large tree-dwelling animals. Their grip strength is equivalent to a large dog\'s bite. Observe from the viewing platform during demonstrations.',2],
                ['Reptiles','Saltwater Crocodiles','Southeast Asian Coasts & Rivers','Carnivorous','Least Concern','Among the largest and most powerful reptiles, saltwater crocodiles are ancient predators largely unchanged for millions of years. Our facility includes both indoor and outdoor viewing areas.',0],
                ['Reptiles','King Cobras','Southeast Asian Rainforests','Carnivorous (Other snakes)','Vulnerable','The world\'s longest venomous snake, king cobras can grow up to 13 feet. They\'re intelligent and have a distinctive hood display. Observe them safely through reinforced enclosures with expert supervision.',1],
                ['Reptiles','Komodo Dragons','Indonesian Islands','Carnivorous','Endangered','The world\'s largest living lizards, Komodo dragons can weigh up to 300 pounds. They\'re ambush predators with a venomous bite. Our Komodo exhibit recreates their volcanic island home.',2],
                ['Mammals - Other','African Elephants','African Savannas & Forests','Herbivorous','Vulnerable','The world\'s largest land animal, elephants are highly intelligent and social. Our herd includes three adults and one calf born at the zoo. Interactive educational sessions available daily.',0],
                ['Mammals - Other','Red Pandas','Eastern Himalayan Mountains','Omnivorous','Vulnerable','Despite their name, red pandas are not closely related to giant pandas. These adorable creatures are arboreal and spend much of their time in trees. They\'re active at dawn and dusk.',1],
                ['Mammals - Other','Giraffes','African Savannas','Herbivorous','Vulnerable','The world\'s tallest mammals, giraffes can reach up to 18 feet in height. Their long necks are specially adapted for feeding on acacia trees. Our giraffe encounter allows visitors to feed them in a supervised setting.',2],
            ];
            foreach ($items as $it) {
                $insert->execute($it);
            }
        }
    }

    /**
     * Return the current PDO instance. Initializes the database if needed.
     *
     * @return \PDO
     */
    public static function pdo()
    {
        if (!self::$pdo) {
            self::initialize();
        }
        return self::$pdo;
    }

    /**
     * Retrieve all animals grouped by category.
     *
     * Returns an associative array where keys are category names and values
     * are arrays of animal rows (associative arrays).
     *
     * @return array<string, array<int, array<string, mixed>>> Grouped animals
     */
    public static function getAnimalsGrouped()
    {
        $stmt = self::pdo()->query('SELECT * FROM animals ORDER BY category, display_order, id');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $groups = [];
        foreach ($rows as $r) {
            $groups[$r['category']][] = $r;
        }
        return $groups;
    }

    /**
     * Get a site setting value from the settings table.
     *
     * @param string $key The setting key
     * @param mixed|null $default Default value if setting is not present
     * @return mixed The setting value or default
     */
    public static function getSetting($key, $default = null)
    {
        $stmt = self::pdo()->prepare('SELECT value FROM settings WHERE key = ? LIMIT 1');
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && isset($row['value'])) return $row['value'];
        return $default;
    }
}

?>

<!-- 2025-12-03 11:00 - Add Database skeleton (Database.php placeholder) - author: Snat -->

