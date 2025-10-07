# Test fixtures

Local test fixtures [almost matching](#local-changes) the structure from
<https://github.com/itk-dev/event-database-imports/tree/develop/src/DataFixtures/indexes>.

## Local changes

* A tag requires a `slug`:

  ``` diff
  {
       "name": "aros",
  +    "slug": "aros",
       "vocabulary": [
           "aarhusguiden"
       ]
  ```

* A vocabulary requires a `slug`:

  ``` diff
  {
       "name": "aarhusguiden",
  +    "slug": "aarhusguiden",
       "tags": [
           "aros",
           "theoceanraceaarhus",
  ```
