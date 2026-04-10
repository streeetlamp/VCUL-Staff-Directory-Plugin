# Changelog

All notable changes to this project will be documented in this file.

## 2026-04-10

### Added
- Added `id` query support to `get-directory` for stable profile lookup by post ID.
- Added department `slug` to department list endpoint responses.
- Added a shared helper for returning taxonomy terms as `{ name, slug }` objects.
- Added REST API tests for slug field contracts and `get-directory?id=` behavior.

### Changed
- Updated staff payload term fields (`expertise`, `department`) to return `{ name, slug }` objects across directory/expertise/department responses.
