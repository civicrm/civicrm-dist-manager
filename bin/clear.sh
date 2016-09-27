#!/bin/bash

## Determine the absolute path of the directory with the file
## usage: absdirname <file-path>
function absdirname() {
  pushd $(dirname $0) >> /dev/null
    pwd
  popd >> /dev/null
}

BINDIR=$(absdirname "$0")

$BINDIR/console --env=prod cache:clear
$BINDIR/console --env=dev cache:clear
