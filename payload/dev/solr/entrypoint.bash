#!/usr/bin/env bash

# Check if missing template folder
DESTINATION_EZ="/ezsolr/server/ez"
DESTINATION_TEMPLATE="${DESTINATION_EZ}/template"
if [ ! -d ${DESTINATION_TEMPLATE} ]; then
    cd $PROJECTCMSROOT
    mkdir -p ${DESTINATION_TEMPLATE}

    if [ -f ./vendor/ibexa/solr/bin/generate-solr-config.sh ]; then
        ./vendor/ibexa/solr/bin/generate-solr-config.sh \
          --destination-dir=$DESTINATION_TEMPLATE \
          --solr-version=$SOLR_VERSION \
          --force
    else
        cp -R vendor/ezsystems/ezplatform-solr-search-engine/lib/Resources/config/solr/* ${DESTINATION_TEMPLATE}
    fi
fi

# Check for solr config folder (changes btw 6 and 7)
SOURCE_SOLR="/opt/solr/server/solr/configsets/_default/"
if [ ! -d ${SOURCE_SOLR} ]; then
    SOURCE_SOLR="/opt/solr/server/solr/configsets/basic_configs/"
fi

mkdir -p ${DESTINATION_EZ}
if [ ! -f ${DESTINATION_EZ}/solr.xml ]; then
    cp /opt/solr/server/solr/solr.xml ${DESTINATION_EZ}
    cp ${SOURCE_SOLR}/conf/{currency.xml,solrconfig.xml,stopwords.txt,synonyms.txt,elevate.xml} ${DESTINATION_TEMPLATE}
    sed -i.bak '/<updateRequestProcessorChain name="add-unknown-fields-to-the-schema".*/,/<\/updateRequestProcessorChain>/d' ${DESTINATION_TEMPLATE}/solrconfig.xml
    sed -i -e 's/<maxTime>${solr.autoSoftCommit.maxTime:-1}<\/maxTime>/<maxTime>${solr.autoSoftCommit.maxTime:20}<\/maxTime>/g' ${DESTINATION_TEMPLATE}/solrconfig.xml
    sed -i -e 's/<dataDir>${solr.data.dir:}<\/dataDir>/<dataDir>\/opt\/solr\/data\/${solr.core.name}<\/dataDir>/g' ${DESTINATION_TEMPLATE}/solrconfig.xml
fi

SOLR_CORES=${SOLR_CORES:-collection1}
CREATE_CORES=false

for core in $SOLR_CORES
do
    if [ ! -d ${DESTINATION_EZ}/${core} ]; then
        CREATE_CORES=true
        echo "Found missing core: ${core}"
    fi
done

if [ "$CREATE_CORES" = true ]; then
    touch ${DESTINATION_EZ}/solr.creating.cores
    echo "Start solr on background to create missing cores"
    /opt/solr/bin/solr -s ${DESTINATION_EZ}

    for core in $SOLR_CORES
    do
        if [ ! -d ${DESTINATION_EZ}/${core} ]; then
            /opt/solr/bin/solr create_core -c ${core}  -d ${DESTINATION_TEMPLATE}
            echo "Core ${core} created."
        fi
    done
    echo "Stop background solr"
    /opt/solr/bin/solr stop
    rm ${DESTINATION_EZ}/solr.creating.cores
fi

/opt/solr/bin/solr -s ${DESTINATION_EZ} -f
