USE stackDB;
GO

-- Passwords - Stack: Stack, Stack_Two: Stack_Two
INSERT INTO appUsers (name, age, city, username, password, email, verified) 
VALUES ('Stack', 1, 'Stack', 'Stack', '$2y$10$m3vCndDU2/CavRvxwB2Gne5lnusLha8NJpgrARhwbzJN.uqqIePUq', 'Stack@stack.com', 1);

INSERT INTO appUsers (name, age, city, username, password, email, verified) 
VALUES ('Stack_Two', 2, 'Stack_Two', 'Stack_Two', '$2y$10$fkjGHwVncM0YQ9Jg0gHvku.E7TUTMvXeUbIdmUOwyoDaBcFUm432i', 'Stack_Two@stack.com', 1);

-- Create Management Pages
SET IDENTITY_INSERT tblPages ON;
INSERT INTO tblPages (pagPK, pagAuthorFK, pagTitle) VALUES (1, 1, 'Account Management');
INSERT INTO tblPages (pagPK, pagAuthorFK, pagTitle) VALUES (2, 1, 'Error Log');
INSERT INTO tblPages (pagPK, pagAuthorFK, pagTitle) VALUES (3, 1, 'Navbar Management');
INSERT INTO tblPages (pagPK, pagAuthorFK, pagTitle) VALUES (4, 1, 'Form Management');
INSERT INTO tblPages (pagPK, pagAuthorFK, pagTitle) VALUES (5, 1, 'Page Management');
INSERT INTO tblPages (pagPK, pagAuthorFK, pagTitle) VALUES (6, 1, 'Banned IP Management');
INSERT INTO tblPages (pagPK, pagAuthorFK, pagTitle) VALUES (7, 1, 'Platform Recovery');
INSERT INTO tblPages (pagPK, pagAuthorFK, pagTitle) VALUES (8, 1, 'Child Services');
SET IDENTITY_INSERT tblPages OFF;

-- Create Table Elements for these pages
SET IDENTITY_INSERT tblElements ON;
INSERT INTO tblElements (elePK, eleType, eleContent) VALUES (1, 'heading', 'Account Management');
INSERT INTO tblElements (elePK, eleType, eleContent) VALUES (2, 'table', '{"dataProvider":"AccountDataProvider"}');
INSERT INTO tblElements (elePK, eleType, eleContent) VALUES (3, 'heading', 'Error Log');
INSERT INTO tblElements (elePK, eleType, eleContent) VALUES (4, 'table', '{"dataProvider":"ErrorLogDataProvider"}');
INSERT INTO tblElements (elePK, eleType, eleContent) VALUES (5, 'heading', 'Navbar Management');
INSERT INTO tblElements (elePK, eleType, eleContent) VALUES (6, 'table', '{"dataProvider":"NavbarDataProvider"}');
INSERT INTO tblElements (elePK, eleType, eleContent) VALUES (7, 'heading', 'Form Management');
INSERT INTO tblElements (elePK, eleType, eleContent) VALUES (8, 'table', '{"dataProvider":"FormDataProvider"}');
INSERT INTO tblElements (elePK, eleType, eleContent) VALUES (9, 'heading', 'Page Management');
INSERT INTO tblElements (elePK, eleType, eleContent) VALUES (10, 'table', '{"dataProvider":"PageDataProvider"}');
INSERT INTO tblElements (elePK, eleType, eleContent) VALUES (11, 'heading', 'Banned IPs');
INSERT INTO tblElements (elePK, eleType, eleContent) VALUES (12, 'table', '{"dataProvider":"BannedIpDataProvider"}');
INSERT INTO tblElements (elePK, eleType, eleContent) VALUES (13, 'heading', 'Platform Recovery');
INSERT INTO tblElements (elePK, eleType, eleContent) VALUES (14, 'table', '{"dataProvider":"PlatformRecoveryDataProvider"}');
INSERT INTO tblElements (elePK, eleType, eleContent) VALUES (15, 'heading', 'Child Services');
INSERT INTO tblElements (elePK, eleType, eleContent) VALUES (16, 'table', '{"dataProvider":"ChildServiceDataProvider"}');
INSERT INTO tblElements (elePK, eleType, eleContent) VALUES (21, 'hyperlink', '{"label":"Create New Navbar Item", "url":"/edit_navbar"}');
INSERT INTO tblElements (elePK, eleType, eleContent) VALUES (22, 'hyperlink', '{"label":"Create New Form", "url":"/edit_form"}');
INSERT INTO tblElements (elePK, eleType, eleContent) VALUES (23, 'hyperlink', '{"label":"Create New Page", "url":"/page_editor"}');
INSERT INTO tblElements (elePK, eleType, eleContent) VALUES (17, 'form', '11');
INSERT INTO tblElements (elePK, eleType, eleContent) VALUES (50, 'heading', 'Archived Pages');
INSERT INTO tblElements (elePK, eleType, eleContent) VALUES (51, 'table', '{"dataProvider":"ArchivedPageDataProvider"}');
SET IDENTITY_INSERT tblElements OFF;

