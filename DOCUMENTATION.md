# AZM999 Gaming Platform - Complete Documentation

## Table of Contents
1. [Project Overview](#project-overview)
2. [Database Schema](#database-schema)
3. [User Roles & Permissions](#user-roles--permissions)
4. [Project Flow](#project-flow)
5. [API Endpoints](#api-endpoints)
6. [Features](#features)
7. [Setup Instructions](#setup-instructions)
8. [Architecture](#architecture)

---

## Project Overview

AZM999 is a comprehensive gaming platform management system built with Laravel. It provides a multi-tier user hierarchy (Owner → Agent → Player) with role-based access control, wallet management, game integration, and comprehensive reporting capabilities.

### Technology Stack
- **Backend Framework**: Laravel 10.x
- **Database**: PostgreSQL
- **Authentication**: Laravel Sanctum
- **Wallet System**: Bavix Laravel Wallet
- **Frontend**: Blade Templates + AdminLTE
- **PHP Version**: 8.2+

---

## Database Schema

### Entity Relationship Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         USERS TABLE                             │
├─────────────────────────────────────────────────────────────────┤
│ PK  id (bigint)                                                 │
│     user_name (string, unique)                                  │
│     name (string)                                               │
│     email (string, unique)                                       │
│     password (string)                                           │
│     phone (string)                                              │
│     type (integer) → UserType enum (10=Owner, 20=Agent, 40=Player)│
│ FK  agent_id → users.id (self-referencing)                      │
│     status (integer, default=1)                                 │
│     referral_code (string)                                      │
│     site_link (string)                                          │
│     ...                                                         │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ 1:N
                              │
        ┌─────────────────────┼─────────────────────┐
        │                     │                     │
        ▼                     ▼                     ▼
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│   WALLETS    │    │ TRANSACTIONS │    │ TRANSFER_LOGS│
├──────────────┤    ├──────────────┤    ├──────────────┤
│ PK id        │    │ PK id        │    │ PK id        │
│ FK holder_id │    │ FK wallet_id │    │ FK from_user │
│    balance   │    │    type      │    │ FK to_user   │
│    slug      │    │    amount    │    │    amount    │
└──────────────┘    └──────────────┘    └──────────────┘
        │
        │ 1:N
        │
        ▼
┌──────────────┐
│ TRANSACTIONS │
└──────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                    ROLE-BASED ACCESS CONTROL                    │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ROLES TABLE          PERMISSIONS TABLE                         │
│  ┌─────────────┐      ┌──────────────────┐                      │
│  │ PK id       │      │ PK id            │                      │
│  │    title    │      │    title         │                      │
│  │             │      │    group          │                      │
│  └─────────────┘      └──────────────────┘                      │
│         │                    │                                   │
│         │ N:M                │ N:M                              │
│         │                    │                                   │
│         └──────────┬─────────┘                                  │
│                    │                                             │
│            PERMISSION_ROLE TABLE                                 │
│            ┌──────────────────┐                                 │
│            │ FK permission_id │                                 │
│            │ FK role_id       │                                 │
│            └──────────────────┘                                 │
│                    │                                             │
│                    │                                             │
│            ROLE_USER TABLE                                      │
│            ┌──────────────────┐                                 │
│            │ FK user_id       │                                 │
│            │ FK role_id       │                                 │
│            └──────────────────┘                                 │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                      GAMING TABLES                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  PLACE_BETS TABLE          LOG_BUFFALO_BETS TABLE               │
│  ┌──────────────────┐     ┌──────────────────┐                 │
│  │ PK id            │     │ PK id            │                 │
│  │ FK player_id     │     │ FK player_id     │                 │
│  │ FK player_agent_id│    │ FK player_agent_id│                │
│  │    member_account│     │    member_account│                 │
│  │    bet_amount    │     │    bet_amount    │                 │
│  │    prize_amount  │     │    win_amount    │                 │
│  │    wager_code    │     │    buffalo_game_id│                │
│  │    wager_status  │     │    status         │                 │
│  │    currency      │     │    ...            │                 │
│  └──────────────────┘     └──────────────────┘                 │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                    FINANCIAL REQUEST TABLES                     │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  DEPOSIT_REQUESTS TABLE    WITH_DRAW_REQUESTS TABLE             │
│  ┌──────────────────┐     ┌──────────────────┐                 │
│  │ PK id            │     │ PK id            │                 │
│  │ FK user_id       │     │ FK user_id       │                 │
│  │ FK agent_id      │     │ FK agent_id      │                 │
│  │ FK agent_payment │     │ FK payment_type_id│                │
│  │    amount        │     │    amount        │                 │
│  │    refrence_no   │     │    account_name  │                 │
│  │    status        │     │    account_number│                 │
│  │    image         │     │    status        │                 │
│  └──────────────────┘     └──────────────────┘                 │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                    CONTENT MANAGEMENT TABLES                    │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  BANNERS TABLE          PROMOTIONS TABLE    BANNER_TEXTS TABLE  │
│  ┌──────────────┐      ┌──────────────┐    ┌──────────────┐     │
│  │ PK id        │      │ PK id        │    │ PK id        │     │
│  │    title     │      │    title     │    │    text      │     │
│  │    image     │      │    content   │    │    ...       │     │
│  │    status    │      │    status    │    │              │     │
│  └──────────────┘      └──────────────┘    └──────────────┘     │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### Key Tables Description

#### Core Tables

**users**
- Central user table with self-referencing `agent_id` for hierarchy
- `type` field: 10 (Owner), 20 (Agent), 40 (Player)
- Supports multi-level user hierarchy

**wallets**
- Polymorphic relationship with users
- Stores balance as integer (supports float via accessor)
- One wallet per user

**transactions**
- All wallet transactions (deposits, withdrawals, transfers)
- Linked to wallets via `wallet_id`
- Stores metadata in JSON format

**transfer_logs**
- Records all transfers between users
- Tracks `from_user_id` and `to_user_id`
- Stores transfer type and metadata

#### Gaming Tables

**place_bets**
- Stores all game bets from various providers
- Supports multiple currencies (MMK, MMK2 with conversion)
- Tracks bet status, amounts, and game details

**log_buffalo_bets**
- Specific table for Buffalo Game integration
- Tracks player bets and wins
- Links to players and agents

#### Request Tables

**deposit_requests**
- Player deposit requests to agents
- Status workflow: pending → approved/rejected
- Stores payment proof images

**with_draw_requests**
- Player withdrawal requests to agents
- Includes bank account details
- Status workflow: pending → approved/rejected

#### RBAC Tables

**roles**
- Owner, Agent, Player roles

**permissions**
- Granular permissions grouped by feature
- Examples: `agent_view`, `player_create`, `banner_update`

**permission_role**
- Many-to-many: Permissions ↔ Roles

**role_user**
- Many-to-many: Users ↔ Roles

**permission_user**
- Direct user permissions (for Players)

---

## User Roles & Permissions

### Role Hierarchy

```
OWNER (Type: 10)
  │
  ├── Creates and manages → AGENTS
  │
  └── Has permissions:
      ├── Agent Management (CRUD)
      ├── Banner Management (CRUD)
      ├── Banner Text Management (CRUD)
      ├── Promotions Management (CRUD)
      ├── Slot Game Settings (View/Update)
      ├── Agent Wallet Operations (Deposit/Withdraw)
      └── Report Acceptance

AGENT (Type: 20)
  │
  ├── Created by → OWNER
  ├── Creates and manages → PLAYERS
  │
  └── Has permissions:
      ├── Player Management (Full CRUD)
      ├── Player Ban/Unban
      ├── Player Password Change
      ├── Bank Management (CRUD)
      ├── Deposit/Withdraw Requests (View/Process)
      └── Transaction Logs (View)

PLAYER (Type: 40)
  │
  ├── Created by → AGENT
  │
  └── Has permissions:
      ├── Profile View/Update
      └── Wallet View
```

### Permission Groups

#### Owner Permissions
- **Agent Management**: `agent_view`, `agent_create`, `agent_update`, `agent_delete`
- **Banner Management**: `banner_view`, `banner_create`, `banner_update`, `banner_delete`
- **Banner Text**: `banner_text_view`, `banner_text_create`, `banner_text_update`, `banner_text_delete`
- **Promotions**: `promotion_view`, `promotion_create`, `promotion_update`, `promotion_delete`
- **Slot Settings**: `slot_setting_view`, `slot_setting_update`
- **Agent Wallet**: `agent_wallet_deposit`, `agent_wallet_withdraw`
- **Reports**: `report_accept`

#### Agent Permissions
- **Player Management**: `player_view`, `player_create`, `player_update`, `player_delete`
- **Player Actions**: `player_ban`, `player_password_change`
- **Bank Management**: `bank_view`, `bank_create`, `bank_update`, `bank_delete`
- **Wallet Operations**: `agent_wallet_deposit`, `agent_wallet_withdraw` (for requests)

#### Player Permissions
- **Self-Service**: `player_profile_view`, `player_profile_update`, `player_wallet_view`

### Permission Logic

```php
// Permission Check Flow:
1. If user has Role 'Owner' → All permissions granted
2. If user has Role 'Agent' → All permissions granted
3. If user has Role 'Player' → Check specific permissions from permission_user table
```

---

## Project Flow

### User Registration & Authentication Flow

```
┌─────────────┐
│   Login      │
└──────┬───────┘
       │
       ▼
┌─────────────────┐
│ Check Credentials│
│ (user_name + pwd)│
└──────┬──────────┘
       │
       ├─── Valid ──→ Check Status ──→ Check Password Changed
       │                                    │
       │                                    ├─── Not Changed ──→ Redirect to Change Password
       │                                    │
       │                                    └─── Changed ──→ Login Success ──→ Dashboard
       │
       └─── Invalid ──→ Show Error
```

### User Creation Flow

```
OWNER creates AGENT:
┌─────────────┐
│ Owner       │
└──────┬──────┘
       │
       ▼
┌─────────────────┐
│ Create Agent    │
│ - Set type=20   │
│ - Set agent_id  │
│ - Assign role   │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│ Create Wallet   │
│ (Initial Balance)│
└─────────────────┘

AGENT creates PLAYER:
┌─────────────┐
│ Agent       │
└──────┬──────┘
       │
       ▼
┌─────────────────┐
│ Create Player   │
│ - Set type=40   │
│ - Set agent_id  │
│ - Assign role   │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│ Transfer Funds  │
│ Agent → Player  │
└─────────────────┘
```

### Wallet Transaction Flow

```
DEPOSIT REQUEST:
Player ──→ Creates Request ──→ Agent Reviews ──→ Approve/Reject
                                              │
                                              ├─── Approve ──→ Transfer Agent → Player
                                              │
                                              └─── Reject ──→ Notify Player

WITHDRAW REQUEST:
Player ──→ Creates Request ──→ Agent Reviews ──→ Approve/Reject
                                              │
                                              ├─── Approve ──→ Transfer Player → Agent
                                              │
                                              └─── Reject ──→ Notify Player

DIRECT TRANSFER (Agent → Player):
Agent ──→ Cash In ──→ Transfer Funds ──→ Update Wallets ──→ Log Transaction

DIRECT TRANSFER (Player → Agent):
Agent ──→ Cash Out ──→ Transfer Funds ──→ Update Wallets ──→ Log Transaction
```

### Game Integration Flow

```
BUFFALO GAME:
┌─────────────┐
│ Player      │
│ Launches    │
│ Game        │
└──────┬──────┘
       │
       ▼
┌─────────────────┐
│ Generate Auth   │
│ Token & Game URL│
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│ Player Plays    │
│ & Places Bets   │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│ Game Provider   │
│ Webhook Callback│
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│ Update Balance  │
│ Log Transaction │
│ Store Bet Data  │
└─────────────────┘
```

### Reporting Flow

```
OWNER VIEW:
┌─────────────┐
│ Owner       │
│ Dashboard   │
└──────┬──────┘
       │
       ├─── View All Agents
       ├─── View All Players
       ├─── View All Transactions
       └─── View Reports (Grouped by Agent)

AGENT VIEW:
┌─────────────┐
│ Agent       │
│ Dashboard   │
└──────┬──────┘
       │
       ├─── View Own Players Only
       ├─── View Own Transactions
       ├─── View Player Reports
       └─── View Win/Lose Stats
```

---

## API Endpoints

### Authentication Endpoints

```
POST   /api/v1/auth/login
POST   /api/v1/auth/register
POST   /api/v1/auth/logout
POST   /api/v1/auth/change-password
```

### Game Integration Endpoints

#### Buffalo Game
```
GET    /api/v1/buffalo-game/user-balance/{uid}
POST   /api/v1/buffalo-game/change-balance
GET    /api/v1/buffalo-game/generate-auth
GET    /api/v1/buffalo-game/generate-url
POST   /api/v1/buffalo-game/launch
GET    /api/v1/buffalo-game/proxy/{path}
```

#### PoneWine Provider
```
POST   /api/ponewine/client-balance-update
```

### Admin Panel Routes

#### Agent Management (Owner Only)
```
GET    /admin/agent                    - List all agents
POST   /admin/agent                    - Create agent
GET    /admin/agent/{id}               - Show agent
GET    /admin/agent/{id}/edit         - Edit agent
PUT    /admin/agent/{id}               - Update agent
DELETE /admin/agent/{id}               - Delete agent
GET    /admin/agent-cash-in/{id}       - Agent deposit form
POST   /admin/agent-cash-in/{id}       - Process agent deposit
GET    /admin/agent/cash-out/{id}      - Agent withdraw form
POST   /admin/agent/cash-out/update/{id} - Process agent withdraw
PUT    /admin/agent/{id}/ban           - Ban/unban agent
GET    /admin/agent-changepassword/{id} - Change agent password form
POST   /admin/agent-changepassword/{id} - Update agent password
```

#### Player Management (Agent)
```
GET    /admin/players                  - List players
GET    /admin/players/{player}        - Show player
GET    /admin/players/{player}/edit   - Edit player
PUT    /admin/players/{player}        - Update player
GET    /admin/agent/players/create     - Create player form
POST   /admin/agent/players            - Store player
GET    /admin/player-cash-in/{player} - Player deposit form
POST   /admin/player-cash-in/{player} - Process player deposit
GET    /admin/player/cash-out/{player} - Player withdraw form
POST   /admin/player-cash-out/update/{player} - Process player withdraw
PUT    /admin/player/{id}/ban         - Ban/unban player
GET    /admin/player-changepassword/{id} - Change player password
POST   /admin/player-changepassword/{id} - Update player password
GET    /admin/player/{player}/report  - Player report
```

#### Financial Requests (Agent Only)
```
GET    /admin/finicialdeposit         - List deposit requests
GET    /admin/finicialdeposit/{id}     - View deposit request
POST   /admin/finicialdeposit/{id}     - Approve deposit
POST   /admin/finicialdeposit/reject/{id} - Reject deposit
GET    /admin/finicialdeposit/{id}/log - Deposit log

GET    /admin/finicialwithdraw        - List withdraw requests
POST   /admin/finicialwithdraw/{id}    - Approve withdraw
POST   /admin/finicialwithdraw/reject/{id} - Reject withdraw
GET    /admin/finicialwithdraw/{id}    - Withdraw log
```

#### Reports
```
GET    /admin/buffalo-game/report     - Buffalo game report
GET    /admin/buffalo-game/report/{id} - Detailed report
GET    /admin/transfer-logs           - Transaction logs
GET    /admin/playertransferlog/{id}  - Player transfer log
```

#### Content Management (Owner Only)
```
# Banners
GET    /admin/banners                 - List banners
POST   /admin/banners                 - Create banner
PUT    /admin/banners/{id}            - Update banner
DELETE /admin/banners/{id}            - Delete banner

# Promotions
GET    /admin/promotions              - List promotions
POST   /admin/promotions              - Create promotion
PUT    /admin/promotions/{id}        - Update promotion
DELETE /admin/promotions/{id}         - Delete promotion

# Banner Texts
GET    /admin/text                    - List banner texts
POST   /admin/text                    - Create banner text
PUT    /admin/text/{id}               - Update banner text
DELETE /admin/text/{id}               - Delete banner text
```

---

## Features

### Core Features

1. **Multi-Tier User Management**
   - Owner → Agent → Player hierarchy
   - Self-referencing user table
   - Role-based access control

2. **Wallet System**
   - Bavix Laravel Wallet integration
   - Float support via HasWalletFloat trait
   - Transaction logging
   - Balance tracking

3. **Financial Management**
   - Deposit/Withdraw requests
   - Direct transfers (Cash In/Out)
   - Transaction history
   - Multi-currency support (MMK, MMK2)

4. **Game Integration**
   - Buffalo Game provider
   - PoneWine provider
   - Webhook callbacks
   - Balance synchronization

5. **Reporting System**
   - Win/Lose calculations
   - Player reports
   - Agent reports
   - Transaction logs
   - Buffalo game reports

6. **Content Management**
   - Banner management
   - Promotions
   - Banner texts
   - Video ads

7. **Bank Management**
   - Bank account management
   - Payment types
   - Deposit/Withdraw configurations

### Security Features

1. **Authentication**
   - Laravel Sanctum for API
   - Session-based for web
   - Password hashing (bcrypt)

2. **Authorization**
   - Role-based permissions
   - Middleware protection
   - Gate policies

3. **Data Protection**
   - CSRF protection
   - SQL injection prevention (Eloquent ORM)
   - XSS protection (Blade escaping)

---

## Setup Instructions

### Prerequisites

- PHP 8.2 or higher
- PostgreSQL 10+
- Composer
- Node.js & NPM (for assets)

### Installation Steps

1. **Clone Repository**
```bash
git clone <repository-url>
cd azm_999_base_project
```

2. **Install Dependencies**
```bash
composer install
npm install
```

3. **Environment Configuration**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure .env**
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=azm999_db
DB_USERNAME=your_username
DB_PASSWORD=your_password

APP_URL=http://localhost:8000
```

5. **Run Migrations**
```bash
php artisan migrate
```

6. **Seed Database**
```bash
php artisan db:seed
```

This will seed:
- Roles (Owner, Agent, Player)
- Permissions
- Role-Permission mappings
- Initial users (Owner, Agents, Players)

7. **Create Storage Link**
```bash
php artisan storage:link
```

8. **Build Assets**
```bash
npm run build
# or for development
npm run dev
```

9. **Start Development Server**
```bash
php artisan serve
```

### Default Credentials

After seeding, default users are created:
- **Owner**: Check `database/seeders/UsersTableSeeder.php`
- **Agent**: Check `database/seeders/UsersTableSeeder.php`
- **Player**: Check `database/seeders/UsersTableSeeder.php`

### Database Seeding Order

```bash
php artisan db:seed --class=RolesTableSeeder
php artisan db:seed --class=PermissionsTableSeeder
php artisan db:seed --class=PermissionRoleTableSeeder
php artisan db:seed --class=UsersTableSeeder
php artisan db:seed --class=PaymentTypeTableSeeder
```

---

## Architecture

### Directory Structure

```
azm_999_base_project/
├── app/
│   ├── Enums/
│   │   └── UserType.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   │   ├── AgentController.php
│   │   │   │   ├── PlayerController.php
│   │   │   │   ├── BuffaloGame/
│   │   │   │   │   └── BuffaloReportController.php
│   │   │   │   └── ...
│   │   │   └── HomeController.php
│   │   ├── Middleware/
│   │   │   ├── CheckPermission.php
│   │   │   ├── CheckBanned.php
│   │   │   └── AuthGates.php
│   │   └── Requests/
│   ├── Models/
│   │   ├── User.php
│   │   ├── Admin/
│   │   │   ├── Role.php
│   │   │   └── Permission.php
│   │   └── ...
│   └── Services/
│       ├── WalletService.php
│       ├── BuffaloGameService.php
│       └── ...
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   └── views/
│       ├── layouts/
│       │   └── master.blade.php
│       ├── admin/
│       │   ├── dashboard.blade.php
│       │   ├── agent/
│       │   └── player/
│       └── auth/
│           └── login.blade.php
├── routes/
│   ├── web.php
│   ├── admin.php
│   └── api.php
└── public/
```

### Middleware Stack

```
Request
  │
  ├──→ Authenticate (auth)
  │
  ├──→ CheckBanned (checkBanned)
  │     └──→ Check if user status = 0
  │
  ├──→ AuthGates (web middleware group)
  │     └──→ Define permissions based on roles
  │
  └──→ CheckPermission (permission:xxx)
        └──→ Verify user has required permission
```

### Service Layer

**WalletService**
- Handles all wallet operations
- Deposit, Withdraw, Transfer
- Transaction logging
- Balance updates

**BuffaloGameService**
- Game URL generation
- Token generation/verification
- UID management

**ShanTransactionService**
- Shan game transactions
- Provider notifications

### Key Design Patterns

1. **Repository Pattern**: Models act as repositories
2. **Service Layer**: Business logic in Services
3. **Middleware Pattern**: Request filtering
4. **Enum Pattern**: Type safety with UserType enum
5. **RBAC Pattern**: Role-based access control

---

## Additional Notes

### Currency Conversion

The system supports MMK2 currency which requires conversion:
- MMK2 amounts are multiplied by 1000 when calculating totals
- Conversion happens in queries and reports

### Wallet Balance Storage

- Balances stored as integers (in smallest currency unit)
- Displayed as floats via `balanceFloat` accessor
- Supports decimal precision via `decimal_places` field

### Transaction Types

- `deposit`: Money added to wallet
- `withdraw`: Money removed from wallet
- `credit_transfer`: Transfer between users
- `debit_transfer`: Reverse transfer

### User Status

- `status = 1`: Active user
- `status = 0`: Banned/Inactive user

### Password Management

- `is_changed_password = 0`: Must change password on first login
- `is_changed_password = 1`: Password already changed

---

## Support & Maintenance

### Logging

- Application logs: `storage/logs/laravel.log`
- Transaction logs: `transaction_logs` table
- User activity logs: `user_logs` table

### Common Commands

```bash
# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize
php artisan optimize

# Check routes
php artisan route:list

# Run tests
php artisan test
```

---

## Version History

- **v1.0.0** - Initial release
  - Multi-tier user management
  - Wallet system
  - Game integrations
  - Reporting system
  - Role-based access control

---

**Documentation Last Updated**: 2025-01-XX
**Project Version**: 1.0.0

