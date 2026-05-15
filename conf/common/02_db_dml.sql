use stackDB;

-- Passwords - Stack: Stack, Stack_Two: Stack_Two
INSERT INTO appUsers (name, age, city, username, password, email, verified) 
VALUES ('Stack', 1, 'Stack', 'Stack', '$2y$10$m3vCndDU2/CavRvxwB2Gne5lnusLha8NJpgrARhwbzJN.uqqIePUq', 'Stack@stack.com', true);

INSERT INTO appUsers (name, age, city, username, password, email, verified) 
VALUES ('Stack_Two', 2, 'Stack_Two', 'Stack_Two', '$2y$10$fkjGHwVncM0YQ9Jg0gHvku.E7TUTMvXeUbIdmUOwyoDaBcFUm432i', 'Stack_Two@stack.com', true);

-- Root Items
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder, nbParentFK) VALUES ('Home', 'c', '/', 'IndexController', false, 1, NULL);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder, nbParentFK) VALUES ('Dashboard', 'p', '/dashboard', 'DashboardController', true, 2, NULL);

-- Settings Menu
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder, nbParentFK) VALUES ('Settings', 'p', NULL, NULL, true, 3, NULL);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder, nbParentFK) VALUES ('Accounts', 'p', '/account_management', 'AccountManagementController', true, 1, 3);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder, nbParentFK) VALUES ('Error Log', 'p', '/error_log', 'ErrorLogController', true, 2, 3);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder, nbParentFK) VALUES ('Banned IPs', 'p', '/banned_ips', 'BannedIpManagementController', true, 3, 3);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder, nbParentFK) VALUES ('Platform Recovery', 'p', '/platform_recovery', 'PlatformRecoveryController', true, 5, 3);

-- CMS Menu
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder, nbParentFK) VALUES ('CMS', 'p', NULL, NULL, true, 4, NULL);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder, nbParentFK) VALUES ('Navbars', 'p', '/navbar_management', 'NavbarManagementController', true, 1, 8);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder, nbParentFK) VALUES ('Forms', 'p', '/form_management', 'FormManagementController', true, 2, 8);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder, nbParentFK) VALUES ('Pages', 'p', '/page_management', 'PageManagementController', true, 3, 8);

-- Logout
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder, nbParentFK) VALUES ('Logout', 'c', '/logout', 'LogoutAction', true, 5, NULL);

-- Actions (Internal Routes) 
-- -- nbDiscriminator = [a = {action, dont show on nav_bar but its a route}, h = {hidden}, p = {page, show on navbar}, c = {core}]
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
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Unban IP Action', 'a', '/unban_ip', 'UnbanIpAction', true, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Undo Redo Action', 'h', '/undo', 'UndoRedoController', true, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Page Action', 'a', '/pageAction', 'PageAction', true, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Element Action', 'a', '/elementAction', 'ElementAction', true, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Page Editor', 'a', '/page_editor', 'PageController', true, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Form JSON Action', 'a', '/formAction', 'FormAction', true, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Preview Page', 'a', '/preview', 'PreviewController', true, 0);
INSERT INTO tblNavBar (nbText, nbDiscriminator, nbPath, nbControllerClass, nbProtected, nbOrder) VALUES ('Data Provider JSON Action', 'a', '/dataProviders', 'DataProviderAction', true, 0);

INSERT INTO tblForm (tfName, tfReadOnly) VALUES ('login', false), ('register', false), ('editUser', false), ('navbar', false), ('editForm', true), ('editColumn', true), ('banned_ips', true), ('platform_recovery', false), ('page', false), ('element', false);

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
(4, 'nbDiscriminator', 'Discriminator (c/p)', 'text', '{"required":true,"min":1,"max":1}', 3),
(4, 'nbPath', 'Route/Path', 'text', '{"required":true}', 4),
(4, 'nbOrder', 'Display Order', 'number', '{"required":true,"numeric":true}', 5),
(4, 'nbParentFK', 'Parent Item ID (Optional)', 'number', '{"numeric":true}', 6),
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
(9, 'pagSlug', 'Slug', 'slug', '{"required":true,"alpha_dash":true}', 30),
(10, 'elePK', 'ID', 'hidden', '{}', 10),
(10, 'eleType', 'Type', 'text', '{"required":true}', 20),
(10, 'eleContent', 'Content', 'textarea', '{}', 30),
(10, 'eleCSSClasses', 'CSS Classes', 'css_class', '{"regex":"/^[a-zA-Z0-9\\\\-_ ]*$/"}', 40),
(10, 'eleParentFK', 'Parent ID', 'number', '{}', 50);


#--$Local_array = ['user' => ['name' => 'John', 'roles' => ['admin', 'editor'], 'pages' => [[1 => 'home', 2 => 'contact us']]], 


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

-- Sample Analytics Data
INSERT INTO tblAnalytics (anaLabel, anaValue, anaCategory) VALUES ('Jan', 100, 'Sales');
INSERT INTO tblAnalytics (anaLabel, anaValue, anaCategory) VALUES ('Feb', 150, 'Sales');
INSERT INTO tblAnalytics (anaLabel, anaValue, anaCategory) VALUES ('Mar', 200, 'Sales');
INSERT INTO tblAnalytics (anaLabel, anaValue, anaCategory) VALUES ('Apr', 180, 'Sales');
INSERT INTO tblAnalytics (anaLabel, anaValue, anaCategory) VALUES ('May', 250, 'Sales');
INSERT INTO tblAnalytics (anaLabel, anaValue, anaCategory) VALUES ('Jun', 300, 'Sales');