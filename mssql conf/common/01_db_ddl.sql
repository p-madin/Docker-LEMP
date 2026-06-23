IF NOT EXISTS (SELECT * FROM sys.databases WHERE name = 'stackDB')
BEGIN
    CREATE DATABASE stackDB;
END
GO

USE master;
GO

IF NOT EXISTS (SELECT * FROM sys.server_principals WHERE name = 'docker_user_lemp')
BEGIN
    CREATE LOGIN docker_user_lemp WITH PASSWORD = 'docker_user_lemp', CHECK_POLICY = OFF;
END
GO

USE stackDB;
GO

IF NOT EXISTS (SELECT * FROM sys.database_principals WHERE name = 'docker_user_lemp')
BEGIN
    CREATE USER docker_user_lemp FOR LOGIN docker_user_lemp;
    EXEC sp_addrolemember 'db_owner', 'docker_user_lemp';
END
GO

CREATE TABLE appUsers(
    auPK INT NOT NULL IDENTITY(1,1),
    name VARCHAR(32) NOT NULL,
    age INT NOT NULL,
    city VARCHAR(32) NOT NULL,
    username VARCHAR(16) NOT NULL,
    password VARCHAR(256) NOT NULL,
    email VARCHAR(32) NOT NULL,
    dateAdded DATETIME NOT NULL DEFAULT GETDATE(),
    dateVerified DATETIME,
    verified BIT NOT NULL DEFAULT 0,
    PRIMARY KEY(auPK)
);

CREATE TABLE tblPages (
    pagPK INT NOT NULL IDENTITY(1,1),
    pagCreated DATETIME NOT NULL DEFAULT GETDATE(),
    pagUpdated DATETIME NULL,
    pagDeleted DATETIME NULL,
    pagAuthorFK INT NOT NULL,
    pagTitle VARCHAR(128) NOT NULL,
    PRIMARY KEY(pagPK)
);

CREATE TABLE tblElements (
    elePK INT NOT NULL IDENTITY(1,1),
    eleType VARCHAR(32) NOT NULL,
    eleContent VARCHAR(MAX) NULL,
    eleCSSClasses VARCHAR(255) NULL,
    eleParentFK INT NULL DEFAULT NULL,
    eleCreated DATETIME NOT NULL DEFAULT GETDATE(),
    PRIMARY KEY(elePK),
    FOREIGN KEY(eleParentFK) REFERENCES tblElements(elePK)
);

CREATE TABLE brgPageElements (
    pelPK INT NOT NULL IDENTITY(1,1),
    pelPageFK INT NOT NULL,
    pelElementFK INT NOT NULL,
    pelOrder INT NOT NULL,
    PRIMARY KEY(pelPK),
    FOREIGN KEY(pelPageFK) REFERENCES tblPages(pagPK) ON DELETE CASCADE,
    FOREIGN KEY(pelElementFK) REFERENCES tblElements(elePK) ON DELETE CASCADE
);

CREATE TABLE tblSession(
    sessPK INT NOT NULL IDENTITY(1,1),
    sessCreated DATETIME NOT NULL DEFAULT GETDATE(),
    sessUpdated DATETIME NULL,
    sessDeleted DATETIME NULL,
    sessChars VARCHAR(64) NOT NULL,
    sessUser INT NULL,
    sessTransactionActive INT NOT NULL,
    PRIMARY KEY(sessPK)
);

CREATE TABLE tblSessionAtt(
    sattPK INT NOT NULL IDENTITY(1,1),
    sattSessionFK INT NOT NULL,
    sattDisc CHAR(1) NOT NULL,
    sattKey VARCHAR(32) NOT NULL,
    sattPrimaryValueFK INT NOT NULL,
    PRIMARY KEY(sattPK)
);

CREATE TABLE tblSessionAttValue(
    sattvPK INT NOT NULL IDENTITY(1,1),
    sattvAttFK INT NOT NULL,
    sattvValueFK INT NULL,
    sattvValue VARCHAR(128) NULL,
    PRIMARY KEY(sattvPK)
);

CREATE TABLE sysConfig(
    scPK INT NOT NULL IDENTITY(1,1),
    scName VARCHAR(32) NOT NULL,
    scValue VARCHAR(128) NOT NULL,
    PRIMARY KEY(scPK)
);

CREATE TABLE tblNavBar(
    nbPK INT NOT NULL IDENTITY(1,1),
    nbText VARCHAR(32) NOT NULL,
    nbDiscriminator CHAR(1) NOT NULL,
    nbPageFK INT NULL,
    nbPath VARCHAR(64) NULL,
    nbControllerClass VARCHAR(64) NULL,
    nbProtected BIT NOT NULL,
    nbOrder INT NOT NULL,
    nbParentFK INT NULL DEFAULT NULL,
    PRIMARY KEY(nbPK),
    FOREIGN KEY(nbParentFK) REFERENCES tblNavBar(nbPK),
    FOREIGN KEY(nbPageFK) REFERENCES tblPages(pagPK) ON DELETE SET NULL
);

