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
problemas. La versión activa en producción es **1.0.30**.

Versiones (tags)
----------------

  v1.0.23   base del fork de migración PHP 8
  v1.0.24   base compatibilidad PHP 8.4 / i18n países ISO 3166-1
  v1.0.25   compatibilidad PHP 8.0 / 8.1
  v1.0.26   compatibilidad PHP 8.2
  v1.0.27   compatibilidad PHP 8.3 (sin cambios de código necesarios)
  v1.0.28   compatibilidad PHP 8.4
  v1.0.29   compatibilidad PHP 8.5
  v1.0.30   correcciones PHP 8 detectadas validando la aplicación real (versión activa)

El linaje 1.0.21 (repo aparte) es la base pre-migración del symfony 1.0 original y
no forma parte de esta historia.

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
