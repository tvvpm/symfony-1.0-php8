<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 * (c) 2004-2006 Sean Kerr <sean@code-box.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfToolkit provides basic utility methods.
 *
 * @package    symfony
 * @subpackage util
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author     Sean Kerr <sean@code-box.org>
 * @version    SVN: $Id: sfToolkit.class.php 19216 2009-06-13 06:42:00Z fabien $
 */
class sfToolkit
{
  /**
   * Extract the class or interface name from filename.
   *
   * @param string A filename.
   *
   * @return string A class or interface name, if one can be extracted, otherwise null.
   */
  public static function extractClassName($filename)
  {
    $retval = null;

    if (self::isPathAbsolute($filename))
    {
      $filename = basename($filename);
    }

    $pattern = '/(.*?)\.(class|interface)\.php/i';

    if (preg_match($pattern, $filename, $match))
    {
      $retval = $match[1];
    }

    return $retval;
  }

  /**
   * Clear all files in a given directory.
   *
   * @param  string An absolute filesystem path to a directory.
   *
   * @return void
   */
  public static function clearDirectory($directory)
  {
    if (!is_dir($directory))
    {
      return;
    }

    // open a file point to the cache dir
    $fp = opendir($directory);

    // ignore names
    $ignore = array('.', '..', 'CVS', '.svn');

    while (($file = readdir($fp)) !== false)
    {
      if (!in_array($file, $ignore))
      {
        if (is_link($directory.'/'.$file))
        {
          // delete symlink
          unlink($directory.'/'.$file);
        }
        else if (is_dir($directory.'/'.$file))
        {
          // recurse through directory
          self::clearDirectory($directory.'/'.$file);

          // delete the directory
          rmdir($directory.'/'.$file);
        }
        else
        {
          // delete the file
          unlink($directory.'/'.$file);
        }
      }
    }

    // close file pointer
    closedir($fp);
  }

  /**
   * Clear all files and directories corresponding to a glob pattern.
   *
   * @param  string An absolute filesystem pattern.
   *
   * @return void
   */
  public static function clearGlob($pattern)
  {
    $files = glob($pattern);

    // order is important when removing directories
    sort($files);

    foreach ($files as $file)
    {
      if (is_dir($file))
      {
        // delete directory
        self::clearDirectory($file);
      }
      else
      {
        // delete file
        unlink($file);
      }
    }
  }

  /**
   * Determine if a filesystem path is absolute.
   *
   * @param path A filesystem path.
   *
   * @return bool true, if the path is absolute, otherwise false.
   */
  public static function isPathAbsolute($path)
  {
    if ($path[0] == '/' || $path[0] == '\\' ||
        (strlen($path) > 3 && ctype_alpha($path[0]) &&
         $path[1] == ':' &&
         ($path[2] == '\\' || $path[2] == '/')
        )
       )
    {
      return true;
    }

    return false;
  }

  /**
   * Determine if a lock file is present.
   *
   * @param integer A max amount of life time for the lock file.
   *
   * @return bool true, if the lock file is present, otherwise false.
   */
  public static function hasLockFile($lockFile, $maxLockFileLifeTime = 0)
  {
    $isLocked = false;
    if (is_readable($lockFile) && ($last_access = fileatime($lockFile)))
    {
      $now = time();
      $timeDiff = $now - $last_access;

      if (!$maxLockFileLifeTime || $timeDiff < $maxLockFileLifeTime)
      {
        $isLocked = true;
      }
      else
      {
        $isLocked = @unlink($lockFile) ? false : true;
      }
    }

    return $isLocked;
  }

  public static function stripComments($source)
  {
    if (!sfConfig::get('sf_strip_comments', true) || !function_exists('token_get_all'))
    {
      return $source;
    }

    $ignore = array(T_COMMENT => true, T_DOC_COMMENT => true);
    $output = '';

    foreach (token_get_all($source) as $token)
    {
      // array
      if (isset($token[1]))
      {
        // no action on comments
        if (!isset($ignore[$token[0]]))
        {
          // anything else -> output "as is"
          $output .= $token[1];
        }
      }
      else
      {
        // simple 1-character token
        $output .= $token;
      }
    }

    return $output;
  }