-- Link Elements to Pages
INSERT INTO brgPageElements (pelPageFK, pelElementFK, pelOrder) VALUES (1, 1, 1);
INSERT INTO brgPageElements (pelPageFK, pelElementFK, pelOrder) VALUES (1, 2, 2);
INSERT INTO brgPageElements (pelPageFK, pelElementFK, pelOrder) VALUES (2, 3, 1);
INSERT INTO brgPageElements (pelPageFK, pelElementFK, pelOrder) VALUES (2, 4, 2);
INSERT INTO brgPageElements (pelPageFK, pelElementFK, pelOrder) VALUES (3, 5, 1);
INSERT INTO brgPageElements (pelPageFK, pelElementFK, pelOrder) VALUES (3, 6, 2);
INSERT INTO brgPageElements (pelPageFK, pelElementFK, pelOrder) VALUES (3, 21, 3);
INSERT INTO brgPageElements (pelPageFK, pelElementFK, pelOrder) VALUES (4, 7, 1);
INSERT INTO brgPageElements (pelPageFK, pelElementFK, pelOrder) VALUES (4, 8, 2);
INSERT INTO brgPageElements (pelPageFK, pelElementFK, pelOrder) VALUES (4, 22, 3);
INSERT INTO brgPageElements (pelPageFK, pelElementFK, pelOrder) VALUES (5, 9, 1);
INSERT INTO brgPageElements (pelPageFK, pelElementFK, pelOrder) VALUES (5, 10, 2);
INSERT INTO brgPageElements (pelPageFK, pelElementFK, pelOrder) VALUES (5, 23, 3);
INSERT INTO brgPageElements (pelPageFK, pelElementFK, pelOrder) VALUES (6, 11, 1);
INSERT INTO brgPageElements (pelPageFK, pelElementFK, pelOrder) VALUES (6, 12, 2);
INSERT INTO brgPageElements (pelPageFK, pelElementFK, pelOrder) VALUES (7, 13, 1);
INSERT INTO brgPageElements (pelPageFK, pelElementFK, pelOrder) VALUES (7, 14, 2);
INSERT INTO brgPageElements (pelPageFK, pelElementFK, pelOrder) VALUES (8, 15, 1);
INSERT INTO brgPageElements (pelPageFK, pelElementFK, pelOrder) VALUES (8, 16, 2);
INSERT INTO brgPageElements (pelPageFK, pelElementFK, pelOrder) VALUES (8, 17, 2);
INSERT INTO brgPageElements (pelPageFK, pelElementFK, pelOrder) VALUES (5, 50, 4);
INSERT INTO brgPageElements (pelPageFK, pelElementFK, pelOrder) VALUES (5, 51, 5);

