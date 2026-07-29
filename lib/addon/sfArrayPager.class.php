<?php

/**
 * Pager sobre un array ya en memoria (p.ej. el resultado de una consulta que
 * se cachea entero aparte) -- no toca la BD. Toda la lógica de paginación
 * (getLinks, haveToPaginate, getLastPage...) la hereda tal cual de sfPager,
 * igual que sfPropelPager para Criteria/Propel.
 *
 * @package    symfony
 * @subpackage addon
 */
class sfArrayPager extends sfPager
{
  protected $items = array();

  public function setItems(array $items)
  {
    $this->items = array_values($items);
    $this->setNbResults(count($this->items));
  }

  public function init()
  {
    $this->setLastPage(max(1, (int) ceil($this->getNbResults() / $this->getMaxPerPage())));
  }

  public function getResults()
  {
    $offset = ($this->getPage() - 1) * $this->getMaxPerPage();

    return array_slice($this->items, max(0, $offset), $this->getMaxPerPage());
  }

  protected function retrieveObject($offset)
  {
    return isset($this->items[$offset - 1]) ? $this->items[$offset - 1] : null;
  }
}