  public static function stripslashesDeep($value)
  {
    return is_array($value) ? array_map(array('sfToolkit', 'stripslashesDeep'), $value) : stripslashes($value);
  }

  // code from php at moechofe dot com (array_merge comment on php.net)
  /*
   * array arrayDeepMerge ( array array1 [, array array2 [, array ...]] )
   *
   * Like array_merge
   *
   *  arrayDeepMerge() merges the elements of one or more arrays together so
   * that the values of one are appended to the end of the previous one. It
   * returns the resulting array.
   *  If the input arrays have the same string keys, then the later value for
   * that key will overwrite the previous one. If, however, the arrays contain
   * numeric keys, the later value will not overwrite the original value, but
   * will be appended.
   *  If only one array is given and the array is numerically indexed, the keys
   * get reindexed in a continuous way.
   *
   * Different from array_merge
   *  If string keys have arrays for values, these arrays will merge recursively.
   */
  public static function arrayDeepMerge()
  {
    switch (func_num_args())
    {
      case 0:
        return false;
      case 1:
        return func_get_arg(0);
      case 2:
        $args = func_get_args();
        $args[2] = array();
        if (is_array($args[0]) && is_array($args[1]))
        {
          foreach (array_unique(array_merge(array_keys($args[0]),array_keys($args[1]))) as $key)
          {
            $isKey0 = array_key_exists($key, $args[0]);
            $isKey1 = array_key_exists($key, $args[1]);
            if ($isKey0 && $isKey1 && is_array($args[0][$key]) && is_array($args[1][$key]))
            {
              $args[2][$key] = self::arrayDeepMerge($args[0][$key], $args[1][$key]);
            }
            else if ($isKey0 && $isKey1)
            {
              $args[2][$key] = $args[1][$key];
            }
            else if (!$isKey1)
            {
              $args[2][$key] = $args[0][$key];
            }
            else if (!$isKey0)
            {
              $args[2][$key] = $args[1][$key];
            }
          }
          return $args[2];
        }
        else
        {
          return $args[1];
        }
      default :
        $args = func_get_args();
        $args[1] = sfToolkit::arrayDeepMerge($args[0], $args[1]);
        array_shift($args);
        return call_user_func_array(array('sfToolkit', 'arrayDeepMerge'), $args);
        break;
    }
  }

