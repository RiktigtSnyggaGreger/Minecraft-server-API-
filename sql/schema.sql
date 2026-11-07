CREATE TABLE IF NOT EXISTS snapshots (
    uuid          CHAR(36)  NOT NULL,
    day           DATE      NOT NULL,
    play_time     INT       NOT NULL,   -- ticks
    deaths        INT       NOT NULL,
    mob_kills     INT       NOT NULL,
    damage_dealt  INT       NOT NULL,   -- tiondels hjärtan
    walked_cm     BIGINT    NOT NULL,   -- cm
    blocks_mined  INT       NOT NULL,
    items_crafted INT       NOT NULL,
    stats         JSON      NOT NULL,   -- hela stats-filen
    PRIMARY KEY (uuid, day)
);