-- Root Items
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder, nbParentFK) VALUES ('Home', 'c', NULL, '/', 'IndexController', 0, 1, NULL);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder, nbParentFK) VALUES ('Dashboard', 'p', NULL, '/dashboard', 'DashboardController', 1, 2, NULL);

-- Settings Menu
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder, nbParentFK) VALUES ('Settings', 'p', NULL, NULL, NULL, 1, 3, NULL);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder, nbParentFK) VALUES ('Accounts', 'p', 1, '/account_management', NULL, 1, 1, 3);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder, nbParentFK) VALUES ('Error Log', 'c', NULL, '/error_log', 'ErrorLogController', 1, 2, 3);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder, nbParentFK) VALUES ('Banned IPs', 'p', 6, '/banned_ips', NULL, 1, 3, 3);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder, nbParentFK) VALUES ('Platform Recovery', 'c', NULL, '/platform_recovery', 'PlatformRecoveryController', 1, 5, 3);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder, nbParentFK) VALUES ('Child Services', 'p', 8, '/child_management', NULL, 1, 6, 3);

-- CMS Menu
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder, nbParentFK) VALUES ('CMS', 'p', NULL, NULL, NULL, 1, 4, NULL);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder, nbParentFK) VALUES ('Navbars', 'p', 3, '/navbar_management', NULL, 1, 1, 9);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder, nbParentFK) VALUES ('Forms', 'p', 4, '/form_management', NULL, 1, 2, 9);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder, nbParentFK) VALUES ('Pages', 'p', 5, '/page_management', NULL, 1, 3, 9);

-- Logout
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder, nbParentFK) VALUES ('Logout', 'c', NULL, '/logout', 'LogoutAction', 1, 5, NULL);

-- Actions (Internal Routes) 
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Login Action', 'a', NULL, '/login', 'LoginAction', 0, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Register Action', 'a', NULL, '/register', 'RegisterAction', 0, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Update Account', 'a', NULL, '/editAccount', 'UpdateAccountAction', 1, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Edit Column', 'a', NULL, '/edit_column', 'EditColumnController', 1, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Edit Form', 'a', NULL, '/edit_form', 'EditFormController', 1, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Edit Navbar', 'a', NULL, '/edit_navbar', 'EditNavbarController', 1, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Edit Account', 'a', NULL, '/edit_account', 'EditAccountController', 1, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Edit Column Action', 'a', NULL, '/editColumn', 'EditColumnAction', 1, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Edit Form Action', 'a', NULL, '/editForm', 'EditFormAction', 1, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Edit Navbar Action', 'a', NULL, '/editNavbar', 'EditNavbarAction', 1, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Unban IP Action', 'a', NULL, '/unban_ip', 'UnbanIpAction', 1, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Undo Redo Action', 'h', NULL, '/undo', 'UndoRedoController', 1, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Page Action', 'a', NULL, '/pageAction', 'PageAction', 1, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Element Action', 'a', NULL, '/elementAction', 'ElementAction', 1, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Page Editor', 'a', NULL, '/page_editor', 'PageController', 1, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Form JSON Action', 'a', NULL, '/formAction', 'FormAction', 1, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Preview Page', 'a', NULL, '/preview', 'PreviewController', 1, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Data Provider JSON Action', 'a', NULL, '/dataProviders', 'DataProviderAction', 1, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Boot Child Action', 'a', NULL, '/bootChild', 'BootChildAction', 1, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Create Child Service Action', 'a', NULL, '/createChildService', 'CreateChildServiceAction', 1, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPageFK, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Child Service Action', 'a', NULL, '/childServiceAction', 'ChildServiceAction', 1, 0);

INSERT INTO tblForm (tfName, tfAction, tfReadOnly) 
VALUES ('login', 'login', 0), 
('register', 'register', 0), 
('editUser', 'editAccount', 0), 
('navbar', 'editNavbar', 0), 
('editForm', 'editForm', 1), 
('editColumn', 'editColumn', 1), 
('banned_ips', 'banned_ips', 1), 
('platform_recovery', 'platform_recovery', 0), 
('page', 'page', 0), 
('element', 'element', 0),
('createChildService', 'createChildService', 0);

