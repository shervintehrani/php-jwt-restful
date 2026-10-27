#!/usr/bin/env bash
# تست سریع end-to-end با سرور داخلی PHP و SQLite (نیازی به MySQL نیست)
set -u
cd "$(dirname "$0")/.."
rm -f /tmp/jwt_api_test.sqlite
export JWT_SECRET="${JWT_SECRET:-0123456789abcdef0123456789abcdef-test}" JWT_ISSUER=http://localhost:18080 JWT_TTL=${JWT_TTL:-60} DB_USER=x
php -d auto_prepend_file=$PWD/tests/sqlite_prepend.php -S 127.0.0.1:18080 -t public >/tmp/php-server.log 2>&1 &
PID=$!; trap 'kill $PID 2>/dev/null' EXIT; sleep 1
B=http://127.0.0.1:18080; fail=0
check() { # name expected_code actual_code
  if [ "$2" = "$3" ]; then echo "PASS  $1 ($3)"; else echo "FAIL  $1 expected $2 got $3"; fail=1; fi; }
code() { curl -s -o /tmp/body -w '%{http_code}' "$@"; }
J='Content-Type: application/json'

check "register ok"            201 "$(code -X POST $B/register.php -H "$J" -d '{"user":"shervin","pass":"S3cretPass!","email":"a@b.com"}')"
check "register duplicate"     409 "$(code -X POST $B/register.php -H "$J" -d '{"user":"shervin","pass":"S3cretPass!"}')"
check "register short pass"    422 "$(code -X POST $B/register.php -H "$J" -d '{"user":"bob","pass":"1"}')"
check "register bad json"      400 "$(code -X POST $B/register.php -H "$J" -d 'nope')"
check "login wrong pass"       401 "$(code -X POST $B/login.php -H "$J" -d '{"user":"shervin","pass":"bad"}')"
check "login unknown user"     401 "$(code -X POST $B/login.php -H "$J" -d '{"user":"ghost","pass":"x"}')"
check "login missing fields"   400 "$(code -X POST $B/login.php -H "$J" -d '{}')"
check "login GET not allowed"  405 "$(code $B/login.php)"
check "login ok"               200 "$(code -X POST $B/login.php -H "$J" -d '{"user":"shervin","pass":"S3cretPass!"}')"
TOKEN=$(php -r 'echo json_decode(file_get_contents("/tmp/body"))->token;')
check "hash stored (not plain)" 0 "$(php -r '$p=new PDO("sqlite:/tmp/jwt_api_test.sqlite");echo str_starts_with($p->query("select pass from user")->fetchColumn(),"\$2y\$")?0:1;')"
check "me no token"            401 "$(code $B/me.php)"
check "me garbage token"       401 "$(code $B/me.php -H 'Authorization: Bearer abc.def.ghi')"
check "me ok"                  200 "$(code $B/me.php -H "Authorization: Bearer $TOKEN")"
check "whois no token"         401 "$(code $B/whois.php)"
check "whois list"             200 "$(code $B/whois.php -H "Authorization: Bearer $TOKEN")"
check "whois filter count=2"   2   "$(code $B/whois.php?domain_name=a.com -H "Authorization: Bearer $TOKEN" >/dev/null; php -r 'echo count(json_decode(file_get_contents("/tmp/body"),true)["data"]);')"
check "sqli payload is inert"  0   "$(code -G $B/whois.php --data-urlencode "domain_name=' OR '1'='1" -H "Authorization: Bearer $TOKEN" >/dev/null; php -r 'echo count(json_decode(file_get_contents("/tmp/body"),true)["data"]);')"
check "whois bad limit"        422 "$(code "$B/whois.php?limit=abc" -H "Authorization: Bearer $TOKEN")"
# alg=none / توکن دست‌کاری‌شده
FORGED=$(php -r '$h=rtrim(strtr(base64_encode(json_encode(["alg"=>"none","typ"=>"JWT"])),"+/","-_"),"=");$p=rtrim(strtr(base64_encode(json_encode(["user"=>"x","exp"=>time()+99,"iss"=>"http://localhost:18080","aud"=>"http://localhost:18080"])),"+/","-_"),"=");echo "$h.$p.";')
check "alg=none rejected"      401 "$(code $B/me.php -H "Authorization: Bearer $FORGED")"
TAMP="${TOKEN%.*}.AAAA"
check "bad signature rejected" 401 "$(code $B/me.php -H "Authorization: Bearer $TAMP")"
if [ "${SLOW:-0}" = 1 ]; then sleep $((JWT_TTL+7)); check "expired token" 401 "$(code $B/me.php -H "Authorization: Bearer $TOKEN")"; fi
exit $fail
