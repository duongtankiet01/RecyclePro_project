# Controllers Structure

## 📁 Folder Organization

```
controllers/
├── api/                    # API Controllers (JSON responses)
│   ├── ApiAuthController.php
│   ├── ApiBusinessOrderController.php
│   ├── ApiCollectorController.php
│   ├── ApiCreditController.php
│   └── ApiWithdrawalController.php
│
├── web/                    # Web Controllers (HTML responses)
│   ├── AdminController.php
│   ├── AuthController.php
│   ├── CtvController.php
│   ├── DoanhNghiepController.php
│   ├── HoGiaDinhController.php
│   └── HomeController.php
│
├── webhook/                # External Webhook Controllers
│   └── WebhookSepayController.php
│
└── BaseController.php      # Base controller (all extend this)
```

## 🎯 Controller Types

### API Controllers (`api/`)
- **Purpose:** Handle AJAX requests, mobile app API
- **Response:** JSON format
- **Authentication:** Session or API token
- **Example:**
  ```php
  // api/ApiAuthController.php
  public function login() {
      echo json_encode(['success' => true, 'token' => $token]);
  }
  ```

### Web Controllers (`web/`)
- **Purpose:** Handle browser page requests
- **Response:** HTML (via views)
- **Authentication:** Session-based
- **Example:**
  ```php
  // web/HoGiaDinhController.php
  public function feed() {
      $this->render('ho-gia-dinh/feed', $data);
  }
  ```

### Webhook Controllers (`webhook/`)
- **Purpose:** Handle external service callbacks
- **Response:** JSON status
- **Authentication:** HMAC signature or API key
- **Example:**
  ```php
  // webhook/WebhookSepayController.php
  public function handle() {
      // Process SePay payment notification
  }
  ```

## 📊 MVC Compliance Status

### ✅ 100% MVC Compliant (0 SQL in controller)
- web/AdminController.php
- api/ApiAuthController.php
- api/ApiBusinessOrderController.php
- api/ApiCreditController.php
- api/ApiWithdrawalController.php
- web/CtvController.php
- web/DoanhNghiepController.php
- web/HoGiaDinhController.php
- web/HomeController.php
- web/AuthController.php

### ⚠️ Partial MVC (some SQL remaining)
- webhook/WebhookSepayController.php (external service)
- api/ApiCollectorController.php (topup queries)

## 🚀 Usage

### Routing
URL format: `index.php?controller={name}&action={method}`

**Examples:**
- Web: `?controller=ho-gia-dinh&action=feed`
  → Loads `web/HoGiaDinhController::feed()`
  
- API: `?controller=api-auth&action=login`
  → Loads `api/ApiAuthController::login()`
  
- Webhook: `?controller=webhook-sepay&action=handle`
  → Loads `webhook/WebhookSepayController::handle()`

### Autoloading
The `index.php` autoloader searches in this order:
1. `controllers/` (for BaseController)
2. `controllers/api/`
3. `controllers/web/`
4. `controllers/webhook/`
5. `models/`

## 📝 Naming Conventions

| Type | Pattern | Example |
|------|---------|---------|
| API Controller | `Api{Name}Controller.php` | `ApiAuthController.php` |
| Web Controller | `{Name}Controller.php` | `HoGiaDinhController.php` |
| Webhook Controller | `Webhook{Service}Controller.php` | `WebhookSepayController.php` |

## 🔧 Best Practices

1. **All controllers extend BaseController**
2. **Controllers only handle routing/response**
3. **Business logic goes in Models**
4. **Use models for all database operations**
5. **Keep controllers thin (<300 lines)**

## 📈 Refactoring Progress

**Phase 10 Completed:**
- 10/14 controllers: 100% MVC compliant
- 0 SQL statements in controllers
- All business logic in models
- Clean separation of concerns

**Last Updated:** 2025-11-02
