<?php
/**
 * Extends the DOMDocument to implement personal (utility) methods.
 *
 * @author Toni Van de Voorde
 */
class XmlDomConstruct extends DOMDocument {
	public function fromMixed($mixed, DOMElement $domElement = null) {

		$domElement = is_null($domElement) ? $this : $domElement;

		if (is_array($mixed)) {
			foreach( $mixed as $index => $mixedElement ) {

				if ( is_int($index) ) {
					if ( $index == 0 ) {
						$node = $domElement;
					} else {
						$node = $this->createElement($domElement->tagName);
						$domElement->parentNode->appendChild($node);
					}
				} 

				else {
					$node = $this->createElement($index);
					$domElement->appendChild($node);
				}

				$this->fromMixed($mixedElement, $node);

			}
		} else {
			$domElement->appendChild($this->createTextNode($mixed));
		}

	}

}