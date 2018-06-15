#!/usr/bin/env bash

mkdir -p /ezsolr/server/ez
if [ ! -f /ezsolr/server/ez/solr.xml ]; then
    cp /opt/solr/server/solr/solr.xml /ezsolr/server/ez
    cp /opt/solr/server/solr/configsets/basic_configs/conf/{currency.xml,solrconfig.xml,stopwords.txt,synonyms.txt,elevate.xml} /ezsolr/server/ez/template
    sed -i.bak '/<updateRequestProcessorChain name="add-unknown-fields-to-the-schema">/,/<\/updateRequestProcessorChain>/d' /ezsolr/server/ez/template/solrconfig.xml
    sed -i -e 's/<maxTime>${solr.autoSoftCommit.maxTime:-1}<\/maxTime>/<maxTime>${solr.autoSoftCommit.maxTime:20}<\/maxTime>/g' /ezsolr/server/ez/template/solrconfig.xml
    sed -i -e 's/<dataDir>${solr.data.dir:}<\/dataDir>/<dataDir>\/opt\/solr\/data\/${solr.core.name}<\/dataDir>/g' /ezsolr/server/ez/template/solrconfig.xml
fi

/opt/solr/bin/solr -s /ezsolr/server/ez -f

SOLR_CORES=${SOLR_CORES:-collection1}
for core in $SOLR_CORES
do
    /opt/solr/bin/solr create_core -c ${core}  -d /ezsolr/server/ez/template
    echo "Core ${core} created."
done