# PHP JWT RESTful API

A simple PHP REST API that handles authentication with **JWT** (using [firebase/php-jwt](https://github.com/firebase/php-jwt) v6).

## Features

- User registration with hashed passwords (`password_hash` / `password_verify`)
- Login and JWT issuing (HS256) with `exp`, `nbf`, `iat`, `iss`, `aud` and `sub` claims
- Authentication middleware (`Authorization: Bearer <token>`)
- Protected endpoint for searching the `whois_log` table
- Prepared statements for every query (no SQL injection)
- Configuration via environment variables, no secrets in source code
- Docker + MySQL with automatic table creation

## Project Structure

```
.
├── public/               # webroot (only this folder is exposed)
│   ├── login.php         # POST  log in and get a token
│   ├── register.php      # POST  register a user
│   ├── me.php            # GET   current user info (token required)
│   ├── whois.php         # GET   whois log (token required)
│   ├── front/login.html  # sample login page
│   └── .htaccess
├── src/                  # Config, Database, Jwt, Auth, Request, Response
├── database/             # 01-schema.sql (user table), 02-whois_log.sql
├── tests/smoke.sh        # end-to-end tests
├── .env.example
├── Dockerfile
└── docker-compose.yml
```

## Installation

### With Docker (recommended)

```bash
cp .env.example .env
# Generate a secret and put it in .env as JWT_SECRET:
openssl rand -base64 32

docker compose up -d --build
```

The API is available at `http://localhost:10103`. Tables are created only on the first run (empty database). To recreate them: `docker compose down -v`.

### Without Docker

Requirements: PHP 8.1+, the `pdo_mysql` extension, Composer, MySQL/MariaDB.

```bash
composer install
cp .env.example .env          # then edit the values
mysql -u root -p api < database/01-schema.sql
mysql -u root -p api < database/02-whois_log.sql
php -S localhost:8000 -t public
```

> If you use Apache, point `DocumentRoot` to the `public` folder and enable `mod_rewrite` and `AllowOverride All`.

## Configuration (environment variables)

| Name | Description | Default |
|---|---|---|
| `DB_HOST`, `DB_PORT`, `DB_NAME` | Database connection | `localhost`, `3306`, `api` |
| `DB_USER`, `DB_PASS` | Database credentials | — |
| `JWT_SECRET` | Signing key, **at least 32 characters** (required) | — |
| `JWT_ISSUER` | Value of the token's `iss` and `aud` claims | `http://localhost` |
| `JWT_TTL` | Token lifetime in seconds | `1200` |
| `CORS_ALLOW_ORIGIN` | Allowed CORS origin (empty = disabled) | — |

## API

### `POST /register.php`

```json
{ "user": "shervin", "pass": "MyStrongPass1", "first_name": "Shervin", "family": "Tehrani", "email": "a@b.com" }
```

- `user`: 3–50 characters (letters, digits, `_ . -`); `pass`: 8–72 characters
- Success: `201` → `{"message":"User created","id":1}`
- Errors: `400` (invalid JSON), `409` (user already exists), `422` (invalid input)

### `POST /login.php`

```bash
curl -X POST http://localhost:10103/login.php \
  -H "Content-Type: application/json" \
  -d '{"user":"shervin","pass":"MyStrongPass1"}'
```

```json
{ "authorized": true, "token": "eyJ...", "token_type": "Bearer", "expires_in": 1200 }
```

Wrong credentials: `401` → `{"authorized":false,"error":"Invalid credentials"}`

### `GET /me.php`

```bash
curl http://localhost:10103/me.php -H "Authorization: Bearer YOUR_TOKEN"
```

```json
{ "id": "1", "user": "shervin", "expires_at": 1700001200 }
```

### `GET /whois.php`

Optional parameters: `domain_name` (exact match), `limit` (1–100, default 10).

```bash
curl "http://localhost:10103/whois.php?domain_name=pagac.biz&limit=5" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Error Codes

| Code | Meaning |
|---|---|
| 400 | Invalid JSON body or missing required field |
| 401 | Token missing, invalid or expired (a `WWW-Authenticate` header is sent) |
| 405 | HTTP method not allowed |
| 409 | Conflict (user already exists) |
| 422 | Invalid input value |
| 500 | Internal server error (details only in the server log) |

### Example Token Payload

```json
{
  "iss": "http://localhost:10103",
  "aud": "http://localhost:10103",
  "iat": 1700000000,
  "nbf": 1700000000,
  "exp": 1700001200,
  "sub": "1",
  "user": "shervin"
}
```

## Testing

```bash
composer install
bash tests/smoke.sh                    # uses SQLite, no MySQL needed
SLOW=1 JWT_TTL=2 bash tests/smoke.sh   # also tests token expiry
```

The tests cover registration, login, invalid/expired tokens, the `alg=none` attack, tampered signatures and SQL injection.

## Security Notes

- Never commit your `.env` file. If an old key exists in your git history, **rotate it** (earlier versions kept it in the source).
- Always use HTTPS in production.
- To prevent brute force, add rate limiting on `/login.php` at the reverse proxy level or with a tool like fail2ban.
- On the frontend, keep the token in memory or `sessionStorage`, and keep its lifetime short.

## Author

Shervin Tehrani
