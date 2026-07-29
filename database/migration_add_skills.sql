-- ============================================
-- WorkBridge Migration: add required_skills to jobs
-- Run this once against your existing database — it adds the new
-- column without deleting any of your existing jobs/users/proposals.
-- ============================================

USE workbridge;

ALTER TABLE jobs ADD COLUMN required_skills VARCHAR(255) AFTER category;
