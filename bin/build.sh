#!/bin/bash

cd $(dirname $0)/..

for file in templates/scss/*; do 
  filename=`basename $file`; 
  echo "Building $file as ${filename%.*}.css";
  node_modules/.bin/sass $file htdocs/static/generated/${filename%.*}.css; 
done;