symfony 1.0 — fork de compatibilidad PHP 8.x
============================================

Este repositorio es un fork del framework **symfony 1.0** (Propel, PHP templates)
mantenido para que siga funcionando sobre **PHP 8.0 → 8.5**. Es el core que usa
ultimahora.es (y ediciones), que corre sobre symfony 1.0 legacy.

El symfony original se escribió para PHP 5; este fork va corrigiendo, versión a
versión, las incompatibilidades y deprecations que han ido apareciendo en cada
release de PHP 8 (propiedades dinámicas, paso de null a funciones internas,
constructores estilo PHP 4, utf8_encode/decode, strftime, casts no canónicos,
E_STRICT, session_set_save_handler, ReturnTypeWillChange, etc.).

La historia es **lineal y acumulativa**: cada "versión" es un punto de esa historia,
marcado con un tag, pensado como punto de rollback por si una versión de PHP da
problemas. La versión activa en producción es **1.0.31**.

Versiones (tags)
----------------

  v1.0.23   base del fork de migración PHP 8
  v1.0.24   base compatibilidad PHP 8.4 / i18n países ISO 3166-1
  v1.0.25   compatibilidad PHP 8.0 / 8.1
  v1.0.26   compatibilidad PHP 8.2
  v1.0.27   compatibilidad PHP 8.3 (sin cambios de código necesarios)
  v1.0.28   compatibilidad PHP 8.4
  v1.0.29   compatibilidad PHP 8.5
  v1.0.30   correcciones PHP 8 detectadas validando la aplicación real
  v1.0.31   secretos fuera del repo: sintaxis %env(VAR)% en los .yml (versión activa)

El linaje 1.0.21 (repo aparte) es la base pre-migración del symfony 1.0 original y
no forma parte de esta historia.

Secretos en configuración: `%env(VAR)%`
---------------------------------------

symfony 1.0 es anterior a la idea de sacar los secretos del repo: los `.yml` de
configuración se versionan, así que una `api_key` escrita ahí acaba en el
control de versiones y en cada copia del proyecto.

Desde **1.0.31** cualquier valor de un `.yml` procesado por
`sfDefineEnvironmentConfigHandler` (`app.yml`, `factories.yml`, …) admite
placeholders de entorno, junto a los `%CONSTANTE%` de toda la vida:

```yaml
all:
  mi_servicio:
    api_key: '%env(MI_SERVICIO_API_KEY)%'
```

- **Se resuelve en tiempo de ejecución, no al compilar la caché.**
  `sfDefineEnvironmentConfigHandler` emite en el fichero de caché una llamada a
  `sfToolkit::replaceEnvironmentVariables()` en lugar del valor ya sustituido,
  y ésta se ejecuta al incluirlo, en cada petición. Dos motivos: el secreto
  nunca llega a escribirse en `cache/`, y el valor no queda congelado según qué
  proceso generó la caché (la genera el primero que accede tras un
  clear-cache, y un batch de CLI no tiene el mismo entorno que php-fpm; si no,
  una regeneración desde el sitio equivocado deja sin clave al otro hasta el
  siguiente `cc`). Coste medido: ~0,5 µs por clave y petición, y sólo lo pagan
  los valores que llevan placeholder — el resto se compila como literal.
- Se consulta `getenv()` y, como respaldo, `$_SERVER` y `$_ENV`: según SAPI y
  `variables_order`, una variable definida en el pool de php-fpm puede aparecer
  sólo en `$_SERVER`.
- Si la variable no está definida, el valor queda en **cadena vacía**. Para el
  consumidor "sin definir" y "sin valor" son lo mismo, así que no necesita
  conocer esta sintaxis (dejar el placeholder intacto obligaría a cada
  consumidor a reconocerlo para no tomarlo por un valor real).
- El nombre admite `[A-Za-z_][A-Za-z0-9_]*`. Lo que no case con eso se deja tal
  cual.

**Al desplegar**: los procesos CLI (batches, tareas) heredan el entorno del
shell, pero **php-fpm no**. Para lo que se sirva por web hay que declarar la
variable en el pool y permitir que pase:

```ini
clear_env = no
env[MI_SERVICIO_API_KEY] = "..."
```

Cada servidor necesita sus propias variables; no viajan con el código.

Uso
---

Reemplazo directo del core de symfony 1.0: se usa igual que el symfony 1.0 original,
sobre un intérprete PHP 8.x. Cada quien lo integra/despliega como le convenga.

Licencia
--------

symfony es software libre. Ver el fichero LICENSE (licencia original de symfony 1.0).
Los parches de este fork mantienen la misma licencia.

Documentación original
-----------------------

see doc/03-Running-Symfony.txt