  public static function stringToArray($string)
  {
    if ($string === null || $string === '')
    {
      return array();
    }

    preg_match_all('/
      \s*(\w+)              # key                               \\1
      \s*=\s*               # =
      (\'|")?               # values may be included in \' or " \\2
      (.*?)                 # value                             \\3
      (?(2) \\2)            # matching \' or " if needed        \\4
      \s*(?:
        (?=\w+\s*=) | \s*$  # followed by another key= or the end of the string
      )
    /x', $string, $matches, PREG_SET_ORDER);

    $attributes = array();
    foreach ($matches as $val)
    {
      $attributes[$val[1]] = self::literalize($val[3]);
    }

    return $attributes;
  }

  /**
   * Finds the type of the passed value, returns the value as the new type.
   *
   * @param  string
   * @return mixed
   */
  public static function literalize($value, $quoted = false)
  {
    // lowercase our value for comparison
    $value  = trim($value);
    $lvalue = strtolower($value);

    if (in_array($lvalue, array('null', '~', '')))
    {
      $value = null;
    }
    else if (in_array($lvalue, array('true', 'on', '+', 'yes')))
    {
      $value = true;
    }
    else if (in_array($lvalue, array('false', 'off', '-', 'no')))
    {
      $value = false;
    }
    else if (ctype_digit($value))
    {
      $value = (int) $value;
    }
    else if (is_numeric($value))
    {
      $value = (float) $value;
    }
    else
    {
      $value = self::replaceConstants($value);
      if ($quoted)
      {
        $value = '\''.str_replace('\'', '\\\'', $value).'\'';
      }
    }

    return $value;
  }

  /**
   * Replaces constant identifiers in a scalar value.
   *
   * Los placeholders %env(NOMBRE)% NO se tocan aquí: los resuelve el fichero
   * de caché generado, en cada petición y contra el entorno de su propio
   * proceso (ver sfDefineEnvironmentConfigHandler::exportValue y
   * replaceEnvironmentVariables). Resolverlos en este punto los congelaría en
   * la caché compilada. La regex de abajo no los estropea: busca la clave
   * 'env(nombre)' en sfConfig, no la encuentra y devuelve el placeholder tal
   * cual, que es justo lo que hace falta.
   *
   * @param string the value to perform the replacement on
   * @return string the value with substitutions made
   */
  public static function replaceConstants($value)
  {
    return is_string($value) ? preg_replace_callback('/%(.+?)%/', function($v) { return sfConfig::has(strtolower($v[1])) ? sfConfig::get(strtolower($v[1])) : "%{$v[1]}%";}, $value) : $value;
  }

  /**
   * Loads a .env file into the process environment.
   *
   * Añadido en este fork (1.0.31). Lo llama sfCore::bootstrap() con la raíz
   * del proyecto, así que vale igual para web y para CLI/batch sin tener que
   * tocar el pool de php-fpm ni los perfiles de shell: un único fichero, fuera
   * del repo (añádelo al .gitignore), con las variables del proyecto.
   *
   *   # comentario
   *   ANTHROPIC_API_KEY=sk-ant-...
   *   OPENAI_API_KEY="sk-..."
   *
   * Formato: KEY=VALOR por línea; se admiten comentarios con #, líneas en
   * blanco, el prefijo `export ` y comillas simples o dobles alrededor del
   * valor. Todo lo que no case con eso se ignora en silencio (un .env roto no
   * debe tumbar la aplicación).
   *
   * NO pisa lo que ya venga del entorno real: si la variable está definida en
   * el shell o en el pool de php-fpm, gana ésa. Es la semántica habitual de
   * dotenv y permite sobreescribir por máquina sin editar el fichero.
   *
   * @param  string $file Ruta del fichero .env
   * @return bool   true si se cargó, false si no existe o no es legible
   */
  public static function loadEnvironmentFile($file)
  {
    if (!is_file($file) || !is_readable($file))
    {
      return false;
    }

    $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (false === $lines)
    {
      return false;
    }

    foreach ($lines as $line)
    {
      $line = trim($line);
      if ('' === $line || '#' === $line[0])
      {
        continue;
      }

      if (0 === strpos($line, 'export '))
      {
        $line = ltrim(substr($line, 7));
      }

      $pos = strpos($line, '=');
      if (false === $pos)
      {
        continue;
      }

      $name = rtrim(substr($line, 0, $pos));
      if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name))
      {
        continue;
      }

      // Ya definida en el entorno real: manda ésa.
      if (false !== getenv($name) || isset($_SERVER[$name]) || isset($_ENV[$name]))
      {
        continue;
      }

      $value = trim(substr($line, $pos + 1));
      $len   = strlen($value);
      if ($len > 1 && (('"' === $value[0] && '"' === $value[$len - 1]) || ("'" === $value[0] && "'" === $value[$len - 1])))
      {
        $value = substr($value, 1, -1);
      }

      putenv($name.'='.$value);
      $_ENV[$name]    = $value;
      $_SERVER[$name] = $value;
    }

    return true;
  }

