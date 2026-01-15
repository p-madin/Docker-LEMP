CREATE DATABASE IF NOT EXISTS stackDB;

CREATE TABLE stackDB.appUsers(
    auPK INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(32) NOT NULL,
    age INT NOT NULL,
    city VARCHAR(32) NOT NULL,
    username VARCHAR(16) NOT NULL,
    password VARCHAR(256) NOT NULL,
    email VARCHAR(32) NOT NULL,
    dateAdded DATETIME NOT NULL DEFAULT NOW(),
    dateVerified DATETIME,
    PRIMARY KEY(auPK)
);

CREATE TABLE stackDB.tblSession(
    sessPK INT NOT NULL AUTO_INCREMENT,
    sessCreated DATETIME NOT NULL DEFAULT NOW(),
    sessUpdated DATETIME NULL,
    sessChars VARCHAR(64) NOT NULL,
    sessUser INT NULL,
    sessTransactionActive INT NOT NULL,
    PRIMARY KEY(sessPK)
);

CREATE TABLE stackDB.tblSessionAtt(
    sattPK INT NOT NULL AUTO_INCREMENT,
    sattSessionFK INT NOT NULL,
    sattDisc CHAR(1) NOT NULL,
    sattKey VARCHAR(32) NOT NULL,
    sattPrimaryValueFK INT NOT NULL,
    PRIMARY KEY(sattPK)
);

CREATE TABLE stackDB.tblSessionAttValue(
    sattvPK INT NOT NULL AUTO_INCREMENT,
    sattvAttFK INT NOT NULL,
    sattvValueFK INT NULL,
    sattvValue VARCHAR(128) NULL,
    PRIMARY KEY(sattvPK)
);

CREATE TABLE stackDB.sysConfig(
    scPK INT NOT NULL AUTO_INCREMENT,
    scName VARCHAR(32) NOT NULL,
    scValue VARCHAR(128) NOT NULL,
    PRIMARY KEY(scPK)
);

CREATE UNIQUE INDEX idx_session_chars ON stackDB.tblSession(sessChars);
CREATE INDEX idx_session_att_lookup ON stackDB.tblSessionAtt(sattSessionFK, sattKey, sattDisc);
CREATE INDEX idx_session_val_lookup ON stackDB.tblSessionAttValue(sattvAttFK);

CREATE TABLE stackDB.httpAction(
    haPK INT NOT NULL AUTO_INCREMENT,
    haDate DATETIME NOT NULL DEFAULT NOW(),
    haSessionFK INT NOT NULL,
    haUserFK INT NULL,
    haIP VARCHAR(45) NOT NULL,
    haURL VARCHAR(512) NOT NULL,
    haReferrer VARCHAR(512) NULL,
    haMethod VARCHAR(8) NOT NULL,
    haUserAgent VARCHAR(512) NOT NULL,
    haHeaders TEXT NULL,
    PRIMARY KEY(haPK)
);