-- ============================================
-- WorkBridge Database Schema
-- Relational data: users, jobs, proposals, reviews
-- ============================================

DROP DATABASE IF EXISTS workbridge;
CREATE DATABASE workbridge;
USE workbridge;

-- ============================================
-- USERS
-- Stores both clients and freelancers.
-- Role determines what dashboard/permissions they get.
-- ============================================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('client', 'freelancer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- JOBS
-- Posted by clients. Freelancers browse and bid on these.
-- ============================================
CREATE TABLE jobs (
    job_id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    budget DECIMAL(10, 2) NOT NULL,
    category VARCHAR(100),
    required_skills VARCHAR(255),
    status ENUM('open', 'in_progress', 'completed') DEFAULT 'open',
    deadline DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ============================================
-- PROPOSALS
-- Freelancers submit these against a specific job.
-- A job can have many proposals; a proposal belongs to one job + one freelancer.
-- ============================================
CREATE TABLE proposals (
    proposal_id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    freelancer_id INT NOT NULL,
    bid_amount DECIMAL(10, 2) NOT NULL,
    cover_note TEXT,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE,
    FOREIGN KEY (freelancer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_bid_per_job (job_id, freelancer_id) -- one proposal per freelancer per job
);

-- ============================================
-- REVIEWS
-- Left after a job is marked completed.
-- reviewer = the one leaving the review, reviewee = the one being reviewed
-- (works both ways: client reviews freelancer, freelancer reviews client)
-- ============================================
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewee_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewee_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ============================================
-- INDEXES
-- Speed up the most common queries (filtering jobs, looking up proposals)
-- ============================================
CREATE INDEX idx_jobs_status ON jobs(status);
CREATE INDEX idx_jobs_category ON jobs(category);
CREATE INDEX idx_proposals_job ON proposals(job_id);
CREATE INDEX idx_proposals_freelancer ON proposals(freelancer_id);
CREATE INDEX idx_reviews_reviewee ON reviews(reviewee_id);
