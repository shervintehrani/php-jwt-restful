<?php
// فقط برای تست محلی: دیتابیس MySQL را با SQLite در حافظه/فایل جایگزین می‌کند
require_once dirname(__DIR__) . '/vendor/autoload.php';
$file = sys_get_temp_dir() . '/jwt_api_test.sqlite';
$pdo = new PDO('sqlite:' . $file, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec('CREATE TABLE IF NOT EXISTS `user` (id INTEGER PRIMARY KEY AUTOINCREMENT, user TEXT UNIQUE NOT NULL, pass TEXT NOT NULL, first_name TEXT, family TEXT, email TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)');
$pdo->exec('CREATE TABLE IF NOT EXISTS whois_log (log_id INTEGER PRIMARY KEY AUTOINCREMENT, domain_name TEXT, title TEXT, ip TEXT, domain_creation_date TEXT)');
if (!$pdo->query('SELECT COUNT(*) FROM whois_log')->fetchColumn()) {
    $pdo->exec("INSERT INTO whois_log (domain_name,title,ip,domain_creation_date) VALUES ('a.com','A','1.1.1.1','2000-01-01'),('b.com','B','2.2.2.2','2001-01-01'),('a.com','A2','1.1.1.2','2002-01-01')");
}
App\Database::setConnection($pdo);