  /**
   * Replaces %env(NOMBRE)% placeholders with environment variables.
   *
   * Añadido en este fork (1.0.31) para poder sacar secretos (api keys, tokens)
   * de los .yml versionados: el fichero lleva el placeholder y el valor real
   * vive en el entorno del proceso, fuera del repo.
   *
   *   api_key: '%env(ANTHROPIC_API_KEY)%'
   *
   * La llama el fichero de configuración compilado, en cada petición (ver
   * sfDefineEnvironmentConfigHandler::exportValue). Así el secreto nunca se
   * escribe en la caché y cada proceso resuelve contra su propio entorno; si
   * se resolviera al compilar, el valor quedaría congelado según qué proceso
   * generó la caché primero (un batch de CLI y php-fpm no tienen el mismo
   * entorno). El coste medido es ~0,5 us por clave y petición.
   *
   * Si la variable no está definida se sustituye por cadena vacía: para el
   * consumidor "no hay valor" y "variable sin definir" son lo mismo, y así no
   * tiene que saber nada de esta sintaxis (dejar el placeholder intacto
   * obligaría a cada consumidor a reconocerlo para no tomarlo por un valor
   * real). Es el mismo criterio con el que symfony trata una clave ausente.
   *
   * OJO con el despliegue: los procesos CLI (batch, tareas) heredan el entorno
   * del shell, pero php-fpm NO — hay que declarar la variable en el pool
   * (env[NOMBRE] = ... con clear_env = no) en cada servidor que sirva web.
   *
   * Se consulta getenv() y, como respaldo, $_SERVER/$_ENV: según SAPI y
   * variables_order, una variable puesta por el pool de php-fpm puede aparecer
   * solo en $_SERVER.
   *
   * @param string the value to perform the replacement on
   * @return string the value with substitutions made
   */
  public static function replaceEnvironmentVariables($value)
  {
    if (!is_string($value) || false === strpos($value, '%env('))
    {
      return $value;
    }

    return preg_replace_callback('/%env\(([A-Za-z_][A-Za-z0-9_]*)\)%/', function($v)
    {
      $name = $v[1];

      $env = getenv($name);
      if (false !== $env && '' !== $env)
      {
        return $env;
      }

      if (isset($_SERVER[$name]) && '' !== $_SERVER[$name])
      {
        return $_SERVER[$name];
      }

      if (isset($_ENV[$name]) && '' !== $_ENV[$name])
      {
        return $_ENV[$name];
      }

      // No definida: equivale a no tener valor.
      return '';
    }, $value);
  }

  /**
   * Returns subject replaced with regular expression matchs
   *
   * @param mixed subject to search
   * @param array array of search => replace pairs
   */
  public static function pregtr($search, $replacePairs)
  {
    return preg_replace(array_keys($replacePairs), array_values($replacePairs), $search);
  }

  public static function isArrayValuesEmpty($array)
  {
    static $isEmpty = true;
    foreach ($array as $value)
    {
      $isEmpty = (is_array($value)) ? self::isArrayValuesEmpty($value) : (strlen($value) == 0);
      if (!$isEmpty)
      {
        break;
      }
    }

    return $isEmpty;
  }

  /**
   * Checks if a string is an utf8.
   *
   * Yi Stone Li<yili@yahoo-inc.com>
   * Copyright (c) 2007 Yahoo! Inc. All rights reserved.
   * Licensed under the BSD open source license
   *
   * @param string
   *
   * @return bool true if $string is valid UTF-8 and false otherwise.
   */
  public static function isUTF8($string)
  {
    for ($idx = 0, $strlen = strlen($string); $idx < $strlen; $idx++)
    {
      $byte = ord($string[$idx]);

      if ($byte & 0x80)
      {
        if (($byte & 0xE0) == 0xC0)
        {
          // 2 byte char
          $bytes_remaining = 1;
        }
        else if (($byte & 0xF0) == 0xE0)
        {
          // 3 byte char
          $bytes_remaining = 2;
        }
        else if (($byte & 0xF8) == 0xF0)
        {
          // 4 byte char
          $bytes_remaining = 3;
        }
        else
        {
          return false;
        }

        if ($idx + $bytes_remaining >= $strlen)
        {
          return false;
        }

        while ($bytes_remaining--)
        {
          if ((ord($string[++$idx]) & 0xC0) != 0x80)
          {
            return false;
          }
        }
      }
    }

    return true;
  }

