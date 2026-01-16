-- database/migrations/008_add_business_intelligence_role.sql
-- Add business_intelligence role to users table

ALTER TABLE users
MODIFY COLUMN role ENUM('admin', 'trader', 'guest', 'business_intelligence') DEFAULT 'guest';
