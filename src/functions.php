<?php

namespace Andig;

use Andig\CardDav\Backend;
use Andig\Vcard\Parser;
use Andig\FritzBox\Converter;
use Andig\FritzBox\Api;
use \SimpleXMLElement;

function download($url, $user, $password, callable $callback=null): string
{
    $backend = new Backend($url);
    $backend->setAuth($user, $password);
    $backend->setProgress($callback);
    return $backend->get();
}

function countCards($xml): int
{
    $count = 0;
    $xml = simplexml_load_string($xml);

    foreach ($xml->element as $element) {
        foreach ($element->vcard as $vcard) {
            $count++;
        }
    }
    return $count;
}

function parse(SimpleXMLElement $xml, array $conversions): array
{
    $cards = [];
    $groups = [];

    // parse all vcards
    foreach ($xml->element as $element) {
        foreach ($element->vcard as $vcard) {
            $parser = new Parser($vcard);
            $card = $parser->getCardAtIndex(0);

            // separate iCloud groups
            if (isset($card->xabsmember)) {
                $groups[$card->fullname] = $card->xabsmember;
                continue;
            }

            $cards[] = $card;
            // print_r($card);
        }
    }

    // assign group memberships
    foreach ($cards as $card) {
        foreach ($groups as $group => $members) {
            if (in_array($card->uid, $members)) {
                if (!isset($card->group)) {
                    $card->group = array();
                }

                $card->group = $group;
                break;
            }
        }
    }

    return $cards;
}

function filter(array $cards, array $filters): array
{
    $result = [];

    foreach ($cards as $card) {
        if (filtersMatch($card, $filters)) {
            continue;
        }

        $result[] = $card;
    }

    return $result;
}

function filtersMatch(object $card, array $filters): bool
{
    foreach ($filters as $attribute => $values) {
        if (isset($card->$attribute)) {
            if (filterMatches($card->$attribute, $values)) {
                return true;
            }
        }
    }

    return false;
}

function filterMatches($attribute, $filterValues): bool
{
    if (!is_array($filterValues)) {
        $filterValues = array($filterMatches);
    }

    foreach ($filterValues as $filter) {
        if ($attribute === $filter) {
            return true;
        }
    }

    return false;
}

function export(string $name, array $cards, array $conversions): SimpleXMLElement
{
    $xml = new SimpleXMLElement(
        <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<phonebooks>
<phonebook />
</phonebooks>
EOT
    );

    $root = $xml->xpath('//phonebook')[0];
    $root->addAttribute('name', $name);

    $converter = new Converter($conversions);

    foreach ($cards as $card) {
        $contact = $converter->convert($card);
        // $root->addChild('contact', $contact);
        xml_adopt($root, $contact);
    }

    return $xml;
}

// https://stackoverflow.com/questions/4778865/php-simplexml-addchild-with-another-simplexmlelement
function xml_adopt(SimpleXMLElement $to, SimpleXMLElement $from)
{
    $toDom = dom_import_simplexml($to);
    $fromDom = dom_import_simplexml($from);
    $toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
}


function upload(string $xml, string $url, string $user, string $password, int $phonebook=0)
{
    $fritz = new Api($url, $user, $password, 1);

    $formfields = array(
        'PhonebookId' => $phonebook
    );

    $filefields = array(
        'PhonebookImportFile' => array(
            'type' => 'text/xml',
            'filename' => 'updatepb.xml',
            'content' => $xml,
        )
    );

    $result = $fritz->doPostFile($formfields, $filefields); // send the command

    if (strpos($result, 'Das Telefonbuch der FRITZ!Box wurde wiederhergestellt') === false) {
        throw new \Exception('Upload failed');
    }
}
