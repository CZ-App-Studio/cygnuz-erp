## Cygnuz ERP

### By CZ App Studio [https://czappstudio.com]

```sh
composer update --ignore-platform-reqs
```

## DB Migration and Data Seeding

For complete setup with migrations and seeding:

```sh
# Development/Demo Environment
php artisan migrate:erp --seed-demo

# Production Environment
php artisan migrate:erp --seed-production
```

For detailed seeding documentation, see [docs/SEEDING_GUIDE.md](docs/SEEDING_GUIDE.md)

```sh
npm run serve
or
php artisan serve --host=192.168.29.192 --port=44313
```

- Run API Document Generator

```sh
php artisan l5-swagger:generate

http://127.0.0.1:8000/docs
http://127.0.0.1:8000/api/documentation
```

## Module Management

### Module Toggle Utility

A powerful command-line utility for managing module states, particularly useful for testing core functionality without non-core modules.

#### Quick Usage

```bash
# List all modules with their status
php artisan module:toggle list

# Disable all non-core modules for testing
php artisan module:toggle disable-non-core

# Re-enable all non-core modules
php artisan module:toggle enable-non-core

# Backup current module status
php artisan module:toggle backup

# Restore previous module status
php artisan module:toggle restore
```

#### Available Actions

| Action | Description | Example |
|--------|-------------|---------|
| `list` | Display all modules with status | `php artisan module:toggle list` |
| `enable-non-core` | Enable all non-core modules | `php artisan module:toggle enable-non-core` |
| `disable-non-core` | Disable all non-core modules | `php artisan module:toggle disable-non-core` |
| `enable-all` | Enable ALL modules (including core) | `php artisan module:toggle enable-all --force` |
| `disable-all` | Disable ALL modules (dangerous!) | `php artisan module:toggle disable-all --force` |
| `backup` | Create backup of current status | `php artisan module:toggle backup` |
| `restore` | Restore from backup | `php artisan module:toggle restore` |

#### Options

- `--dry-run` - Preview changes without applying them
- `--force` - Skip confirmation prompts
- `--exclude=Module1 --exclude=Module2` - Exclude specific modules from operation
- `--include=Module1 --include=Module2` - Only operate on specific modules

#### Examples

```bash
# Test core modules only
php artisan module:toggle disable-non-core --force

# Disable non-core except specific modules
php artisan module:toggle disable-non-core --exclude=Calendar --exclude=Notes

# Enable specific modules only
php artisan module:toggle enable-non-core --include=Calendar --include=Kanban

# Preview what would be disabled
php artisan module:toggle disable-non-core --dry-run

# Backup before major changes
php artisan module:toggle backup
php artisan module:toggle disable-all --force
# ... do testing ...
php artisan module:toggle restore
```

#### Testing Workflow

```bash
# 1. Backup current state
php artisan module:toggle backup

# 2. Disable non-core modules for core testing
php artisan module:toggle disable-non-core --force

# 3. Run your tests
php artisan test

# 4. Restore original state
php artisan module:toggle restore
```

### Module Seeding

For individual module seeding:

```bash
php artisan module:seed WMSInventoryCore
php artisan module:seed CRMCore
php artisan module:seed MultiCurrency
php artisan module:seed Calendar
php artisan module:seed PMCore
php artisan module:seed HRCore
```

## TODO

- File Manager Storage Unification S3, disk so on
- Dynamic Drag and Drop Workflow
- AI Agents & Features
- PDF reader image reader Agent (Auto PO creation and so on)
-

//Flow

# Claude Flow generated files

.claude-flow/
.claude/settings.local.json
.mcp.json
claude-flow.config.json
.swarm/
.hive-mind/
memory/claude-flow-data.json
memory/sessions/_
!memory/sessions/README.md
memory/agents/_
!memory/agents/README.md
coordination/memory*bank/*
coordination/subtasks/_
coordination/orchestration/\*
_.db
_.db-journal
_.db-wal
_.sqlite
_.sqlite-journal
\_.sqlite-wal
claude-flow
claude-flow.bat
claude-flow.ps1
hive-mind-prompt-\*.txt
