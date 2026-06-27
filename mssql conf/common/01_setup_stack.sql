IF NOT EXISTS (SELECT * FROM sys.databases WHERE name = 'stackDB')
BEGIN
    CREATE DATABASE stackDB;
END
GO

USE master;
GO

IF NOT EXISTS (SELECT * FROM sys.server_principals WHERE name = 'docker_user_lemp')
BEGIN
    CREATE LOGIN docker_user_lemp WITH PASSWORD = 'Docker_user_lemp123!', CHECK_POLICY = OFF;
END
GO

USE stackDB;
IF NOT EXISTS (SELECT * FROM sys.database_principals WHERE name = 'docker_user_lemp')
BEGIN
    CREATE USER docker_user_lemp FOR LOGIN docker_user_lemp;
    EXEC sp_addrolemember 'db_owner', 'docker_user_lemp';
END
GO