INSERT INTO tblColumns (tcFormFK, tcName, tcLabel, tcType, tcRules, tcOrder) VALUES
(1, 'username', 'Username', 'text', '{"required":true}', 1),
(1, 'password', 'Password', 'password', '{"required":true}', 2),
(2, 'username', 'Username', 'text', '{"required":true,"min":3,"unique":"registration_username"}', 1),
(2, 'password', 'Password', 'password', '{"required":true,"min":6}', 2),
(2, 'confirm_password', 'Confirm Password', 'password', '{"required":true,"match":"password"}', 3),
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
(4, 'nbDiscriminator', 'Discriminator (c/p/h)', 'text', '{"required":true,"min":1,"max":1}', 3),
(4, 'nbPath', 'Route/Path', 'text', '{"required":true}', 4),
(4, 'nbPageFK', 'Target Page ID (Optional)', 'number', '{"numeric":true}', 5),
(4, 'nbOrder', 'Display Order', 'number', '{"required":true,"numeric":true}', 6),
(4, 'nbParentFK', 'Parent Item ID (Optional)', 'number', '{"numeric":true}', 7),
(5, 'tfPK', '', 'hidden', '{}', 1),
(5, 'tfName', 'Form Name', 'text', '{"required":true,"min":1}', 2),
(6, 'tcPK', '', 'hidden', '{}', 1),
(6, 'tcFormFK', '', 'hidden', '{"required":true,"numeric":true}', 2),
(6, 'tcName', 'Field Name', 'text', '{"required":true,"min":1}', 3),
(6, 'tcLabel', 'Field Label', 'text', '{"required":true}', 4),
(6, 'tcType', 'Field Type (e.g. text/password)', 'text', '{"required":true}', 5),
(6, 'tcRules', 'Validation Rules JSON (e.g. {"required":true})', 'text', '{}', 6),
(6, 'tcOrder', 'Display Order', 'number', '{"required":true,"numeric":true}', 7),
(8, 'tcPK', '', 'hidden', '{}', 1),
(8, 'action', '', 'hidden', '{}', 2),
(8, 'target_time', 'Recover to Timestamp', 'datetime-local', '{"required":true}', 3),
(9, 'pagPK', 'ID', 'hidden', '{}', 10),
(9, 'pagTitle', 'Title', 'text', '{"required":true}', 20),
(10, 'elePK', 'ID', 'hidden', '{}', 10),
(10, 'eleType', 'Type', 'text', '{"required":true}', 20),
(10, 'eleContent', 'Content', 'textarea', '{}', 30),
(10, 'eleCSSClasses', 'CSS Classes', 'css_class', '{"regex":"/^[a-zA-Z0-9\\\\-_ ]*$/"}', 40),
(10, 'eleParentFK', 'Parent ID', 'number', '{}', 50),
(11, 'csName', 'Service Name', 'alpha_dash', '{"required":true,"alpha_dash":true}', 1),
(11, 'csAdminFK', 'Tenant Administrator', 'user_select', '{"required":true,"numeric":true}', 2);

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

INSERT INTO tblAnalytics (anaLabel, anaValue, anaCategory) VALUES ('Jan', 100, 'Sales');
INSERT INTO tblAnalytics (anaLabel, anaValue, anaCategory) VALUES ('Feb', 150, 'Sales');
INSERT INTO tblAnalytics (anaLabel, anaValue, anaCategory) VALUES ('Mar', 200, 'Sales');
INSERT INTO tblAnalytics (anaLabel, anaValue, anaCategory) VALUES ('Apr', 180, 'Sales');
INSERT INTO tblAnalytics (anaLabel, anaValue, anaCategory) VALUES ('May', 250, 'Sales');
INSERT INTO tblAnalytics (anaLabel, anaValue, anaCategory) VALUES ('Jun', 300, 'Sales');
GO