  public static function &getArrayValueForPathByRef(&$values, $name, $default = null)
  {
    if (false !== ($offset = strpos($name, '[')))
    {
      if (isset($values[substr($name, 0, $offset)]))
      {
        $array = &$values[substr($name, 0, $offset)];

        while ($pos = strpos($name, '[', $offset))
        {
          $end = strpos($name, ']', $pos);
          if ($end == $pos + 1)
          {
            // reached a []
            break;
          }
          else if (!isset($array[substr($name, $pos + 1, $end - $pos - 1)]))
          {
            return $default;
          }
          else if (is_array($array))
          {
            $array = &$array[substr($name, $pos + 1, $end - $pos - 1)];
            $offset = $end;
          }
          else
          {
            return $default;
          }
        }

        return $array;
      }
    }

    return $default;
  }

  public static function getArrayValueForPath($values, $name, $default = null)
  {
    if (false !== ($offset = strpos($name, '[')))
    {
      if (isset($values[substr($name, 0, $offset)]))
      {
        $array = $values[substr($name, 0, $offset)];

        while ($pos = strpos($name, '[', $offset))
        {
          $end = strpos($name, ']', $pos);
          if ($end == $pos + 1)
          {
            // reached a []
            break;
          }
          else if (!isset($array[substr($name, $pos + 1, $end - $pos - 1)]))
          {
            return $default;
          }
          else if (is_array($array))
          {
            $array = $array[substr($name, $pos + 1, $end - $pos - 1)];
            $offset = $end;
          }
          else
          {
            return $default;
          }
        }

        return $array;
      }
    }

    return $default;
  }

  public static function getPhpCli()
  {
    $path = getenv('PATH') ? getenv('PATH') : getenv('Path');
    $suffixes = DIRECTORY_SEPARATOR == '\\' ? (getenv('PATHEXT') ? explode(PATH_SEPARATOR, getenv('PATHEXT')) : array('.exe', '.bat', '.cmd', '.com')) : array('');
    foreach (array('php5', 'php') as $phpCli)
    {
      foreach ($suffixes as $suffix)
      {
        foreach (explode(PATH_SEPARATOR, $path) as $dir)
        {
          $file = $dir.DIRECTORY_SEPARATOR.$phpCli.$suffix;
          if (is_executable($file))
          {
            return $file;
          }
        }
      }
    }

    throw new sfException('Unable to find PHP executable');
  }

  /**
   * From PEAR System.php
   *
   * LICENSE: This source file is subject to version 3.0 of the PHP license
   * that is available through the world-wide-web at the following URI:
   * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
   * the PHP License and are unable to obtain it through the web, please
   * send a note to license@php.net so we can mail you a copy immediately.
   *
   * @author     Tomas V.V.Cox <cox@idecnet.com>
   * @copyright  1997-2006 The PHP Group
   * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
   */
  public static function getTmpDir()
  {
    if (DIRECTORY_SEPARATOR == '\\')
    {
      if ($var = isset($_ENV['TEMP']) ? $_ENV['TEMP'] : getenv('TEMP'))
      {
        return $var;
      }
      if ($var = isset($_ENV['TMP']) ? $_ENV['TMP'] : getenv('TMP'))
      {
        return $var;
      }
      if ($var = isset($_ENV['windir']) ? $_ENV['windir'] : getenv('windir'))
      {
        return $var;
      }

      return getenv('SystemRoot').'\temp';
    }

    if ($var = isset($_ENV['TMPDIR']) ? $_ENV['TMPDIR'] : getenv('TMPDIR'))
    {
      return $var;
    }

    return '/tmp';
  }
}
