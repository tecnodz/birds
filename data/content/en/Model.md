
Birds Model
===========

Models are an ORM abstraction for the database — Birds aims to handle multiple types of databases simultaneously. For every model there must be a schema definition with details on the table structure, relationships, triggers (events) and custom column collections (scopes).

## Config & Install

The easiest way to start using models is to configure the database connection, and run the schema synchronization command. On your site configuration file:


```yaml
---
all:
  Birds:
    ...
  Data:
    database1:
      dsn: mysql:host=db;dbname=database;charset=utf8
      username: user
      password: secure-password****
...
```

Then run on command line:

```bash

Birds$ ./bird schema  [--lib-dir=./lib] [--site=sitename] [--connection=connectionName] [table1] [tablen]

```

This will record the schema and create the proper class to access it. For example, the following MySQL table:

**page** table

| Field      | Type                | Null | Key | Default | Extra          |
|------------|---------------------|------|-----|---------|----------------|
| id         | bigint(19) unsigned | NO   | PRI | NULL    | auto_increment |
| url        | varchar(150)        | NO   | MUL | NULL    |                |
| language   | varchar(5)          | NO   |     | NULL    |                |
| title      | varchar(250)        | YES  |     | NULL    |                |
| formats    | varchar(250)        | YES  |     | NULL    |                |
| script     | varchar(250)        | YES  |     | NULL    |                |
| stylesheet | varchar(250)        | YES  |     | NULL    |                |
| multiviews | tinyint(1)          | YES  |     | NULL    |                |
| created    | datetime(6)         | NO   |     | NULL    |                |
| modified   | datetime(6)         | NO   | MUL | NULL    |                |
| published  | datetime(6)         | YES  | MUL | NULL    |                |


Would be translated, as a schema in Yaml:

```yaml
---
class: Page
table: page
columns:
  id:         { type: int, min: 0, increment: auto, "null": false, primary: true }
  url:        { type: string, size: 150, "null": false }
  language:   { type: string, size: 5, "null": false }
  title:      { type: string, size: 250, "null": true }
  formats:    { type: string, size: 250, "null": true }
  script:     { type: string, size: 250, "null": true }
  stylesheet: { type: string, size: 250, "null": true }
  multiviews: { type: int, max: 1, "null": true }
  created:    { type: datetime, "null": false }
  modified:   { type: datetime, "null": false }
  published:  { type: datetime, "null": true }
relations:
  Content:    { local: id, foreign: page, type: many }
scope:
  primary: [ id ]
  route: [ uid, url, language, formats, options, meta, content ]
```

And be available as the class `Page`:

```php
class Page extends Birds\Model
{
    public static $schemaid='page';
    protected $id, $url, $language, $title, $formats, $script, $stylesheet, $multiviews, $created, $modified, $published, $Content;
}
```

That is enough to enable Birds to handle most actions to lookup, review and manage its contents.

## Usage

These are the methods available to all Models:

* catalog (static): Displays a crud-like list of existing records, enables each record preview/update;
* render
* create (static): Alias to create a new object;
* find (static): Queries the database and returns a handler with the current resultset
* label (static): Renders the column label
* isNew
* delete
* save
* event
* timestamp
* autoincrement
* relation
* asArray
* asJson
* schema (static): Returns the class schema configuration (array)
* scope (static): Returns the columns that form the scope
* getScope: actual model scope
* setScope: sets actual model scope
* get*: magically gets the field name value (field_name is accessed as $object->fieldName, $object['field_name'] or $object->getFieldName)
* set*: magically sets the field value
* handler (static from Birds\Data): returns the database handler for the class

