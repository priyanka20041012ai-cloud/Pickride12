-- Run this ONLY if your `pickride` database already exists from before
-- (i.e. you already ran the old database.sql and don't want to lose your
-- data). It just adds the two new columns drivers need to log in.
--
--   mysql -u root -p pickride < migration_driver_login.sql
--
-- If you're setting up fresh, just use database.sql — it already
-- includes these columns, so you don't need this file.

USE pickride;

ALTER TABLE drivers
    ADD COLUMN username VARCHAR(60) UNIQUE AFTER status,
    ADD COLUMN password_hash VARCHAR(255) AFTER username;
