db = db.getSiblingDB("workbridge");

db.messages.createIndex({ "job_id": 1 });
db.messages.createIndex({ "timestamp": 1 });

db.portfolios.createIndex({ "user_id": 1 }, { unique: true });

// Note: Sample data should be seeded by a dev script, not in init.js
// But if you want it behind a dev flag, you can't easily pass args to docker-entrypoint-initdb.d scripts.
// So we just create the indexes.
