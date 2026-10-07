CREATE DATABASE IF NOT EXISTS workbridge;
CREATE DATABASE IF NOT EXISTS workbridge_test;
GRANT ALL PRIVILEGES ON workbridge.* TO 'wb_user'@'%';
GRANT ALL PRIVILEGES ON workbridge_test.* TO 'wb_user'@'%';
FLUSH PRIVILEGES;
