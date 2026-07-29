-- ============================================
-- WorkBridge Seed Data
-- Sample records so you can test the app immediately
-- Passwords below are placeholders — real hashed passwords
-- will be generated once the auth system (Python backend) is built.
-- ============================================

USE workbridge;

-- --------- USERS ---------
INSERT INTO users (name, email, password_hash, role) VALUES
('Aditi Sharma', 'client@demo.com', 'PLACEHOLDER_HASH', 'client'),
('Rohan Mehta', 'freelancer@demo.com', 'PLACEHOLDER_HASH', 'freelancer'),
('Priya Nair', 'priya.client@demo.com', 'PLACEHOLDER_HASH', 'client'),
('Karan Verma', 'karan.dev@demo.com', 'PLACEHOLDER_HASH', 'freelancer'),
('Simran Kaur', 'simran.design@demo.com', 'PLACEHOLDER_HASH', 'freelancer');

-- --------- JOBS ---------
INSERT INTO jobs (client_id, title, description, budget, category, status, deadline) VALUES
(1, 'Build a responsive portfolio website', 'Need a clean, modern portfolio site with 5 pages, mobile-responsive.', 15000.00, 'Web Development', 'open', '2026-08-15'),
(1, 'Logo design for a coffee brand', 'Looking for a minimalist logo, 3 concepts to choose from.', 3000.00, 'Graphic Design', 'open', '2026-08-05'),
(3, 'Fix bugs in existing Python Flask app', 'App has some API errors on the /login route, needs debugging.', 5000.00, 'Web Development', 'open', '2026-07-30'),
(3, 'Write product descriptions for e-commerce store', '20 products, need SEO-friendly descriptions.', 4000.00, 'Content Writing', 'completed', '2026-07-10');

-- --------- PROPOSALS ---------
INSERT INTO proposals (job_id, freelancer_id, bid_amount, cover_note, status) VALUES
(1, 2, 14000.00, 'I have built 10+ portfolio sites, happy to share examples.', 'pending'),
(1, 4, 13500.00, 'Full-stack dev, can deliver in 5 days.', 'pending'),
(2, 5, 2800.00, 'I specialize in minimalist branding, portfolio attached.', 'accepted'),
(3, 4, 4500.00, 'Experienced with Flask, can start immediately.', 'accepted');

-- --------- REVIEWS ---------
-- Job 4 (product descriptions) was completed, so it has reviews both ways
INSERT INTO reviews (job_id, reviewer_id, reviewee_id, rating, comment) VALUES
(4, 3, 2, 5, 'Excellent work, delivered on time and descriptions were great quality.'),
(4, 2, 3, 5, 'Clear instructions and paid promptly. Great client to work with.');
