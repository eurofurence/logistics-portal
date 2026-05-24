# Custom Laravel Commands

This wiki page documents the custom Artisan commands available in the Logistics Portal application.

## Role & User Management

### `update:roles`
**Description:** Reset the roles table and seed the new information into the database.
**Usage:** `php artisan update:roles {--force}`
**Details:** This command deletes all manually made changes to the `roles` table and reseeds it using the `RoleSeeder`. It asks for confirmation before proceeding.
*   `--force`: Use this flag when the application is in production to bypass the typical production seeder warning.

### `user:add_role`
**Description:** Add a role to a user.
**Usage:** `php artisan user:add_role {user} {role}`
**Arguments:**
*   `user`: The ID of the user.
*   `role`: The exact name of the role to assign.
**Details:** Assigns an existing role to an existing user by their ID.

### `whitelist:add`
**Description:** Adds a new email to the whitelist.
**Usage:** `php artisan whitelist:add {email}`
**Arguments:**
*   `email`: The email address to add to the whitelist.

---

## Backups & S3 Storage

### `backup:with-s3`
**Description:** Inserts files from S3 into the local backup and then executes the backup.
**Usage:** `php artisan backup:with-s3 {--only-download}`
**Details:** This command downloads all files from the S3 bucket to a local temporary folder, maintaining the folder structure, and then runs the Spatie backup process to include these files.
*   `--only-download`: Only downloads and extracts the files locally without running the Spatie backup.

### `backup:spatie-restore`
**Description:** Restore a Spatie backup from a specified file on the SFTP server.
**Usage:** `php artisan backup:spatie-restore {password} {--only-download}`
**Arguments:**
*   `password`: The password for the encrypted zip backup.
**Details:** This command connects to the SFTP server, downloads the latest backup archive, extracts it using the provided password, clears the current S3 bucket, and then uploads the extracted files to S3.
*   `--only-download`: Stops the process after downloading and extracting, without uploading to S3.

### `backup:delete-s3-backups`
**Description:** Deletes spatie backups from S3.
**Usage:** `php artisan backup:delete-s3-backups`
**Details:** Prompts for confirmation and then deletes all files located in the `/{app_name}` directory on S3.

### `s3:delete-files`
**Description:** Delete specific files from S3 storage.
**Usage:** `php artisan s3:delete-files`
**Details:** Currently, the files to be deleted are hardcoded within the command's `handle()` method.

---

## Cleanups & Maintenance

### `files:delete-old`
**Description:** Delete old temp files.
**Usage:** `php artisan files:delete-old`
**Details:** Deletes temporary Excel export files located in `/export/excel/tmp` on S3 that are older than 2 hours.

### `exports:delete-filament-exports`
**Description:** Deletes filament exports from S3.
**Usage:** `php artisan exports:delete-filament-exports`
**Details:** Prompts for confirmation and then deletes all files located in the `/filament_exports` directory on S3.
