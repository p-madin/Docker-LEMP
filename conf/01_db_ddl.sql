CREATE DATABASE IF NOT EXISTS stackDB;
use stackDB;

CREATE TABLE appUsers(
    auPK INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(32) NOT NULL,
    age INT NOT NULL,
    city VARCHAR(32) NOT NULL,
    username VARCHAR(16) NOT NULL,
    password VARCHAR(256) NOT NULL,
    email VARCHAR(32) NOT NULL,
    dateAdded DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    dateVerified DATETIME,
    verified TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY(auPK)
);

CREATE TABLE tblSession(
    sessPK INT NOT NULL AUTO_INCREMENT,
    sessCreated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sessUpdated DATETIME NULL,
    sessDeleted DATETIME NULL,
    sessChars VARCHAR(64) NOT NULL,
    sessUser INT NULL,
    sessTransactionActive INT NOT NULL,
    PRIMARY KEY(sessPK)
);

CREATE TABLE tblSessionAtt(
    sattPK INT NOT NULL AUTO_INCREMENT,
    sattSessionFK INT NOT NULL,
    sattDisc CHAR(1) NOT NULL,
    sattKey VARCHAR(32) NOT NULL,
    sattPrimaryValueFK INT NOT NULL,
    PRIMARY KEY(sattPK)
);

CREATE TABLE tblSessionAttValue(
    sattvPK INT NOT NULL AUTO_INCREMENT,
    sattvAttFK INT NOT NULL,
    sattvValueFK INT NULL,
    sattvValue VARCHAR(128) NULL,
    PRIMARY KEY(sattvPK)
);

CREATE TABLE sysConfig(
    scPK INT NOT NULL AUTO_INCREMENT,
    scName VARCHAR(32) NOT NULL,
    scValue VARCHAR(128) NOT NULL,
    PRIMARY KEY(scPK)
);

CREATE TABLE tblNavBar(
    nbPK INT NOT NULL AUTO_INCREMENT,
    nbText VARCHAR(32) NOT NULL,
    nbDiscriminator CHAR(1) NOT NULL,
    nbPath VARCHAR(64) NULL,
    nbControllerClass VARCHAR(64) NULL,
    nbProtected TINYINT(1) NOT NULL,
    nbOrder INT NOT NULL,
    nbParentFK INT NULL DEFAULT NULL,
    PRIMARY KEY(nbPK),
    FOREIGN KEY(nbParentFK) REFERENCES tblNavBar(nbPK) ON DELETE SET NULL
);

CREATE UNIQUE INDEX idx_session_chars ON tblSession(sessChars);
CREATE INDEX idx_session_att_lookup ON tblSessionAtt(sattSessionFK, sattKey, sattDisc);
CREATE INDEX idx_session_val_lookup ON tblSessionAttValue(sattvAttFK);

CREATE TABLE httpAction(
    haPK INT NOT NULL AUTO_INCREMENT,
    haDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    haSessionFK INT NOT NULL,
    haUserFK INT NULL,
    haIP VARCHAR(45) NOT NULL,
    haURL VARCHAR(512) NOT NULL,
    haReferrer VARCHAR(512) NULL,
    haMethod VARCHAR(8) NOT NULL,
    haUserAgent VARCHAR(512) NOT NULL,
    haHeaders TEXT NULL,
    haWafRuleTriggered VARCHAR(255) NULL,
    haWafPayload TEXT NULL,
    PRIMARY KEY(haPK)
);

CREATE TABLE phpErrorLog (
  pelPK INT NOT NULL AUTO_INCREMENT,
  pelTimestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  pelSeverity VARCHAR(32) NOT NULL,
  pelMessage TEXT NOT NULL,
  pelFile VARCHAR(512) NOT NULL,
  pelLine INT NOT NULL,
  PRIMARY KEY(pelPK)
);

CREATE TABLE tblForm(
    tfPK INT NOT NULL AUTO_INCREMENT,
    tfName VARCHAR(32) NOT NULL,
    tfReadOnly TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY(tfPK)
);

CREATE TABLE tblColumns(
    tcPK INT NOT NULL AUTO_INCREMENT,
    tcFormFK INT NOT NULL,
    tcName VARCHAR(32) NOT NULL,
    tcLabel VARCHAR(64) NOT NULL,
    tcType VARCHAR(32) NOT NULL,
    tcRules TEXT NOT NULL,
    tcOrder INT NOT NULL,
    PRIMARY KEY(tcPK),
    FOREIGN KEY(tcFormFK) REFERENCES tblForm(tfPK) ON DELETE CASCADE
);

CREATE TABLE banned_ips(
    biPK INT NOT NULL AUTO_INCREMENT,
    biIP VARCHAR(45) NOT NULL,
    biReason VARCHAR(255) NOT NULL,
    biExpires DATETIME NOT NULL,
    biDateAdded DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(biPK)
);

CREATE TABLE event_store(
    id INT NOT NULL AUTO_INCREMENT,
    aggregate_id INT NULL,
    event_type VARCHAR(128) NOT NULL,
    payload TEXT NOT NULL,
    previous_payload TEXT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    user_id INT NULL,
    is_reversal TINYINT(1) NOT NULL DEFAULT 0,
    predecessor_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(id)
);