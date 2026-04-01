-- Run this entire file in phpMyAdmin SQL tab
-- It adds all missing columns to your existing tables

-- Add missing columns to movies_details
ALTER TABLE movies_details
ADD COLUMN synopsis TEXT,
ADD COLUMN poster_path VARCHAR(255),
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Add missing columns to review_details
ALTER TABLE review_details
ADD COLUMN status ENUM('pending','approved') NOT NULL DEFAULT 'pending',
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Create uploads folder path reference (run this separately if needed)
-- mkdir uploads in your case_study folder manually
