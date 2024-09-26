## Install
- `curl -O https://raw.githubusercontent.com/nmaltsev/fm/main/src/fm.php`
- `chmod 777 fm.php`

## Run without any server
`php -S 0.0.0.0:9000 -t ./src`

Links:
fm.php?action=dir&path=%2Fvar%2Fwww%2Fsite
fm.php?action=error&message=Test%2C123&path=%2Fvar%2Fwww%2Fsite

## Requirements
PHP 5.5.15/5.4.8

## TODO
http://localhost:9000/fm.php?action=files&path=%2Fhome%2Fnmaltsev%2FDocuments%2Frepos%2Ficons%2Fimages
