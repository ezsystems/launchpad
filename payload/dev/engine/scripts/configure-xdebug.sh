#!/bin/sh

if [ "${XDEBUG_ENABLE}" = "true" ] ; then
    STEP='Configuring XDebug'
    echo "[....] "${STEP}

    if [ $(php -m | grep 'xdebug' | wc -l) -eq 0 ]; then
        pecl install xdebug
    fi

    XDEBUG_CONFIG_FILE_TMP=$(php -i | grep xdebug | grep ini | cut -d ',' -f 1)
    if [ -L ${XDEBUG_CONFIG_FILE_TMP} ];then
        XDEBUG_CONFIG_FILE=$(readlink ${XDEBUG_CONFIG_FILE_TMP})
        cd $(dirname ${XDEBUG_CONFIG_FILE_TMP})
    else
        XDEBUG_CONFIG_FILE=${XDEBUG_CONFIG_FILE_TMP}
    fi

    if [ -f "${XDEBUG_CONFIG_FILE}" ]; then
        if [ $(cat "${XDEBUG_CONFIG_FILE}" | grep ';zend_extension' | wc -l) -eq 1 ]; then
            sed -i "s#^;zend_extension=#zend_extension=#g" "${XDEBUG_CONFIG_FILE}"
        fi

        if [ $(cat "${XDEBUG_CONFIG_FILE}" | grep 'xdebug.remote_enable' | wc -l) -eq 0 ]; then
            echo "xdebug.remote_enable=1" >> "${XDEBUG_CONFIG_FILE}"
        fi

        if [ ! -z ${XDEBUG_REMOTE_HOST} ]; then
            if [ $(cat "${XDEBUG_CONFIG_FILE}" | grep 'xdebug.remote_host' | wc -l) -eq 0 ]; then
                echo "xdebug.remote_host=${XDEBUG_REMOTE_HOST}" >> "${XDEBUG_CONFIG_FILE}"
            else
                sed -i "s#xdebug.remote_host=.*#xdebug.remote_host=${XDEBUG_REMOTE_HOST}#g" "${XDEBUG_CONFIG_FILE}"
            fi
        fi

        if [ ! -z ${XDEBUG_IDEKEY} ]; then
            if [ $(cat "${XDEBUG_CONFIG_FILE}" | grep 'xdebug.idekey' | wc -l) -eq 0 ]; then
                echo "xdebug.idekey=${XDEBUG_IDEKEY}" >> "${XDEBUG_CONFIG_FILE}"
            else
                sed -i "s#xdebug.idekey=.*#xdebug.idekey=${XDEBUG_IDEKEY}#g" "${XDEBUG_CONFIG_FILE}"
            fi
        fi

        if [ ! -z ${XDEBUG_REMOTE_PORT} ]; then
            if [ $(cat "${XDEBUG_CONFIG_FILE}" | grep 'xdebug.remote_port' | wc -l) -eq 0 ]; then
                echo "xdebug.remote_port=${XDEBUG_REMOTE_PORT}" >> "${XDEBUG_CONFIG_FILE}"
            else
                sed -i "s#xdebug.remote_port=.*#xdebug.remote_port=${XDEBUG_REMOTE_PORT}#g" "${XDEBUG_CONFIG_FILE}"
            fi
        fi

        if [ $(cat "${XDEBUG_CONFIG_FILE}" | grep 'xdebug.max_nesting_level' | wc -l) -eq 0 ]; then
            echo "xdebug.max_nesting_level=1000" >> "${XDEBUG_CONFIG_FILE}"
        else
            sed -i "s#xdebug.max_nesting_level=.*#xdebug.nesting_level=1000#g" "${XDEBUG_CONFIG_FILE}"
        fi

        if [ ! -z ${XDEBUG_REMOTE_AUTOSTART} ]; then
            if [ $(cat "${XDEBUG_CONFIG_FILE}" | grep 'xdebug.remote_autostart' | wc -l) -eq 0 ]; then
                echo "xdebug.remote_autostart=${XDEBUG_REMOTE_AUTOSTART}" >> "${XDEBUG_CONFIG_FILE}"
            else
                sed -i "s#xdebug.remote_autostart=.*#xdebug.remote_autostart=${XDEBUG_REMOTE_AUTOSTART}#g" "${XDEBUG_CONFIG_FILE}"
            fi
        fi

        if [ ! -z ${XDEBUG_REMOTE_CONNECT_BACK} ]; then
            if [ $(cat "${XDEBUG_CONFIG_FILE}" | grep 'xdebug.remote_connect_back' | wc -l) -eq 0 ]; then
                echo "xdebug.remote_connect_back=${XDEBUG_REMOTE_CONNECT_BACK}" >> "${XDEBUG_CONFIG_FILE}"
            else
                sed -i "s#xdebug.remote_connect_back=.*#xdebug.remote_connect_back=${XDEBUG_REMOTE_CONNECT_BACK}#g" "${XDEBUG_CONFIG_FILE}"
            fi
        fi

    fi

    echo "[ OK ] "${STEP}
fi