# Changelog

## v1.3.0 — 2026-01-30

- Added pluggable SMS support with generic HTTP driver and driver extension points.
- Added WhatsApp Business channel powered by `asimnet/wba-filament`.
- Introduced per-topic channel preferences (`fcm_enabled`, `sms_enabled`, `wba_enabled`) with mobile APIs (`GET/PATCH /api/notify/topics/preferences`).
- Added tenant migrations for channel flags and WBA/SMS settings; documented tenancy path inclusion.
- Added SMS and WBA webhooks (generic + Taqnyat adapter).
- Updated Filament settings page, README, and provided frontend API schemas/mocks.

