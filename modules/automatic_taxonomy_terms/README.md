This module is able to automatically create taxonomy terms for you when an
entity, of any type, is being created.

##Features

* Configure per vocabulary if taxonomy terms should be automatically created.
* Pick your entity type(s) on which taxonomy terms should be created.
* Configure per entity type's bundle what the name of your taxonomy term should
be. This field supports Tokens.
* Optionally configure per entity type's bundle what the parent taxonomy term
of the newly created taxonomy term should be.
* Optionally configure per entity type's bundle to keep taxonomy terms in sync.
This means, that when the entity that caused the creation of the taxonomy term
is being updated, the taxonomy term will be updated aswell.

##Usage

1. Create a taxonomy vocabulary.
2. Navigate from your vocabulary to "Automatic Taxonomy Terms" which is
available under
/admin/structure/taxonomy/manage/{vocabulary_name}/automatic-taxonomy-terms.
3. Choose your entity types and save the configuration.
4. When the page has been reloaded, configuration is available per entity type.
5. Done! Start creating entities!

##Dependencies

* Token
* Hook Event Dispatcher
