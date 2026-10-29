#!/bin/bash
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    SELECT 'CREATE DATABASE cultiva_test'
    WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'cultiva_test')\gexec

    \c cultiva_test
    CREATE EXTENSION IF NOT EXISTS postgis;
EOSQL
