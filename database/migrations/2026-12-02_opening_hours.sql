-- Sprint 33: kitchen opening hours. Run once on an existing database (a fresh import of campusbite.sql already has them).
-- Times are Ghana time (Africa/Accra). Both NULL means no set hours: the kitchen is open whenever "Taking orders" is on,
-- which is how every kitchen behaved before, so nothing changes until a kitchen admin sets its hours in Kitchen info.
-- closes_at earlier than opens_at means the kitchen stays open past midnight (e.g. 18:00 to 02:00).

ALTER TABLE `cb_brands`
  ADD COLUMN `opens_at` time DEFAULT NULL AFTER `admin_paused`,
  ADD COLUMN `closes_at` time DEFAULT NULL AFTER `opens_at`;
