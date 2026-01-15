-- Passwords - Stack: Stack, Stack_Two: Stack_Two
INSERT INTO stackDB.appUsers (name, age, city, username, password, email) 
VALUES ('Stack', 1, 'Stack', 'Stack', '$2y$10$m3vCndDU2/CavRvxwB2Gne5lnusLha8NJpgrARhwbzJN.uqqIePUq', 'Stack');

INSERT INTO stackDB.appUsers (name, age, city, username, password, email) 
VALUES ('Stack_Two', 2, 'Stack_Two', 'Stack_Two', '$2y$10$fkjGHwVncM0YQ9Jg0gHvku.E7TUTMvXeUbIdmUOwyoDaBcFUm432i', 'Stack_Two');

INSERT INTO stackDB.sysConfig (scName, scValue) VALUES ('myDomain', 'localhost');
INSERT INTO stackDB.sysConfig (scName, scValue) VALUES ('myDomainProtocol', 'http');


#--$Local_array = ['user' => ['name' => 'John', 'roles' => ['admin', 'editor'], 'pages' => [[1 => 'home', 2 => 'contact us']]], 


INSERT INTO tblSession (sessChars, sessTransactionActive) 
VALUES ('64 byte key', 0);

INSERT INTO tblSessionAtt (sattSessionFK, sattPK, sattDisc, sattKey, sattPrimaryValueFK) 
VALUES (1, 1, 'r', 'Local array', 1);

INSERT INTO tblSessionAttValue (sattvAttFK, sattvPK, sattvValueFK) 
VALUES (1, 1, 1);

INSERT INTO tblSessionAtt (sattSessionFK, sattPK, sattDisc, sattKey, sattPrimaryValueFK) 
VALUES (1, 2, 'l', 'user', 2);

INSERT INTO tblSessionAttValue (sattvAttFK, sattvPK, sattvValue) 
VALUES (2, 2, 'John');

INSERT INTO tblSessionAtt (sattSessionFK, sattPK, sattDisc, sattKey, sattPrimaryValueFK) 
VALUES (1, 3, 'l', 'roles', 2);

INSERT INTO tblSessionAttValue (sattvAttFK, sattvPK, sattvValue) 
VALUES (3, 3, 'admin');

INSERT INTO tblSessionAttValue (sattvAttFK, sattvPK, sattvValue) 
VALUES (3, 4, 'editor');

INSERT INTO tblSessionAtt (sattSessionFK, sattPK, sattDisc, sattKey, sattPrimaryValueFK) 
VALUES (1, 4, 'b', 'pages', 4);

INSERT INTO tblSessionAttValue (sattvAttFK, sattvPK, sattvValueFK) 
VALUES (4, 5, 4);

INSERT INTO tblSessionAttValue (sattvAttFK, sattvPK, sattvValueFK) 
VALUES (4, 6, 5);

INSERT INTO tblSessionAtt (sattSessionFK, sattPK, sattDisc, sattKey, sattPrimaryValueFK) 
VALUES (1, 5, 'l', '1', 4);

INSERT INTO tblSessionAttValue (sattvAttFK, sattvPK, sattvValue) 
VALUES (5, 7, 'home');

INSERT INTO tblSessionAtt (sattSessionFK, sattPK, sattDisc, sattKey, sattPrimaryValueFK) 
VALUES (1, 6, 'l', '2', 4);

INSERT INTO tblSessionAttValue (sattvAttFK, sattvPK, sattvValue) 
VALUES (6, 8, 'contact us');