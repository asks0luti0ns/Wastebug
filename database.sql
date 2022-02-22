
--
-- This dump should be able to create empty
-- database schema for Wastebug to use.
--
-- Originally created from development database
-- with phpPgAdmin, since then manually edited.
--

--
-- PostgreSQL database dump
--

CREATE SCHEMA wastebug;

REVOKE ALL ON SCHEMA wastebug FROM PUBLIC;
GRANT USAGE ON SCHEMA wastebug TO apache;

SET search_path = wastebug, pg_catalog;

-- Users

CREATE TABLE wb_users (
    id serial NOT NULL,
    name character varying(20) NOT NULL,
    "password" character(32) NOT NULL,
    fullname character varying(100) NOT NULL,
    email character varying(100) NOT NULL,
    super boolean DEFAULT false NOT NULL,
    enabled boolean DEFAULT true NOT NULL
);

REVOKE ALL ON TABLE wb_users FROM PUBLIC;
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE wb_users TO apache;

REVOKE ALL ON TABLE wb_users_id_seq FROM PUBLIC;
GRANT SELECT,UPDATE ON TABLE wb_users_id_seq TO apache;

-- Projects

CREATE TABLE wb_projects (
    id serial NOT NULL,
    name character varying(100) NOT NULL,
    "owner" integer NOT NULL
);

REVOKE ALL ON TABLE wb_projects FROM PUBLIC;
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE wb_projects TO apache;

REVOKE ALL ON TABLE wb_projects_id_seq FROM PUBLIC;
GRANT SELECT,UPDATE ON TABLE wb_projects_id_seq TO apache;

-- Status

CREATE TABLE wb_status (
    id serial NOT NULL,
    open boolean NOT NULL,
    name character varying(30) NOT NULL
);

REVOKE ALL ON TABLE wb_status FROM PUBLIC;
GRANT SELECT ON TABLE wb_status TO apache;

-- Type

CREATE TABLE wb_type (
    id serial NOT NULL,
    name character varying(20) NOT NULL,
    icon character varying(100) NOT NULL
);

REVOKE ALL ON TABLE wb_type FROM PUBLIC;
GRANT SELECT ON TABLE wb_type TO apache;

-- Priority

CREATE TABLE wb_priority (
    id serial NOT NULL,
    name character varying(20) NOT NULL
);

REVOKE ALL ON TABLE wb_priority FROM PUBLIC;
GRANT SELECT ON TABLE wb_priority TO apache;

-- Bugs

CREATE TABLE wb_bugs (
    id serial NOT NULL,
    name character varying(100) NOT NULL,
    project integer NOT NULL,
    assigned integer NOT NULL,
    "type" integer NOT NULL,
    priority integer NOT NULL,
    "version" character varying(100) NOT NULL,
    computer character varying(100) NOT NULL,
    status integer DEFAULT 1 NOT NULL,
    opened timestamp without time zone DEFAULT '::now' NOT NULL
);

REVOKE ALL ON TABLE wb_bugs FROM PUBLIC;
GRANT INSERT,SELECT,UPDATE ON TABLE wb_bugs TO apache;

REVOKE ALL ON TABLE wb_bugs_id_seq FROM PUBLIC;
GRANT SELECT,UPDATE ON TABLE wb_bugs_id_seq TO apache;

-- News

CREATE TABLE wb_news (
     userid integer NOT NULL,
     posted timestamp without time zone DEFAULT '::now' NOT NULL,
     title character varying(100) NOT NULL,
     content text NOT NULL
);

REVOKE ALL ON TABLE wb_news FROM PUBLIC;
GRANT INSERT,SELECT ON TABLE wb_news TO apache;

-- Bug subscriptions for users

CREATE TABLE wb_subscriptions (
     bugid integer NOT NULL,
     userid integer NOT NULL
);

REVOKE ALL ON TABLE wb_subscriptions FROM PUBLIC;
GRANT INSERT,SELECT,DELETE ON TABLE wb_subscriptions TO apache;

-- Some views we use

CREATE VIEW wb_project_stats AS
    SELECT p.id, p.name, u.fullname AS "owner", (SELECT count(*) AS count FROM wb_bugs b, wb_status s WHERE (((b.status = s.id) AND (s.open = true)) AND (p.id = b.project))) AS open, (SELECT count(*) AS count FROM wb_bugs b, wb_status s WHERE (((b.status = s.id) AND (s.open = false)) AND (p.id = b.project))) AS closed FROM wb_projects p, wb_users u WHERE (p."owner" = u.id) ORDER BY p.name;

REVOKE ALL ON TABLE wb_project_stats FROM PUBLIC;
GRANT SELECT ON TABLE wb_project_stats TO apache;

CREATE VIEW wb_bug_list AS
    SELECT b.id, b.name, p.name AS project, p.id AS projectid, u.fullname AS "owner", u.id AS ownerid, t.name AS "type", t.icon, pr.name AS priority, s.name AS status, s.open, s.id AS statusid, b.opened FROM wb_bugs b, wb_users u, wb_projects p, wb_priority pr, wb_status s, wb_type t WHERE (((((b.assigned = u.id) AND (b."type" = t.id)) AND (b.priority = pr.id)) AND (b.project = p.id)) AND (b.status = s.id));

