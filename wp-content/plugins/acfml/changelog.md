# 1.3
* support for ACF Gutenberg Blocks (acfml-94)
* ACF Options Pages handling reverted to old workflow (with switching language switcher on Options page) (acfml-156)

# 1.2

* fixed not displayed serialized galleries (acfml-134)
* fixed fatal error call to undefined function acf_maybe_get_POST() (acfml-138)
* fixed issues with umlauts saved incorrectly (acfml-137)
* fixed relationship fields not copying to translation when there are more than one related items (acfml-141)
* made last parameter to WPML_ACF_Field_Settings::field_value_updated() optional (ACFML-142)
* added GNU GPL license file (acfml-139) 
* fixed translating ACF Options page (it can be done now with WPML String Translation) (ACFML-24)
* fixed fatal error when saving the page/product (ACFML-145)

# 1.1

* you can set field translation preferences directly on the Field Gorup edit screen (acfml-104)
* possibility to hide ACFML field annotations by setting constant ACFML_HIDE_FIELD_ANNOTATIONS to boolean true (acfml-129)

# 1.0.4

* better checking if ACF is active on Windows (acfml-125)
* fixed translation of ACF Options Page (acfml-126)
* fixed fatal error in field adnotations (wpmlsupp-6141)

# 1.0.3

* fixed warning about foreach loop (acfml-123)

# 1.0.2

* fixed checking if ACF is active (acfml-121)

# 1.0.1

* fixed fatal error when ACF plugin disabled (acfml-117)

# 1.0
* fixed missing repeater field on translated posts with "display when post is...' display rule (acfml-107)
* fixed not displayed custom fields on post edit screen when Location rules wasn't correctly resolved (acfml-108)
* added field hints for ACF Pro (acfml-90)
* human readable ACF field names in Multilingual Content Setup metbox (acfml-102)
* removed PHP notices from Add Field Group screen (acfml-90, acfml-110)
* automatically set field preferences for subfields - support for flexible field (acfml-98)
* display WYSIWYG fields in Translation Editor - support for ACF Pro (acfml-39)


# 0.9
* field group display rules are correctly applied for translated posts now (wpmlbridge-125)
* automatically set translation preferences for repeater subfields based on repeater main field (wpmlbridge-23)
* display original field value during creation of translated post (wpmlbridge-144)
* field set to copy-once is correctly synchronised between languages
* fixed display of custom post types and taxonomies in relationship select boxes when posts and/or taxonomies are set to "display as translated" (acfml-95)
* taxonomy fields inside repeater field are correctly copied now during post duplication (acfml-96)
* ACF attachments fields (images, galleries, files...) has translated metadata on secondary language pages (acfml-88)

# 0.8
* added support for WPML "display translated" mode (wpmlbridge-131)
* fixed issue with reordering repeater field (wpmlbridge-98)
* fixed enqueue scripts notices (wpmlbridge-150)
* fixed support for WYSIWYG fields in Translation Editor (wpmlbridge-90)

# 0.7
* Fields are now synchronised also during standard post creation when has "Copy" set (wpmlbridge-101)

# 0.6
* Introduced support for clone fields (wpmlbridge-46)

# 0.5.1
* Fixed impossible duplication of field groups (wpmlbridge-91)

# 0.5
* Fixed issue with field group overwriting: fields are no longer duplicated
* Fixed xliff file generation performance (wpmlbridge-25)
* Fixed maximum nesting level error when duplicating repeater field (wpmlbridge-68)

# 0.4
* Fixed problem with returned wrong data type after conversion (one-item arrays retruned as strings)
* Fixed fields dissapearance when translating field groups
* Added support for Gallery field

# 0.3

* added support for ACF Pro
* convert() method now returns original object id if translation is missing
* fixed not working repeater field

# 0.2

* Moved fix about xliff support from WPML Translation Management to this plugin. If you use xliff files to send documents
to translation, define WPML_ACF_XLIFF_SUPPORT to be true in wp-config.php file.  

# 0.1

* Initial release.
* Fixes issues during post translation with field of types: Post Object, Page Link, Relationship, Taxonomy, Repeater
