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
- El valor se busca en el entorno del proceso; la forma recomendada de
  poblarlo es el fichero `.env` del proyecto (ver más abajo).
- Si la variable no está definida, el valor queda en **cadena vacía**. Para el
  consumidor "sin definir" y "sin valor" son lo mismo, así que no necesita
  conocer esta sintaxis (dejar el placeholder intacto obligaría a cada
  consumidor a reconocerlo para no tomarlo por un valor real).
- El nombre admite `[A-Za-z_][A-Za-z0-9_]*`. Lo que no case con eso se deja tal
  cual.

### El fichero `.env` del proyecto

Las variables se pueden dejar en un `.env` en la raíz del proyecto
(`SF_ROOT_DIR`), con el formato habitual:

```
# comentario
MI_SERVICIO_API_KEY=valor
OTRA="valor entre comillas"
export TAMBIEN_VALE=1
```

Lo carga `sfCore::bootstrap()`, que es el punto por el que pasan **todos** los
arranques —front controllers web, batches y la CLI `symfony`— y ocurre antes de
leer o generar ninguna caché de configuración. Así que la misma definición vale
para web y para CLI: **no hace falta declarar nada en el pool de php-fpm**
(`clear_env` / `env[...]`), que era la alternativa antes de esto.

#### Cascada por app

Además del `.env` de la raíz (variables comunes a todas las apps), cada app
puede tener el suyo propio en `apps/<app>/.env`, con lo que solo necesita ella.
Mismo criterio que la cascada de `app.yml`: lo específico se añade encima de lo
común y puede sobreescribirlo.

`sfCore::bootstrap()` carga primero el `.env` de la raíz y luego, si `SF_APP`
está definida (lo está en cualquier arranque normal: se define antes de
requerir el `config.php` de la app), el de `apps/<app>/.env` — sus valores
ganan si coinciden con los de la raíz. Ninguno de los dos pisa lo que ya
viniera del entorno real del proceso, que sigue mandando por encima de
cualquier `.env`.

Internamente esto usa `sfToolkit::loadEnvironmentFiles(array $files)`, que
fusiona los ficheros (el último de la lista gana) antes de aplicar el
resultado al entorno. Llamar dos veces a `loadEnvironmentFile()` (singular)
para el mismo efecto **no funciona**: la segunda llamada vería lo que puso la
primera como si fuera entorno real y nunca lo sobreescribiría.

- Las variables **ya presentes en el entorno no se pisan**: lo que venga del
  shell, del pool o del systemd unit tiene prioridad sobre cualquier `.env`.
- **Comillas dobles** interpretan los escapes `\n`, `\r`, `\t`, `\"` y `\\`, lo
  que permite meter en una línea un valor con saltos (una clave PEM, por
  ejemplo). **Comillas simples** y sin comillas son literales. Ojo al migrar un
  valor que ya estaba en un `.yml`: el parser YAML de symfony 1.0 **no**
  interpreta escapes ni con comillas dobles, así que un `"...\n..."` de un
  `.yml` vale una barra y una ene, y para reproducirlo tal cual hay que usar
  comillas simples en el `.env`.
- Si el fichero no existe o no se puede leer, no pasa nada: simplemente no se
  carga (los proyectos que no lo usen no notan el cambio).
- Se escribe en `putenv()`, `$_ENV` y `$_SERVER`.

**Al desplegar**: el `.env` no se versiona ni viaja con el código —cada máquina
tiene el suyo, y hay que provisionarlo aparte. Conviene excluirlo del control de
versiones y de la sincronización de ficheros, y darle permisos que permitan
leerlo al usuario que corre php-fpm además del de CLI.

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
