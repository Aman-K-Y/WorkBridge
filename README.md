# WorkBridge

A free, zero-commission freelance marketplace connecting clients and freelancers directly. Full-stack: Python, PHP, Java, MySQL, MongoDB.

## Architecture
![Architecture Diagram](docs/architecture-diagram.png)

This project consists of 5 main components:
- **Frontend (PHP)**: The web UI serving freelancers and clients on port 80.
- **Backend (Python/Flask)**: The main API handling users, jobs, messages, and business logic on port 5000.
- **Matching Service (Java/Spring Boot)**: A microservice computing job-to-freelancer skill overlap on port 8081.
- **MySQL**: Stores relational data (users, jobs, proposals, reviews).
- **MongoDB**: Stores document data (freelancer portfolios, chat messages).

## Prerequisites
- PHP 8.x + Apache/Nginx
- Python 3.10+
- Java 17+ and Maven
- MySQL 8+
- MongoDB 6+

## Setup & Configuration

1. **Environment Variables**:
   Copy `.env.example` to `.env` in the root folder and configure your credentials.
   ```bash
   cp .env.example .env
   ```

2. **Database Setup**:
   - Initialize MySQL schema and seed data (located in `database/`).
   - Run the MongoDB initialization script:
     ```bash
     cd mongo
     python init_collections.py
     ```

3. **Running the Python Backend**:
   ```bash
   cd backend
   pip install -r requirements.txt
   flask run --port=5000
   ```

4. **Running the Java Matching Service**:
   ```bash
   cd matching-service
   mvn spring-boot:run
   ```

5. **Running the PHP Frontend**:
   Serve the `frontend/` directory using your preferred web server (e.g. Apache/XAMPP or PHP built-in server).
   ```bash
   cd frontend
   php -S localhost:8000
   ```

## Testing

- **Backend (Python)**:
  ```bash
  cd backend
  python -m pyflakes .
  ```
- **Matching Service (Java)**:
  ```bash
  cd matching-service
  mvn clean verify
  ```

## Demo Accounts
Use the seeded accounts in the database for testing (e.g., client or freelancer profiles). Password for seeded demo users is standard (check database/seed_data.sql for emails).
