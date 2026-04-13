-- Passwords - Stack: Stack, Stack_Two: Stack_Two
INSERT INTO appUsers (name, age, city, username, password, email, verified) 
VALUES ('Stack', 1, 'Stack', 'Stack', '$2y$10$m3vCndDU2/CavRvxwB2Gne5lnusLha8NJpgrARhwbzJN.uqqIePUq', 'Stack@stack.com', true);

INSERT INTO appUsers (name, age, city, username, password, email, verified) 
VALUES ('Stack_Two', 2, 'Stack_Two', 'Stack_Two', '$2y$10$fkjGHwVncM0YQ9Jg0gHvku.E7TUTMvXeUbIdmUOwyoDaBcFUm432i', 'Stack_Two@stack.com', true);

INSERT INTO sysConfig (scName, scValue) VALUES ('myDomain', 'localhost');
INSERT INTO sysConfig (scName, scValue) VALUES ('myDomainProtocol', 'http');

INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Home', 'c', '/', 'IndexController', false, 1);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Dashboard', 'p', '/dashboard', 'DashboardController', true, 2);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Accounts', 'p', '/account_management', 'AccountManagementController', true, 3);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Error Log', 'p', '/error_log', 'ErrorLogController', true, 4);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Navbars', 'p', '/navbar_management', 'NavbarManagementController', true, 5);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Forms', 'p', '/form_management', 'FormManagementController', true, 6);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Logout', 'c', '/logout', 'LogoutAction', true, 7);

-- Actions (Internal Routes)
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Login Action', 'a', '/login', 'LoginAction', false, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Register Action', 'a', '/register', 'RegisterAction', false, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Update Account', 'a', '/editAccount', 'UpdateAccountAction', true, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Edit Column', 'a', '/edit_column', 'EditColumnController', true, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Edit Form', 'a', '/edit_form', 'EditFormController', true, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Edit Navbar', 'a', '/edit_navbar', 'EditNavbarController', true, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Edit Account', 'a', '/edit_account', 'EditAccountController', true, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Edit Column Action', 'a', '/editColumn', 'EditColumnAction', true, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Edit Form Action', 'a', '/editForm', 'EditFormAction', true, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Edit Navbar Action', 'a', '/editNavbar', 'EditNavbarAction', true, 0);

INSERT INTO tblForm (tfName, tfReadOnly) VALUES ('login', false), ('register', false), ('editUser', false), ('navbar', false), ('editForm', true), ('editColumn', true);

INSERT INTO tblColumns (tcFormFK, tcName, tcLabel, tcType, tcRules, tcOrder) VALUES
(1, 'username', 'Username', 'text', '{"required":true}', 1),
(1, 'password', 'Password', 'password', '{"required":true}', 2),
(2, 'username', 'Username', 'text', '{"required":true,"min":3}', 1),
(2, 'password', 'Password', 'password', '{"required":true,"min":6}', 2),
(2, 'confirm_password', 'Confirm Password', 'password', '{"match":"password"}', 3),
(2, 'name', 'Name', 'text', '{"required":true}', 4),
(2, 'age', 'Age', 'number', '{"required":true,"numeric":true}', 5),
(2, 'city', 'City', 'text', '{}', 6),
(2, 'email', 'Email', 'email', '{"required":true,"email":true}', 7),
(3, 'auPK', '', 'hidden', '{}', 1),
(3, 'username', 'Username', 'text', '{"required":true,"min":3}', 2),
(3, 'name', 'Name', 'text', '{"required":true}', 3),
(3, 'age', 'Age', 'number', '{"required":true,"numeric":true}', 4),
(3, 'city', 'City', 'text', '{}', 5),
(3, 'email', 'Email', 'email', '{"required":true,"email":true}', 6),
(4, 'nbPK', '', 'hidden', '{}', 1),
(4, 'nbText', 'Display Text', 'text', '{"required":true,"min":1}', 2),
(4, 'nbDiscriminator', 'Discriminator (c/p)', 'text', '{"required":true,"min":1,"max":1}', 3),
(4, 'nbPath', 'Route/Path', 'text', '{"required":true}', 4),
(4, 'nbOrder', 'Display Order', 'number', '{"required":true,"numeric":true}', 5),
(5, 'tfPK', '', 'hidden', '{}', 1),
(5, 'tfName', 'Form Name', 'text', '{"required":true,"min":1}', 2),
(6, 'tcPK', '', 'hidden', '{}', 1),
(6, 'tcFormFK', '', 'hidden', '{"required":true,"numeric":true}', 2),
(6, 'tcName', 'Field Name', 'text', '{"required":true,"min":1}', 3),
(6, 'tcLabel', 'Field Label', 'text', '{"required":true}', 4),
(6, 'tcType', 'Field Type (e.g. text/password)', 'text', '{"required":true}', 5),
(6, 'tcRules', 'Validation Rules JSON (e.g. {"required":true})', 'text', '{}', 6),
(6, 'tcOrder', 'Display Order', 'number', '{"required":true,"numeric":true}', 7);


-- $Local_array = ['user' => ['name' => 'John', 'roles' => ['admin', 'editor'], 'pages' => [[1 => 'home', 2 => 'contact us']]], 


INSERT INTO tblSession (sessChars, sessTransactionActive) 
VALUES ('64 byte key', 0);

INSERT INTO tblSessionAtt (sattSessionFK, sattDisc, sattKey, sattPrimaryValueFK) 
VALUES (1, 'r', 'Local array', 1);

INSERT INTO tblSessionAttValue (sattvAttFK, sattvValueFK) 
VALUES (1, 1);

INSERT INTO tblSessionAtt (sattSessionFK, sattDisc, sattKey, sattPrimaryValueFK) 
VALUES (1, 'l', 'user', 2);

INSERT INTO tblSessionAttValue (sattvAttFK, sattvValue) 
VALUES (2, 'John');

INSERT INTO tblSessionAtt (sattSessionFK, sattDisc, sattKey, sattPrimaryValueFK) 
VALUES (1, 'l', 'roles', 2);

INSERT INTO tblSessionAttValue (sattvAttFK, sattvValue) 
VALUES (3, 'admin');

INSERT INTO tblSessionAttValue (sattvAttFK, sattvValue) 
VALUES (3, 'editor');

INSERT INTO tblSessionAtt (sattSessionFK, sattDisc, sattKey, sattPrimaryValueFK) 
VALUES (1, 'b', 'pages', 4);

INSERT INTO tblSessionAttValue (sattvAttFK, sattvValueFK) 
VALUES (4, 4);

INSERT INTO tblSessionAttValue (sattvAttFK, sattvValueFK) 
VALUES (4, 5);

INSERT INTO tblSessionAtt (sattSessionFK, sattDisc, sattKey, sattPrimaryValueFK) 
VALUES (1, 'l', '1', 4);

INSERT INTO tblSessionAttValue (sattvAttFK, sattvValue) 
VALUES (5, 'home');

INSERT INTO tblSessionAtt (sattSessionFK, sattDisc, sattKey, sattPrimaryValueFK) 
VALUES (1, 'l', '2', 4);

INSERT INTO tblSessionAttValue (sattvAttFK, sattvValue) 
VALUES (6, 'contact us');