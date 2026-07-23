# Security Policy

## Supported Versions

Only the latest stable release receives security fixes. Please make sure you are running the most recent version before reporting a vulnerability.

| Version | Supported |
|---------|-----------|
| Latest stable (`^11.0`) | ✅ |
| Older releases | ❌ |

Upgrade via Composer:

```bash
composer require krowinski/php-mysql-replication:^11.0
```

---

## Reporting a Vulnerability

**Do not open a public GitHub issue for security vulnerabilities.**

Please report them privately by emailing:

**kacper.rowinski@gmail.com**

Include as much detail as possible:

- Description of the vulnerability and its potential impact
- Steps to reproduce (proof-of-concept code if applicable)
- Affected version(s)
- Any suggested fix or mitigation

You should receive an acknowledgement within **72 hours**. If you do not hear back, follow up to ensure the message was received.

Fixes are released as patch versions and credited to the reporter (unless anonymity is requested).

---

## Security Considerations for Integrators

This library connects to MySQL as a replication slave and reads the binary log. Because of the nature of what it does, several security aspects require special attention in production deployments.

### 1. MySQL Credentials

Never hardcode credentials in source code. Use environment variables or a secrets manager:

```php
// ✅ Good
$config = (new ConfigBuilder())
    ->withUser(getenv('MYSQL_REPL_USER'))
    ->withPassword(getenv('MYSQL_REPL_PASSWORD'))
    ->withHost(getenv('MYSQL_HOST'))
    ->build();

// ❌ Bad
$config = (new ConfigBuilder())
    ->withUser('repl_user')
    ->withPassword('secret123')
    ->build();
```

### 2. MySQL Replication User — Principle of Least Privilege

The MySQL user used by this library requires the following privileges only:

```sql
GRANT REPLICATION SLAVE, REPLICATION CLIENT ON *.* TO 'repl_user'@'app_host';
GRANT SELECT ON `your_database`.* TO 'repl_user'@'app_host';
```

- Do **not** grant `SUPER`, `ALL PRIVILEGES`, or write permissions.
- Restrict the `@'host'` to the specific IP/hostname of the application server — never use `@'%'`.
- Create a dedicated user for this library; never reuse an application or admin account.

### 3. Change Default `slaveId` and `slaveUuid`

The library defaults to `slaveId = 666` and a fixed `slaveUuid`. These values must be unique across all replication slaves connected to the same MySQL master. In production, set them explicitly to avoid conflicts and to prevent fingerprinting:

```php
$config = (new ConfigBuilder())
    ->withSlaveId(12345)                               // Unique integer for this instance
    ->withSlaveUuid('a1b2c3d4-e5f6-7890-abcd-ef1234567890')  // Generate with uuidgen
    ->build();
```

### 4. Binlog Contains All Row Data — Including Sensitive Fields

`REPLICATION SLAVE` gives access to **every row written to the binary log** across all databases and tables (unless filtered by MySQL itself). This includes PII, passwords, tokens, and any other sensitive column values.

Mitigations:

- Use `databasesOnly` and `tablesOnly` config options to limit what your consumer receives:

  ```php
  $config = (new ConfigBuilder())
      ->withDatabasesOnly(['orders', 'products'])
      ->withTablesOnly(['orders.line_items'])
      ->build();
  ```

- Sanitize or redact sensitive columns before persisting or forwarding events downstream.
- Never log raw event payloads in production.

### 5. Use SSL/TLS for the MySQL Connection

If the application server and MySQL server are on separate hosts or traverse a shared network, enforce an encrypted connection. Configure `ssl_ca`, `ssl_cert`, and `ssl_key` in MySQL and require SSL for the replication user:

```sql
ALTER USER 'repl_user'@'app_host' REQUIRE SSL;
```

### 6. Protect the Running Process

The process that runs this library has read access to the entire binary log stream:

- Run it under a dedicated, unprivileged OS user.
- Do not expose its standard output, log files, or IPC channels to other processes.
- Restrict filesystem access so it cannot write to directories outside its working scope.

### 7. Binlog Position / GTID Persistence

If the process restarts, it resumes from a saved binlog position or GTID. Store this state securely:

- Do not store position data in a world-readable file.
- Protect the state storage (file, Redis, database) with appropriate permissions.
- A corrupted or tampered position can cause events to be replayed or skipped.

### 8. Dependency Management

Keep dependencies up to date. Run regularly:

```bash
composer audit
composer update
```

This covers known vulnerabilities in `doctrine/dbal`, `symfony/event-dispatcher`, and other dependencies.

---

## Scope

The following are considered in scope for security reports:

- Vulnerabilities in the PHP library code (parsing, connection handling, event deserialization)
- Insecure defaults that could lead to data exposure or privilege escalation
- Dependencies with a CVE that directly affect this library's attack surface

The following are **out of scope**:

- Vulnerabilities in MySQL/MariaDB server itself
- Issues in the integrator's application code or infrastructure
- General PHP or operating system vulnerabilities unrelated to this library

---
