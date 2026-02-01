# Google Credentials Storage Migration

Google OAuth credentials and tokens have been migrated to use Laravel's Storage facade for better security and consistency.

## What Changed

### Old Location (deprecated)
- Credentials: `etc/credentials.json`, `etc/credentials_{account}.json`
- Tokens: `storage/app/tokens/token_{account}.json`

### New Location
- Credentials: `storage/app/google/credentials.json`, `storage/app/google/credentials_{account}.json`
- Tokens: `storage/app/google/tokens/token_{account}.json`

## Migration

### Automatic Migration

Run the migration command to move existing files:

```bash
php artisan google:migrate-credentials
```

This will:
- Copy all `etc/credentials*.json` files to `storage/app/google/`
- Move all tokens from `storage/app/tokens/` to `storage/app/google/tokens/`
- Keep original files intact (you can delete them manually after verification)

### Manual Migration

If you prefer to migrate manually:

```bash
# Create directories
mkdir -p storage/app/google/tokens

# Move credentials
mv etc/credentials*.json storage/app/google/

# Move tokens (if any exist in old location)
mv storage/app/tokens/token_*.json storage/app/google/tokens/
```

## Setting Up New Credentials

For new accounts, place credentials at:

```
storage/app/google/credentials_{account}.json  # Account-specific (recommended)
storage/app/google/credentials.json            # Default (fallback)
```

Example for account "aqcom":
```bash
cp ~/Downloads/client_secret.json storage/app/google/credentials_aqcom.json
```

Then authenticate:
```bash
php artisan google:auth aqcom
```

## Security

All files in `storage/app/google/` are:
- Automatically excluded from version control (.gitignore)
- Protected by Laravel's storage permissions
- Accessed only through Laravel's Storage facade
- Tokens are encrypted using Laravel's Crypt facade

## Configuration

Update `.env` if using a custom credentials path:

```env
# Old (deprecated)
GOOGLE_CREDENTIALS_PATH=/path/to/etc/credentials.json

# New (relative to storage/app/)
GOOGLE_CREDENTIALS_PATH=google/credentials.json
```

## Verification

Check that authentication still works:

```bash
# Test existing account
php artisan google:auth aqcom

# Should show: "✓ Account 'aqcom' already has a valid token."
```

## Benefits

1. **Consistency**: All sensitive data in `storage/app/`
2. **Security**: Better file permissions and encryption
3. **Laravel Convention**: Uses Storage facade throughout
4. **Gitignore**: Automatic exclusion from version control
5. **Testing**: Easier to mock and test with Storage facade
