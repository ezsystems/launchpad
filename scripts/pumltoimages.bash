#!/usr/bin/env bash

BASEDIR=$(dirname $0)
PUML="java -jar ${BASEDIR}/../plantuml.jar"

$PUML -o images/puml docs/*.puml
