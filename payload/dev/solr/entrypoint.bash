#!/usr/bin/env bash

# Check if missing template folder
DESTINATION_EZ="/ezsolr/server/ez"
DESTINATION_TEMPLATE="$DESTINATION_EZ/template"
if [ ! -d ${DESTINATION_TEMPLATE} ]; then
    cd $PROJECTMAPPINGFOLDER/ezplatform
    mkdir -p $DESTINATION_TEMPLATE
    cp -R vendor/ezsystems/ezplatform-solr-search-engine/lib/Resources/config/solr/* $DESTINATION_TEMPLATE
fi

mkdir -p /ezsolr/server/ez
if [ ! -f /ezsolr/server/ez/solr.xml ]; then
    cp /opt/solr/server/solr/solr.xml /ezsolr/server/ez
    cp /opt/solr/server/solr/configsets/basic_configs/conf/{currency.xml,solrconfig.xml,stopwords.txt,synonyms.txt,elevate.xml} /ezsolr/server/ez/template
    sed -i.bak '/<updateRequestProcessorChain name="add-unknown-fields-to-the-schema">/,/<\/updateRequestProcessorChain>/d' /ezsolr/server/ez/template/solrconfig.xml
    sed -i -e 's/<maxTime>${solr.autoSoftCommit.maxTime:-1}<\/maxTime>/<maxTime>${solr.autoSoftCommit.maxTime:20}<\/maxTime>/g' /ezsolr/server/ez/template/solrconfig.xml
    sed -i -e 's/<dataDir>${solr.data.dir:}<\/dataDir>/<dataDir>\/opt\/solr\/data\/${solr.core.name}<\/dataDir>/g' /ezsolr/server/ez/template/solrconfig.xml
fi

SOLR_CORES=${SOLR_CORES:-collection1}
CREATE_CORES=false

for core in $SOLR_CORES
do
    if [ ! -d /ezsolr/server/ez/${core} ]; then
        CREATE_CORES=true
        echo "Found missing core: ${core}"
    fi
done

if [ "$CREATE_CORES" = true ]; then
    echo "Try to start solr on background..."
    /opt/solr/bin/solr -s /ezsolr/server/ez

    for core in $SOLR_CORES
    do
        if [ ! -d /ezsolr/server/ez/${core} ]; then
            /opt/solr/bin/solr create_core -c ${core}  -d /ezsolr/server/ez/template
            echo "Core ${core} created."
        fi
    done
    echo "Try to stop background solr..."
    /opt/solr/bin/solr stop
fi

/opt/solr/bin/solr -s /ezsolr/server/ez -f