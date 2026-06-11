<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Puente entre los storages de sesion de symfony y SessionHandlerInterface.
 *
 * La firma de session_set_save_handler() con 6 callbacks esta deprecada en
 * PHP 8.4. Los storages no pueden implementar SessionHandlerInterface
 * directamente porque sus metodos read()/write() (API de atributos de
 * sfStorage) chocan con los de la interfaz, asi que este puente delega en
 * los metodos session* historicos.
 *
 * @package    symfony
 * @subpackage storage
 */
class sfSessionHandlerBridge implements SessionHandlerInterface
{
  protected $storage;

  public function __construct($storage)
  {
    $this->storage = $storage;
  }

  #[\ReturnTypeWillChange]
  public function open($path, $name)
  {
    return $this->storage->sessionOpen($path, $name);
  }

  #[\ReturnTypeWillChange]
  public function close()
  {
    return $this->storage->sessionClose();
  }

  #[\ReturnTypeWillChange]
  public function read($id)
  {
    return $this->storage->sessionRead($id);
  }

  #[\ReturnTypeWillChange]
  public function write($id, $data)
  {
    return $this->storage->sessionWrite($id, $data);
  }

  #[\ReturnTypeWillChange]
  public function destroy($id)
  {
    return $this->storage->sessionDestroy($id);
  }

  #[\ReturnTypeWillChange]
  public function gc($lifetime)
  {
    return $this->storage->sessionGC($lifetime);
  }
}