REVOKE ALL ON TABLE wb_bug_list FROM PUBLIC;
GRANT SELECT ON TABLE wb_bug_list TO apache;

-- Log

CREATE TABLE wb_log (
    id serial NOT NULL,
    bug integer NOT NULL,
    person integer NOT NULL,
    date timestamp without time zone DEFAULT '::now' NOT NULL,
    data text NOT NULL,
    "action" character varying(100)
);

REVOKE ALL ON TABLE wb_log FROM PUBLIC;
GRANT INSERT,SELECT ON TABLE wb_log TO apache;

REVOKE ALL ON TABLE wb_log_id_seq FROM PUBLIC;
GRANT SELECT,UPDATE ON TABLE wb_log_id_seq TO apache;

-- Fill in data.

INSERT INTO wb_status VALUES (1, true, 'New');
INSERT INTO wb_status VALUES (2, true, 'Open');
INSERT INTO wb_status VALUES (3, true, 'Work in process');
INSERT INTO wb_status VALUES (4, false, 'Fixed');
INSERT INTO wb_status VALUES (5, false, 'Cancelled');
INSERT INTO wb_status VALUES (6, false, 'Invalid');
INSERT INTO wb_status VALUES (7, false, 'Can''t reproduce');
INSERT INTO wb_status VALUES (8, false, 'Won''t fix');
INSERT INTO wb_status VALUES (9, false, 'By design');

INSERT INTO wb_type VALUES (1, 'Bug', 'pics/bug.png');
INSERT INTO wb_type VALUES (2, 'Feature request', 'pics/frequest.png');
INSERT INTO wb_type VALUES (3, 'Question', 'pics/question.png');

INSERT INTO wb_priority VALUES (1, '1 - Critical');
INSERT INTO wb_priority VALUES (2, '2 - High');
INSERT INTO wb_priority VALUES (3, '3 - Normal');
INSERT INTO wb_priority VALUES (4, '4 - Low');
INSERT INTO wb_priority VALUES (5, '5 - Harmless');

-- Constraints

ALTER TABLE ONLY wb_users
    ADD CONSTRAINT wb_users_pkey PRIMARY KEY (id);

ALTER TABLE ONLY wb_users
    ADD CONSTRAINT wb_users_name_key UNIQUE (name);

ALTER TABLE ONLY wb_projects
    ADD CONSTRAINT wb_projects_pkey PRIMARY KEY (id);

ALTER TABLE ONLY wb_projects
    ADD CONSTRAINT wb_projects_name_key UNIQUE (name);

ALTER TABLE ONLY wb_projects
    ADD CONSTRAINT "$1" FOREIGN KEY ("owner") REFERENCES wb_users(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY wb_type
    ADD CONSTRAINT wb_type_pkey PRIMARY KEY (id);

ALTER TABLE ONLY wb_status
    ADD CONSTRAINT wb_status_pkey PRIMARY KEY (id);

ALTER TABLE ONLY wb_bugs
    ADD CONSTRAINT wb_bugs_pkey PRIMARY KEY (id);

ALTER TABLE ONLY wb_bugs
    ADD CONSTRAINT "$1" FOREIGN KEY (project) REFERENCES wb_projects(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY wb_bugs
    ADD CONSTRAINT "$2" FOREIGN KEY (assigned) REFERENCES wb_users(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY wb_bugs
    ADD CONSTRAINT "$3" FOREIGN KEY ("type") REFERENCES wb_type(id) ON UPDATE NO ACTION ON DELETE NO ACTION;

ALTER TABLE ONLY wb_priority
    ADD CONSTRAINT wb_priority_pkey PRIMARY KEY (id);

ALTER TABLE ONLY wb_bugs
    ADD CONSTRAINT "$4" FOREIGN KEY (priority) REFERENCES wb_priority(id) ON UPDATE NO ACTION ON DELETE NO ACTION;

ALTER TABLE ONLY wb_bugs
    ADD CONSTRAINT "$5" FOREIGN KEY (status) REFERENCES wb_status(id) ON UPDATE NO ACTION ON DELETE NO ACTION;

ALTER TABLE ONLY wb_log
    ADD CONSTRAINT wb_log_pkey PRIMARY KEY (id);

ALTER TABLE ONLY wb_log
    ADD CONSTRAINT "$1" FOREIGN KEY (bug) REFERENCES wb_bugs(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY wb_log
    ADD CONSTRAINT "$2" FOREIGN KEY (person) REFERENCES wb_users(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY wb_news
    ADD CONSTRAINT "$1" FOREIGN KEY (userid) REFERENCES wb_users(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY wb_subscriptions
    ADD CONSTRAINT "$1" FOREIGN KEY (bugid) REFERENCES wb_bugs(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY wb_subscriptions
    ADD CONSTRAINT "$2" FOREIGN KEY (userid) REFERENCES wb_users(id) ON UPDATE CASCADE ON DELETE CASCADE;

-- Bring sequences up to date

SELECT pg_catalog.setval ('wb_status_id_seq', 9, true);
SELECT pg_catalog.setval ('wb_type_id_seq', 3, true);
SELECT pg_catalog.setval ('wb_priority_id_seq', 5, true);

-- Create the admin account
INSERT INTO wb_users (name, password, fullname, email, super)
    VALUES ('admin', '21232f297a57a5a743894a0e4a801fc3', 'Administrator', '', true);