CREATE UNIQUE INDEX idx_session_chars ON tblSession(sessChars);
CREATE INDEX idx_session_att_lookup ON tblSessionAtt(sattSessionFK, sattKey, sattDisc);
CREATE INDEX idx_session_val_lookup ON tblSessionAttValue(sattvAttFK);

CREATE TABLE httpAction(
    haPK INT NOT NULL IDENTITY(1,1),
    haDate DATETIME NOT NULL DEFAULT GETDATE(),
    haSessionFK INT NOT NULL,
    haUserFK INT NULL,
    haIP VARCHAR(45) NOT NULL,
    haURL VARCHAR(512) NOT NULL,
    haReferrer VARCHAR(512) NULL,
    haMethod VARCHAR(8) NOT NULL,
    haUserAgent VARCHAR(512) NOT NULL,
    haHeaders VARCHAR(MAX) NULL,
    haWafRuleTriggered VARCHAR(255) NULL,
    haWafPayload VARCHAR(MAX) NULL,
    PRIMARY KEY(haPK)
);

CREATE TABLE phpErrorLog (
  pelPK INT NOT NULL IDENTITY(1,1),
  pelTimestamp DATETIME NOT NULL DEFAULT GETDATE(),
  pelSeverity VARCHAR(32) NOT NULL,
  pelMessage VARCHAR(MAX) NOT NULL,
  pelFile VARCHAR(512) NOT NULL,
  pelLine INT NOT NULL,
  PRIMARY KEY(pelPK)
);

CREATE TABLE tblForm(
    tfPK INT NOT NULL IDENTITY(1,1),
    tfName VARCHAR(32) NOT NULL,
    tfAction VARCHAR(255) NULL,
    tfReadOnly BIT NOT NULL DEFAULT 0,
    PRIMARY KEY(tfPK)
);

CREATE TABLE tblColumns(
    tcPK INT NOT NULL IDENTITY(1,1),
    tcFormFK INT NOT NULL,
    tcName VARCHAR(32) NOT NULL,
    tcLabel VARCHAR(64) NOT NULL,
    tcType VARCHAR(32) NOT NULL,
    tcRules VARCHAR(MAX) NOT NULL,
    tcOrder INT NOT NULL,
    PRIMARY KEY(tcPK),
    FOREIGN KEY(tcFormFK) REFERENCES tblForm(tfPK) ON DELETE CASCADE
);

CREATE TABLE banned_ips(
    biPK INT NOT NULL IDENTITY(1,1),
    biIP VARCHAR(45) NOT NULL,
    biReason VARCHAR(255) NOT NULL,
    biExpires DATETIME NOT NULL,
    biDateAdded DATETIME NOT NULL DEFAULT GETDATE(),
    PRIMARY KEY(biPK)
);

CREATE TABLE tblEventStore(
    evsPK INT NOT NULL IDENTITY(1,1),
    evsAggregateFK INT NULL,
    evsEventType VARCHAR(128) NOT NULL,
    evsPayload VARCHAR(MAX) NOT NULL,
    evsPreviousPayload VARCHAR(MAX) NULL,
    evsStatus VARCHAR(32) NOT NULL DEFAULT 'pending',
    evsUserFK INT NULL,
    evsIsReversal BIT NOT NULL DEFAULT 0,
    evsPredecessorFK INT NULL,
    evsCreatedAt DATETIME NOT NULL DEFAULT GETDATE(),
    PRIMARY KEY(evsPK)
);

CREATE TABLE tblAnalytics (
    anaPK INT NOT NULL IDENTITY(1,1),
    anaLabel VARCHAR(32) NOT NULL,
    anaValue INT NOT NULL,
    anaCategory VARCHAR(32) NOT NULL,
    anaDate DATETIME NOT NULL DEFAULT GETDATE(),
    PRIMARY KEY(anaPK)
);

CREATE TABLE absChildServices (
    csPK INT NOT NULL IDENTITY(1,1),
    csCreatedDate DATETIME NOT NULL DEFAULT GETDATE(),
    csCreatedByFK INT NOT NULL,
    csUpdatedDate DATETIME NULL,
    csAdminFK INT NOT NULL,
    csUptimeDate DATETIME NULL,
    csCheckDate DATETIME NULL,
    csName VARCHAR(32) NOT NULL,
    csStatus CHAR(1) NOT NULL,
    csDockerID VARCHAR(64) NULL,
    csSubdomain VARCHAR(64) NULL,
    csFailureCount INT NOT NULL DEFAULT 0,
    PRIMARY KEY(csPK)
);
GO
