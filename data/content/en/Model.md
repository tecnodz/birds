
Birds Model
===========

Models are an ORM abstraction for the database — Birds aims to handle multiple types of databases simultaneously. For every model there must be a schema definition with details on the table structure, relationships, triggers (events) and custom column collections (scopes).

If we have, for example, the following table:
+--------------------------------------------------------------------------+
| **page**                                                                 |
+------------+---------------------+------+-----+---------+----------------+
| Field      | Type                | Null | Key | Default | Extra          |
+------------+---------------------+------+-----+---------+----------------+
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
+------------+---------------------+------+-----+---------+----------------+

It would be translated, as a schema in Yaml:

...yaml
---
class: Page
table: page
columns:
  id:         { type: int, min: 0, increment: auto, "null": false, primary: true }
  url:        { type: string, size: "150", "null": false }
  language:   { type: string, size: "5", "null": false }
  title:      { type: string, size: "250", "null": true }
  formats:    { type: string, size: "250", "null": true }
  script:     { type: string, size: "250", "null": true }
  stylesheet: { type: string, size: "250", "null": true }
  multiviews: { type: int, max: 1, "null": true }
  created:    { type: datetime, "null": false }
  modified:   { type: datetime, "null": false }
  published:  { type: datetime, "null": true }
relations:
  Content:    { local: id, foreign: page, type: many }
scope:
  primary: [ id ]
  route: [ uid, url, language, formats, options, meta, content ]
...

That would be available as the class `Page`:

...php
class Page extends Birds\Model
{
    public static $schemaid='page';
    protected $id, $url, $language, $title, $formats, $script, $stylesheet, $multiviews, $created, $modified, $published, $Content;
}
...

That is enough to enable Birds to handle most actions to lookup, review and manage its contents.