--/////////////////////////////////
-- prefix_block_poll
--/////////////////////////////////

CREATE TABLE prefix_block_poll (
    id SERIAL8 PRIMARY KEY,
    name varchar(64) NOT NULL DEFAULT '',
    courseid bigint NOT NULL DEFAULT 0,
    questiontext text NOT NULL DEFAULT '',
    eligible varchar(10) NOT NULL DEFAULT 'all', CHECK (eligible IN ('all', 'students', 'teachers')),
    created bigint DEFAULT 0 NOT NULL
);

COMMENT ON TABLE prefix_block_poll IS 'Contains polls for the poll block';

--/////////////////////////////////
-- prefix_block_poll_option
--/////////////////////////////////

CREATE TABLE prefix_block_poll_option (
    id SERIAL8 PRIMARY KEY,
    pollid bigint NOT NULL DEFAULT 0,
    optiontext text NOT NULL DEFAULT ''
);

COMMENT ON TABLE prefix_block_poll_option IS 'Contains options for each poll in the poll block';

--/////////////////////////////////
-- prefix_block_poll_response
--/////////////////////////////////

CREATE TABLE prefix_block_poll_response (
    id SERIAL8 PRIMARY KEY,
    pollid bigint DEFAULT 0 NOT NULL,
    optionid bigint DEFAULT 0 NOT NULL,
    userid bigint NOT NULL default 0,
    submitted bigint NOT NULL default 0
);

COMMENT ON TABLE prefix_block_poll_response IS 'Contains response info for each poll in the poll block.';
