# PostgreSQL Sequence Fix Documentation

## Problem Description

You encountered a **PostgreSQL unique constraint violation** error:

```
SQLSTATE[23505]: Unique violation: 7 ERROR: llave duplicada viola restricción de unicidad «compras_pkey»
DETAIL: Ya existe la llave (id)=(3).
```

This error occurs when the PostgreSQL sequence for auto-incrementing IDs gets out of sync with the actual data in the table.

## Root Cause

PostgreSQL uses sequences to generate auto-incrementing IDs. When data is:
- Manually inserted with specific IDs
- Bulk imported from another database
- Restored from a backup
- Or the sequence is manually reset incorrectly

The sequence can become out of sync, causing it to try to generate IDs that already exist in the table.

## Solution

### Option 1: Fix All Sequences (Recommended)

Run the following Artisan command to fix all table sequences in your database:

```bash
php artisan fix:sequences
```

This will automatically:
1. Scan all tables in your database
2. Find the maximum ID for each table
3. Reset the sequence to `max(id) + 1`
4. Skip tables without sequences (like pivot tables)

### Option 2: Fix a Specific Table

If you only want to fix a specific table (e.g., `compras`):

```bash
php artisan fix:sequences compras
```

Or use the dedicated command:

```bash
php artisan fix:compras-sequence
```

### Option 3: Manual SQL Fix

If you prefer to run SQL directly, you can use:

```sql
-- Fix compras table sequence
SELECT setval(
    pg_get_serial_sequence('compras', 'id'),
    COALESCE((SELECT MAX(id) FROM compras), 0) + 1,
    false
);
```

## Files Created

1. **`app/Console/Commands/FixAllSequences.php`**
   - Comprehensive command to fix all sequences
   - Usage: `php artisan fix:sequences [table?]`
   - Can fix all tables or a specific table

2. **`app/Console/Commands/FixComprasSequence.php`**
   - Dedicated command for the `compras` table
   - Usage: `php artisan fix:compras-sequence`

3. **`fix_compras_sequence.sql`**
   - SQL script for manual execution
   - Can be run directly in PostgreSQL client

## Prevention

To prevent this issue in the future:

1. **Avoid manual ID insertion**: Let PostgreSQL handle auto-incrementing IDs
2. **After bulk imports**: Always run `php artisan fix:sequences` to reset all sequences
3. **After database restore**: Run the fix command to ensure sequences are in sync
4. **Use migrations**: Always use Laravel migrations for schema changes

## Verification

After running the fix, you can verify the sequence is correct:

```sql
-- Check current sequence value
SELECT currval(pg_get_serial_sequence('compras', 'id'));

-- Check maximum ID in table
SELECT MAX(id) FROM compras;
```

The sequence value should be greater than the maximum ID.

## Testing

Try creating a new purchase (compra) to verify the fix worked:

1. Go to the purchases creation page
2. Fill in the required fields
3. Submit the form
4. The purchase should be created successfully without the unique constraint error

## Additional Notes

- The fix is safe to run multiple times
- It will not affect existing data
- Only resets the sequence to the correct value
- Tables without sequences (like pivot tables) are automatically skipped
- The command provides detailed output showing which sequences were fixed

## Error Messages Explained

- **"No sequence found for table"**: Normal for tables without auto-incrementing IDs (like pivot tables)
- **"Undefined column 'id'"**: Normal for tables that use composite keys or different primary key names
- **"✓ Fixed 'table_name' sequence"**: Success! The sequence was reset correctly

## Support

If you continue to experience issues after running the fix:

1. Check the Laravel logs: `storage/logs/laravel.log`
2. Verify database connection settings in `.env`
3. Ensure PostgreSQL user has sufficient permissions
4. Try running the fix command again with verbose output

## Related Files

- Controller: `app/Http/Controllers/CompraController.php` (line 401)
- Model: `app/Models/Compra.php`
- Migration: `database/migrations/2025_03_01_060430_create_compras_table.php`
