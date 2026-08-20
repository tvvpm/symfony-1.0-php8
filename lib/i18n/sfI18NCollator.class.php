<?php

/**
 * Comparación/ordenación de texto locale-aware (usa las tablas de colación
 * ICU vía ext-intl, no setlocale()+strcmp/strcoll -- no depende de locales
 * generados en el SO ni deja estado global de proceso). Por defecto usa la
 * cultura de la app (i18n.yml -> sf_i18n_default_culture) salvo que se
 * indique otra explícitamente.
 *
 * @package    symfony
 * @subpackage i18n
 */
class sfI18NCollator
{
  static protected $instancias = array();

  static protected function get($culture = null)
  {
    if ($culture === null)
      $culture = sfConfig::get('sf_i18n_default_culture', 'es_ES');

    if (!isset(self::$instancias[$culture]))
      self::$instancias[$culture] = new Collator($culture);

    return self::$instancias[$culture];
  }

  static public function compare($a, $b, $culture = null)
  {
    return self::get($culture)->compare($a, $b);
  }

  static public function sort(array &$items, $culture = null)
  {
    return self::get($culture)->sort($items);
  }
}
