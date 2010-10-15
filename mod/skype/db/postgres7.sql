CREATE TABLE prefix_skype (
  id SERIAL PRIMARY KEY,
  course integer NOT NULL default '0',
  name varchar(255) default NULL,
  participants varchar(10),
  timemodified integer NOT NULL default '0'
);


INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('skype', 'add', 'skype', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('skype', 'update', 'skype', 'name');
