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

  /**
   * symfony (sfPHPView::render) serializa el pager entero dentro de la cache de
   * plantilla. En listados grandes $items son megas de objetos Propel hidratados
   * que nadie vuelve a leer tras un hit de cache: los partials de paginación solo
   * usan page/lastPage/nbResults/getLinks(). Lo excluimos de la serialización;
   * nbResults ya guarda el total, así que la paginación se reconstruye igual.
   * getResults() devolvería [] sobre un pager deserializado, pero para entonces
   * ya se ha consumido antes de cachear.
   */
  public function __sleep()
  {
    return array_values(array_diff(array_keys(get_object_vars($this)), array('items')));
  }
}
