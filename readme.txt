=== WPA9 Gallery ===
Contributors: aloziak
Tags: gallery, album, masonry, images
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.2
Stable tag: 1.3.1
License: GPLv2 or later

A lightweight gallery & album manager. Stores files outside the Media Library and ships with a theme-overridable masonry template.

== Description ==

Storage location: `wp-content/uploads/wpa9-galleries/{gallery-slug}/`
Thumbnails: `wp-content/uploads/wpa9-galleries/{gallery-slug}/thumbs/`

Features:

* Galleries with name, description, photo gallery author, custom thumb size, album assignment
* Albums (name, short description) — galleries can belong to multiple albums
* Per-image caption, alt text, description
* Drag-to-reorder
* Bulk select & delete (incl. "select all")
* AJAX upload via Plupload (drag & drop, multi-file, progress bar)
* No WYSIWYG anywhere — plain textareas only
* Theme-overridable templates: copy `templates/gallery-masonry.php` to `{your-theme}/wpa9-gallery/gallery-masonry.php`
* Standalone CSS — no JS framework needed on the frontend (pure CSS columns masonry)

Shortcodes:

* `[wpa9_gallery id="1"]`
* `[wpa9_gallery slug="my-gallery" template="gallery-masonry" columns="3" gap="12"]`
* `[wpa9_album id="2"]` — renders every gallery in the album

Available template variables:

* `$gallery` — gallery row (id, name, slug, description, author_name, thumb_width, thumb_height, album_id, sort_order, created_at)
* `$images`  — array of image rows (id, gallery_id, filename, caption, description, alt_text, width, height, filesize, sort_order)
* `$album`   — album row or null
* `$atts`    — shortcode attributes

Helper static methods:

* `WPA9_Image::url( $gallery, $image )` — full image URL
* `WPA9_Image::thumb_url( $gallery, $image )` — thumbnail URL

== Installation ==

1. Drop this folder into `wp-content/plugins/` and activate.
2. Open *WPA9 Gallery* in the admin menu, create a gallery, then upload images.

== Changelog ==

= 1.3.1 =
WordPress.org review compliance: enqueue, sanitization, prepared SQL, menu position, uploads index file.

= 1.3.0 =
Fixed hard crop on regenerate thumbnails.
Add Dashboard.

= 1.2.0 =
Add export/import option.

= 1.1.0 =
Add import from NextGen feature.

= 1.0.0 =
Initial release.
