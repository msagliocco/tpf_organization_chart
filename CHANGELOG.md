# Changelog
All notable changes to the TPF Organization Chart module will be documented in this file.

## [1.1.0] - 2024-11-08

### Added
- New routes:
  - `/organization` landing page with visualization type selection
  - `/organization/table` dedicated table view
  - `/organization/treeview` dedicated tree view
- View switcher buttons on table and tree views
- Expand/Collapse All functionality in table view
- Configurable full name field in module settings
- Bootstrap Icons integration

### Changed
- Reorganized module structure with dedicated pages for each view
- Improved template organization with new template files:
  - `tpf-organization-page.html.twig`
  - `tpf-organization-table-view.html.twig`
  - `tpf-organization-tree-view.html.twig`
- Enhanced JavaScript functionality for collapse/expand features
- Updated permission handling for new routes
- Improved configuration form with additional field options

### Fixed
- HTML entity encoding in description fields
- Chevron icon toggle behavior in table view
- Permission consistency across all module routes

### Technical
- Added new configuration schema for full name field
- Improved code organization in OrganizationChartController
- Enhanced template structure for better maintainability
- Updated JavaScript to handle multiple view types
- Added proper cache tags for new routes

## [1.0.0] - Initial Release

- Basic organization chart functionality
- Table and tree view support
- Taxonomy term integration
- Basic configuration options 