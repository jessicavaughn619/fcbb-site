Fountain City Brass Band - Drupal 10 Build 

Using DDEV 

To export a database: 
`ddev drush sql-dump --gzip --result-file=./db.sql`
Creates a db.sql.gz file to send to another device for importing

To import a database:
Drop the unzipped .gz file into the top-level of the repo, and run this command.
`ddev import-db --file=db.sql.gz`