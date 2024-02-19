create trigger autoMap BEFORE INSERT on monitoringDevicePoller 
FOR EACH ROW
  INSERT INTO trapEventMap VALUES( new.checkName, new.CheckName, 1, '', 1, '', '', 86400, '') ON DUPLICATE KEY UPDATE oid=new.checkName ;
