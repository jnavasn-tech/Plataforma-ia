-- ============================================================================
-- Esquema de base de datos MySQL 8.0 de la plataforma (Anexo F del Capítulo V).
-- Generado a partir de las migraciones de Laravel con: php artisan migrate --pretend
-- Equivale a ejecutar: php artisan migrate. Se recomienda usar las migraciones;
-- este script se incluye como evidencia y para instalaciones sin Artisan.
-- Motor: InnoDB, juego de caracteres utf8mb4 (utf8mb4_unicode_ci).
-- Datos iniciales: php artisan db:seed --class=CatalogoSeeder
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


-- Migración: 2026_06_01_000001_create_usuarios_table
create table `usuarios` (`id_usuario` bigint unsigned not null auto_increment primary key, `nombre` varchar(120) not null, `correo` varchar(150) not null, `email_verified_at` timestamp null, `contrasena_hash` varchar(255) not null, `rol` enum('aspirante', 'admin_rrhh') not null default 'aspirante', `telefono` varchar(20) null, `cargo` varchar(100) null, `es_menor_edad` tinyint(1) not null default '0', `consentimiento_privacidad_at` timestamp null, `version_aviso_privacidad` varchar(10) null, `consentimiento_tutor_at` timestamp null, `ultimo_acceso_at` timestamp null, `activo` tinyint(1) not null default '1', `remember_token` varchar(100) null, `fecha_registro` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `usuarios` add unique `usuarios_correo_unique`(`correo`);
alter table `usuarios` add index `usuarios_rol_index`(`rol`);

-- Migración: 2026_06_01_000002_create_tablas_framework
create table `password_reset_tokens` (`email` varchar(255) not null, `token` varchar(255) not null, `created_at` timestamp null, primary key (`email`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
create table `sessions` (`id` varchar(255) not null, `user_id` bigint unsigned null, `ip_address` varchar(45) null, `user_agent` text null, `payload` longtext not null, `last_activity` int not null, primary key (`id`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `sessions` add index `sessions_user_id_index`(`user_id`);
alter table `sessions` add index `sessions_last_activity_index`(`last_activity`);
create table `jobs` (`id` bigint unsigned not null auto_increment primary key, `queue` varchar(255) not null, `payload` longtext not null, `attempts` tinyint unsigned not null, `reserved_at` int unsigned null, `available_at` int unsigned not null, `created_at` int unsigned not null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `jobs` add index `jobs_queue_index`(`queue`);
create table `failed_jobs` (`id` bigint unsigned not null auto_increment primary key, `uuid` varchar(255) not null, `connection` text not null, `queue` text not null, `payload` longtext not null, `exception` longtext not null, `failed_at` timestamp not null default CURRENT_TIMESTAMP) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `failed_jobs` add unique `failed_jobs_uuid_unique`(`uuid`);
create table `notifications` (`id` char(36) not null, `type` varchar(255) not null, `notifiable_type` varchar(255) not null, `notifiable_id` bigint unsigned not null, `data` text not null, `read_at` timestamp null, `created_at` timestamp null, `updated_at` timestamp null, primary key (`id`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `notifications` add index `notifications_notifiable_type_notifiable_id_index`(`notifiable_type`, `notifiable_id`);

-- Migración: 2026_06_01_000003_create_areas_competencia_table
create table `areas_competencia` (`id_area` bigint unsigned not null auto_increment primary key, `codigo` varchar(10) not null, `nombre` varchar(80) not null, `descripcion` text null, `referencia_digcomp` varchar(150) null, `orden` smallint unsigned not null default '1', `activo` tinyint(1) not null default '1', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `areas_competencia` add unique `areas_competencia_codigo_unique`(`codigo`);

-- Migración: 2026_06_01_000004_create_procesos_seleccion_table
create table `procesos_seleccion` (`id_proceso` bigint unsigned not null auto_increment primary key, `nombre` varchar(120) not null, `puesto` varchar(120) not null, `fecha_inicio` date not null, `fecha_fin` date null, `activo` tinyint(1) not null default '1', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `procesos_seleccion` add index `procesos_seleccion_activo_index`(`activo`);
create table `postulaciones` (`id_postulacion` bigint unsigned not null auto_increment primary key, `id_usuario` bigint unsigned not null, `id_proceso` bigint unsigned not null, `estado` enum('pendiente', 'en_formacion', 'apto', 'no_apto') not null default 'pendiente', `actualizado_por` bigint unsigned null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `postulaciones` add constraint `postulaciones_id_usuario_foreign` foreign key (`id_usuario`) references `usuarios` (`id_usuario`) on delete cascade;
alter table `postulaciones` add constraint `postulaciones_id_proceso_foreign` foreign key (`id_proceso`) references `procesos_seleccion` (`id_proceso`) on delete restrict;
alter table `postulaciones` add constraint `postulaciones_actualizado_por_foreign` foreign key (`actualizado_por`) references `usuarios` (`id_usuario`) on delete set null;
alter table `postulaciones` add unique `postulaciones_id_usuario_id_proceso_unique`(`id_usuario`, `id_proceso`);
alter table `postulaciones` add index `postulaciones_estado_index`(`estado`);
create table `historial_estados` (`id_historial` bigint unsigned not null auto_increment primary key, `id_postulacion` bigint unsigned not null, `estado_anterior` enum('pendiente', 'en_formacion', 'apto', 'no_apto') null, `estado_nuevo` enum('pendiente', 'en_formacion', 'apto', 'no_apto') not null, `id_admin` bigint unsigned null, `comentario` varchar(500) null, `fecha` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `historial_estados` add constraint `historial_estados_id_postulacion_foreign` foreign key (`id_postulacion`) references `postulaciones` (`id_postulacion`) on delete cascade;
alter table `historial_estados` add constraint `historial_estados_id_admin_foreign` foreign key (`id_admin`) references `usuarios` (`id_usuario`) on delete set null;
alter table `historial_estados` add index `historial_estados_fecha_index`(`fecha`);

-- Migración: 2026_06_01_000005_create_modulos_contenido_table
create table `modulos_contenido` (`id_modulo` bigint unsigned not null auto_increment primary key, `id_area` bigint unsigned not null, `codigo` varchar(20) not null, `nombre` varchar(150) not null, `descripcion` text null, `nivel` enum('basico', 'intermedio', 'avanzado') not null, `orden` smallint unsigned not null default '1', `activo` tinyint(1) not null default '1', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `modulos_contenido` add constraint `modulos_contenido_id_area_foreign` foreign key (`id_area`) references `areas_competencia` (`id_area`) on delete restrict;
alter table `modulos_contenido` add index `modulos_contenido_id_area_nivel_activo_index`(`id_area`, `nivel`, `activo`);
alter table `modulos_contenido` add unique `modulos_contenido_codigo_unique`(`codigo`);

-- Migración: 2026_06_01_000006_create_recursos_aprendizaje_table
create table `recursos_aprendizaje` (`id_recurso` bigint unsigned not null auto_increment primary key, `id_modulo` bigint unsigned not null, `titulo` varchar(150) not null, `descripcion` text null, `tipo` enum('lectura', 'video', 'articulo', 'ejercicio') not null, `url` varchar(500) null, `contenido` text null, `duracion_minutos` smallint unsigned null, `estado` enum('activo', 'pendiente_verificacion', 'inactivo') not null default 'activo', `creado_por` bigint unsigned null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `recursos_aprendizaje` add constraint `recursos_aprendizaje_id_modulo_foreign` foreign key (`id_modulo`) references `modulos_contenido` (`id_modulo`) on delete restrict;
alter table `recursos_aprendizaje` add constraint `recursos_aprendizaje_creado_por_foreign` foreign key (`creado_por`) references `usuarios` (`id_usuario`) on delete set null;
alter table `recursos_aprendizaje` add index `recursos_aprendizaje_id_modulo_estado_index`(`id_modulo`, `estado`);
alter table `recursos_aprendizaje` add unique `recursos_aprendizaje_url_unique`(`url`);

-- Migración: 2026_06_01_000007_create_banco_preguntas_tables
create table `preguntas` (`id_pregunta` bigint unsigned not null auto_increment primary key, `id_area` bigint unsigned not null, `id_modulo` bigint unsigned null, `uso` enum('diagnostico', 'evaluacion_modulo') not null, `dificultad` tinyint unsigned not null, `enunciado` text not null, `explicacion` text null, `activo` tinyint(1) not null default '1', `version` smallint unsigned not null default '1', `creado_por` bigint unsigned null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `preguntas` add constraint `preguntas_id_area_foreign` foreign key (`id_area`) references `areas_competencia` (`id_area`) on delete restrict;
alter table `preguntas` add constraint `preguntas_id_modulo_foreign` foreign key (`id_modulo`) references `modulos_contenido` (`id_modulo`) on delete restrict;
alter table `preguntas` add constraint `preguntas_creado_por_foreign` foreign key (`creado_por`) references `usuarios` (`id_usuario`) on delete set null;
alter table `preguntas` add index `preguntas_uso_id_area_dificultad_activo_index`(`uso`, `id_area`, `dificultad`, `activo`);
create table `opciones_respuesta` (`id_opcion` bigint unsigned not null auto_increment primary key, `id_pregunta` bigint unsigned not null, `texto` varchar(300) not null, `es_correcta` tinyint(1) not null default '0', `orden` tinyint unsigned not null default '1', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `opciones_respuesta` add constraint `opciones_respuesta_id_pregunta_foreign` foreign key (`id_pregunta`) references `preguntas` (`id_pregunta`) on delete cascade;

-- Migración: 2026_06_01_000008_create_diagnosticos_tables
create table `diagnosticos` (`id_diagnostico` bigint unsigned not null auto_increment primary key, `id_usuario` bigint unsigned not null, `id_proceso` bigint unsigned null, `tipo` enum('pretest', 'postest') not null default 'pretest', `estado` enum('iniciado', 'en_progreso', 'completado', 'abandonado') not null default 'iniciado', `total_items` smallint unsigned not null, `iniciado_en` timestamp null, `ultima_actividad_en` timestamp null, `finalizado_en` timestamp null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `diagnosticos` add constraint `diagnosticos_id_usuario_foreign` foreign key (`id_usuario`) references `usuarios` (`id_usuario`) on delete cascade;
alter table `diagnosticos` add constraint `diagnosticos_id_proceso_foreign` foreign key (`id_proceso`) references `procesos_seleccion` (`id_proceso`) on delete set null;
alter table `diagnosticos` add index `diagnosticos_id_usuario_tipo_estado_index`(`id_usuario`, `tipo`, `estado`);
alter table `diagnosticos` add index `diagnosticos_finalizado_en_index`(`finalizado_en`);
create table `respuestas_diagnostico` (`id_respuesta` bigint unsigned not null auto_increment primary key, `id_diagnostico` bigint unsigned not null, `id_pregunta` bigint unsigned not null, `id_opcion` bigint unsigned null, `orden` smallint unsigned not null, `dificultad` tinyint unsigned not null, `es_correcta` tinyint(1) null, `presentada_en` timestamp null, `respondida_en` timestamp null, `tiempo_respuesta_ms` int unsigned null, `tiempo_servidor_ms` int unsigned null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `respuestas_diagnostico` add constraint `respuestas_diagnostico_id_diagnostico_foreign` foreign key (`id_diagnostico`) references `diagnosticos` (`id_diagnostico`) on delete cascade;
alter table `respuestas_diagnostico` add constraint `respuestas_diagnostico_id_pregunta_foreign` foreign key (`id_pregunta`) references `preguntas` (`id_pregunta`) on delete restrict;
alter table `respuestas_diagnostico` add constraint `respuestas_diagnostico_id_opcion_foreign` foreign key (`id_opcion`) references `opciones_respuesta` (`id_opcion`) on delete restrict;
alter table `respuestas_diagnostico` add unique `respuestas_diagnostico_id_diagnostico_id_pregunta_unique`(`id_diagnostico`, `id_pregunta`);
alter table `respuestas_diagnostico` add unique `respuestas_diagnostico_id_diagnostico_orden_unique`(`id_diagnostico`, `orden`);

-- Migración: 2026_06_01_000009_create_perfiles_competencia_table
create table `perfiles_competencia` (`id_perfil` bigint unsigned not null auto_increment primary key, `id_diagnostico` bigint unsigned not null, `nivel_general` enum('basico', 'intermedio', 'avanzado') not null, `puntaje_global` decimal(5, 2) not null, `areas_evaluadas_json` json not null, `resumen` text null, `fortalezas_json` json null, `areas_mejora_json` json null, `recomendacion` text null, `origen_interpretacion` enum('ia', 'reglas') null, `version_prompt` varchar(20) null, `nivel_control` enum('basico', 'intermedio', 'avanzado') null, `control_registrado_por` bigint unsigned null, `control_registrado_en` timestamp null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `perfiles_competencia` add constraint `perfiles_competencia_id_diagnostico_foreign` foreign key (`id_diagnostico`) references `diagnosticos` (`id_diagnostico`) on delete cascade;
alter table `perfiles_competencia` add constraint `perfiles_competencia_control_registrado_por_foreign` foreign key (`control_registrado_por`) references `usuarios` (`id_usuario`) on delete set null;
alter table `perfiles_competencia` add unique `perfiles_competencia_id_diagnostico_unique`(`id_diagnostico`);

-- Migración: 2026_06_01_000010_create_rutas_aprendizaje_tables
create table `rutas_aprendizaje` (`id_ruta` bigint unsigned not null auto_increment primary key, `id_perfil` bigint unsigned not null, `estado` enum('activa', 'completada', 'reemplazada') not null default 'activa', `origen` enum('ia', 'reglas') not null, `narrativa` text not null, `fortalezas_json` json null, `areas_mejora_json` json null, `es_ruta_avanzada` tinyint(1) not null default '0', `modelo_ia` varchar(60) null, `version_prompt` varchar(20) null, `tiempo_generacion_ms` int unsigned null, `fecha_generacion` timestamp null, `updated_at` timestamp null, `completada_en` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `rutas_aprendizaje` add constraint `rutas_aprendizaje_id_perfil_foreign` foreign key (`id_perfil`) references `perfiles_competencia` (`id_perfil`) on delete cascade;
alter table `rutas_aprendizaje` add unique `rutas_aprendizaje_id_perfil_unique`(`id_perfil`);
alter table `rutas_aprendizaje` add index `rutas_aprendizaje_estado_index`(`estado`);
create table `ruta_modulos` (`id_ruta_modulo` bigint unsigned not null auto_increment primary key, `id_ruta` bigint unsigned not null, `id_modulo` bigint unsigned not null, `orden` smallint unsigned not null, `prioridad` enum('alta', 'media', 'baja') not null, `justificacion` varchar(500) not null, `estado` enum('pendiente', 'en_curso', 'completado') not null default 'pendiente', `porcentaje_avance` decimal(5, 2) not null default '0', `completado_en` timestamp null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `ruta_modulos` add constraint `ruta_modulos_id_ruta_foreign` foreign key (`id_ruta`) references `rutas_aprendizaje` (`id_ruta`) on delete cascade;
alter table `ruta_modulos` add constraint `ruta_modulos_id_modulo_foreign` foreign key (`id_modulo`) references `modulos_contenido` (`id_modulo`) on delete restrict;
alter table `ruta_modulos` add unique `ruta_modulos_id_ruta_id_modulo_unique`(`id_ruta`, `id_modulo`);
alter table `ruta_modulos` add unique `ruta_modulos_id_ruta_orden_unique`(`id_ruta`, `orden`);
create table `progreso_recursos` (`id_progreso` bigint unsigned not null auto_increment primary key, `id_usuario` bigint unsigned not null, `id_recurso` bigint unsigned not null, `id_ruta_modulo` bigint unsigned not null, `iniciado_en` timestamp null, `completado_en` timestamp null, `reportado_no_disponible_en` timestamp null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `progreso_recursos` add constraint `progreso_recursos_id_usuario_foreign` foreign key (`id_usuario`) references `usuarios` (`id_usuario`) on delete cascade;
alter table `progreso_recursos` add constraint `progreso_recursos_id_recurso_foreign` foreign key (`id_recurso`) references `recursos_aprendizaje` (`id_recurso`) on delete cascade;
alter table `progreso_recursos` add constraint `progreso_recursos_id_ruta_modulo_foreign` foreign key (`id_ruta_modulo`) references `ruta_modulos` (`id_ruta_modulo`) on delete cascade;
alter table `progreso_recursos` add unique `progreso_recursos_id_ruta_modulo_id_recurso_unique`(`id_ruta_modulo`, `id_recurso`);

-- Migración: 2026_06_01_000011_create_evaluaciones_tables
create table `evaluaciones` (`id_evaluacion` bigint unsigned not null auto_increment primary key, `id_modulo` bigint unsigned not null, `id_usuario` bigint unsigned not null, `id_ruta_modulo` bigint unsigned not null, `intento` tinyint unsigned not null, `calificacion` decimal(5, 2) not null, `aprobado` tinyint(1) not null, `respuestas_json` json not null, `fecha` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `evaluaciones` add constraint `evaluaciones_id_modulo_foreign` foreign key (`id_modulo`) references `modulos_contenido` (`id_modulo`) on delete restrict;
alter table `evaluaciones` add constraint `evaluaciones_id_usuario_foreign` foreign key (`id_usuario`) references `usuarios` (`id_usuario`) on delete cascade;
alter table `evaluaciones` add constraint `evaluaciones_id_ruta_modulo_foreign` foreign key (`id_ruta_modulo`) references `ruta_modulos` (`id_ruta_modulo`) on delete cascade;
alter table `evaluaciones` add unique `evaluaciones_id_ruta_modulo_intento_unique`(`id_ruta_modulo`, `intento`);
alter table `evaluaciones` add index `evaluaciones_id_usuario_id_modulo_index`(`id_usuario`, `id_modulo`);
create table `retroalimentaciones` (`id_retro` bigint unsigned not null auto_increment primary key, `id_evaluacion` bigint unsigned not null, `texto_generado` text not null, `sugerencias_json` json null, `siguiente_paso` varchar(300) null, `origen` enum('ia', 'reglas') not null, `version_prompt` varchar(20) null, `fecha` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `retroalimentaciones` add constraint `retroalimentaciones_id_evaluacion_foreign` foreign key (`id_evaluacion`) references `evaluaciones` (`id_evaluacion`) on delete cascade;
alter table `retroalimentaciones` add unique `retroalimentaciones_id_evaluacion_unique`(`id_evaluacion`);
create table `valoraciones_modulo` (`id_valoracion` bigint unsigned not null auto_increment primary key, `id_ruta_modulo` bigint unsigned not null, `id_usuario` bigint unsigned not null, `puntuacion` tinyint unsigned not null, `comentario` varchar(500) null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `valoraciones_modulo` add constraint `valoraciones_modulo_id_ruta_modulo_foreign` foreign key (`id_ruta_modulo`) references `ruta_modulos` (`id_ruta_modulo`) on delete cascade;
alter table `valoraciones_modulo` add constraint `valoraciones_modulo_id_usuario_foreign` foreign key (`id_usuario`) references `usuarios` (`id_usuario`) on delete cascade;
alter table `valoraciones_modulo` add unique `valoraciones_modulo_id_ruta_modulo_unique`(`id_ruta_modulo`);

-- Migración: 2026_06_01_000012_create_configuracion_y_bitacora_tables
create table `configuraciones_prompt` (`id_prompt` bigint unsigned not null auto_increment primary key, `clave` enum('diagnostico', 'ruta', 'retroalimentacion') not null, `version` smallint unsigned not null, `contenido` text not null, `descripcion_cambio` varchar(255) null, `activo` tinyint(1) not null default '0', `creado_por` bigint unsigned null, `activado_en` timestamp null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `configuraciones_prompt` add constraint `configuraciones_prompt_creado_por_foreign` foreign key (`creado_por`) references `usuarios` (`id_usuario`) on delete set null;
alter table `configuraciones_prompt` add unique `configuraciones_prompt_clave_version_unique`(`clave`, `version`);
alter table `configuraciones_prompt` add index `configuraciones_prompt_clave_activo_index`(`clave`, `activo`);
create table `bitacora` (`id_bitacora` bigint unsigned not null auto_increment primary key, `id_usuario` bigint unsigned null, `accion` varchar(60) not null, `entidad` varchar(60) null, `id_entidad` bigint unsigned null, `detalle_json` json null, `ip_anonimizada` varchar(45) null, `fecha` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `bitacora` add constraint `bitacora_id_usuario_foreign` foreign key (`id_usuario`) references `usuarios` (`id_usuario`) on delete set null;
alter table `bitacora` add index `bitacora_accion_fecha_index`(`accion`, `fecha`);
create table `bitacora_ia` (`id_bitacora_ia` bigint unsigned not null auto_increment primary key, `tipo` enum('diagnostico', 'ruta', 'retroalimentacion') not null, `estado` enum('exito', 'fallback', 'deshabilitada') not null, `modelo` varchar(60) null, `version_prompt` varchar(20) null, `latencia_ms` int unsigned null, `intentos` tinyint unsigned not null default '0', `tokens_entrada` int unsigned null, `tokens_salida` int unsigned null, `codigo_error` varchar(60) null, `referencia_tipo` varchar(40) null, `referencia_id` bigint unsigned null, `fecha` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `bitacora_ia` add index `bitacora_ia_tipo_estado_fecha_index`(`tipo`, `estado`, `fecha`);

-- Migración: 2026_06_01_000013_add_check_constraints
ALTER TABLE preguntas ADD CONSTRAINT chk_preguntas_dificultad CHECK (dificultad BETWEEN 1 AND 3);
ALTER TABLE respuestas_diagnostico ADD CONSTRAINT chk_respuestas_dificultad CHECK (dificultad BETWEEN 1 AND 3);
ALTER TABLE perfiles_competencia ADD CONSTRAINT chk_perfiles_puntaje CHECK (puntaje_global BETWEEN 0 AND 100);
ALTER TABLE evaluaciones ADD CONSTRAINT chk_evaluaciones_calificacion CHECK (calificacion BETWEEN 0 AND 100);
ALTER TABLE evaluaciones ADD CONSTRAINT chk_evaluaciones_intento CHECK (intento >= 1);
ALTER TABLE ruta_modulos ADD CONSTRAINT chk_ruta_modulos_avance CHECK (porcentaje_avance BETWEEN 0 AND 100);
ALTER TABLE valoraciones_modulo ADD CONSTRAINT chk_valoraciones_puntuacion CHECK (puntuacion BETWEEN 1 AND 5);

SET FOREIGN_KEY_CHECKS = 1;
