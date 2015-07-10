#!/bin/sh

TYPES='htmlhelp xhtml'
for type in $TYPES
do
  echo "Generating " $type
  rm -rf $type
  xmlto --extensions -m stylesheets/doc2.xsl -o $type $type doggy_docs.xml
  cp stylesheets/book.css $type/
  mkdir $type/images
  cp -a /usr/share/sgml/docbook/docbook-xsl-1.68.1/images/* $type/images/.
  tar zcpf $type.tgz $type
done

echo "Generating XHTML (no chunks)"
xmlto --extensions -m stylesheets/doc2.xsl xhtml-nochunks doggy_docs.xml

echo "Generating PDF"
xmlto --extensions -x stylesheets/doc.xsl pdf doggy_docs.xml
