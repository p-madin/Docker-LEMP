
INSERT INTO sysConfig (scName, scValue) VALUES ('myDomain', 'localhost');
INSERT INTO sysConfig (scName, scValue) VALUES ('myDomainProtocol', 'https');
INSERT INTO sysConfig (scName, scValue) VALUES ('clientAddress', 'REMOTE_ADDR');

-- System Configuration Variables (Centralized Config)
INSERT INTO sysConfig (scName, scValue) VALUES ('EXTERNAL_PORT', '8443');
INSERT INTO sysConfig (scName, scValue) VALUES ('HOST_PROJECT_ROOT', '/home/phil/Workspace/Docker Workspace/Docker-LEMP');
INSERT INTO sysConfig (scName, scValue) VALUES ('TENANT_APP_IMAGE', 'local-dockerlempapp:latest');
INSERT INTO sysConfig (scName, scValue) VALUES ('TENANT_DB_IMAGE', 'local-dockerlempdb:latest');
INSERT INTO sysConfig (scName, scValue) VALUES ('TENANT_APP_VOLUME', '/home/phil/Workspace/Docker Workspace/Docker-LEMP/app:/var/www/html');
