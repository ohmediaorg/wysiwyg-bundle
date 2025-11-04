# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [[ef2df1c](https://github.com/ohmediaorg/wysiwyg-bundle/commit/ef2df1cc64ea236bcd97f5b40977679b3d5a7f89)] - 2025-10-17

### Added

### Changed

- allow for preservation of Twig array and object syntax in shortcodes
- ensure `image` tag replace and restore accounts for attributes parameter

### Fixed

## [[adc2347](https://github.com/ohmediaorg/wysiwyg-bundle/commit/adc2347d57f71a8ea156cfc19b98bf9e5fa1e7a5)] - 2025-10-17

### Added

### Changed

- all content from `wysiwyg` Twig function is wrapped with
`<div class="wysiwyg-container"></div>`

### Fixed

## [[90a72b73](https://github.com/ohmediaorg/wysiwyg-bundle/commit/90a72b73bbfdcef402e43c37c7b24a31917b1064)] - 2025-10-17

### Added

- TinyMCE Image plugin integration with custom Image Browser and upload
- ability to configure TinyMCE image_class_list and link_class_list options
- TinyMCE inline styles to indicate if a link is a button
- TreeItem and TreeItemBuilder for common TinyMCE data structuring

### Changed

- TinyMCE custom File Browser plugin is only for file_href shortcodes
- shortcode locating can be performed on multiple shortcodes at once
- `image` and `file_href` shortcodes are replaced with human data before TinyMCE
editor is populated, and restored before TinyMCE data is saved in the DB

### Fixed

## [[8f4e21c](https://github.com/ohmediaorg/wysiwyg-bundle/commit/8f4e21c087e6130de1969d9a918c7b7ec88613e1)] - 2025-09-08

Anything implementing `WysiwygRepositoryInterface` will need to replace
`containsWysiwygShortcodes(...)` with 5 new functions.

The `shortcode_script()` tag will need to be placed somewhere in the `<body>` to
enable shortcode locating (already done in backend-bundle).

### Added

- display where a shortcode is used via the `shortcode()` Twig function

### Changed

### Fixed

- preserve copied link text when replacing with Content Links and File Browser
shortcodes

## [[818bbc5](https://github.com/ohmediaorg/wysiwyg-bundle/commit/818bbc5e37d1b2a55ff81dfa067d06533b57ee60)] - 2025-05-06

### Added

### Changed

- removed ability to flag Shortcode as dynamic
- added `<div>` to available TinyMCE block_formats
- enable TinyMCE paste_block_drop
- disable TinyMCE paste_data_images
- allow for `<p>&nbsp;</p>` in TinyMCE editor

### Fixed

- custom TinyMCE URL converter to ensure various URLs are handled properly
