UPDATE tblColumns SET tcRules = '{"required":true,"min":3,"unique":["appUsers","username"]}' WHERE tcPK = 3;
