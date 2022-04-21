#2.2.0

## Fixes
* Fixed PHP Notice when importing CSV file with attachments.
* Fixed issue with disappearing custom fields when WC product is being updated.
* Fixed issues with importing variable products.
* Fixed PHP notices when importing attachments set as not translatable post type.
* Fixed PHP notices when deleting multiple post imports.
* Fixed issue with not removing images on post update.

## Features
* Unique identifier in WPML metabox is prefilled with the value from the previous import.


#2.1.1

* Updated rapid add-on framework to version 1.1.1, fixing missing break statement between cases 'file' and 'image' (wpmlai-92)

#2.1

* Updated rapid add-on framework to version 1.1.0 (wpmlai-85)

#2.0.8

* Fixed fatal error while importing images (wpmlai-81)

#2.0.7

* Added fix to correctly calculate WC taxonomies after the import (wpmlai-78, wpmlai-71) 

#2.0.6

* Added support for importing tags and other taxonomies (WPMLAI-67)
* Fixed PHP notices

#2.0.5

##Fixes

* Fixed not setting setting _wcml_duplicate_of_variation metakey for variations (WPMLAI-68)


#2.0.4

##Fixes

* Fixed interrupted import of variable products in secondary languages (WPMLAI-50)

#2.0.3

##Fixes

* Fixed PHP Fatal Error on deleting imports
* Fixed PHP Notices when WPML wizard is not finished
* Updated Unique Key to Unique Identifier field label

#2.0.2

##Fixes

* Fixed error while importing terms which translations are same as in original language

#2.0.1

##Fixes

* Fixed error while activating when WP All Import absent

#2.0.0

* Initial release (previous versions was maintained not by WPML team)