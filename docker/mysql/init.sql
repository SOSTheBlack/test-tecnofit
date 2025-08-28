-- Configurações iniciais do MySQL para o projeto Tecnofit

-- Garantir que o banco tecnofit_pix existe
CREATE DATABASE IF NOT EXISTS tecnofit_pix;

-- Criar usuário para testes
CREATE DATABASE IF NOT EXISTS tecnofit_pix_test;

-- Configurar timezone
SET GLOBAL time_zone = '-03:00';

-- Configurações de performance
SET GLOBAL innodb_buffer_pool_size = 134217728; -- 128MB
SET GLOBAL max_connections = 100;

USE tecnofit_pix;
