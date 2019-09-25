<?php

namespace Andig\FritzAdr;

/**
 * This class provides a functionality to extract fax numbers
 * and provide them in a simple array with 19 or 21 fields
 * - to pass them to FritzAdr compliant dBASE file (fritzadr.dbf).
 * The DB analysis of several FritzAdr.dbf files has surprisingly
 * shown both variants. Ultimately, the 21er works for me.
 *
 * Copyright (c) 2019 Volker Püschel
 * @license MIT
 */

use blacksenator\fritzdbf\fritzdbf;
use \SimpleXMLElement;

class fritzadr
{
    /**
     * delivers an structured adress array of fax numbers from a designated phone book
     *
     * @param SimpleXMLElement $xmlPhonebook phonebook in FRITZ!Box format
     * @return array fax numbers, names
     */
    public function getFAXcontacts(SimpleXMLElement $xmlPhonebook) : array
    {
        $i = -1;
        $adrRecords = [];
        foreach ($xmlPhonebook->phonebook->contact as $contact) {
            foreach ($contact->telephony->number as $number) {
                if ((string)$number['type'] == "fax_work") {
                    $i++;
                    $name = $contact->person->realName;
                    $faxnumber = (string)$number;
                    // dBase uses the DOS charset (Codepage 850); htmlspecialchars makes a '&amp;' from '&' must be reset here
                    $name = str_replace('&amp;', '&', iconv('UTF-8', 'CP850//TRANSLIT', $name));
                    $adrRecords[$i]['BEZCHNG'] = $name;            // fullName in field 1
                    $parts = explode(', ', $name);
                    if (count($parts) !== 2) {                     // if the name was not separated by a comma (no first and last name)
                        $adrRecords[$i]['FIRMA'] = $name;          // fullName in field 2
                    } else {
                        $adrRecords[$i]['NAME']    = $parts[0];    // lastname in field 3
                        $adrRecords[$i]['VORNAME'] = $parts[1];    // firstnme in field 4
                    }
                    $adrRecords[$i]['TELEFAX'] = $faxnumber;       // FAX number in field 10/11
                }
            }
        }

        return $adrRecords;
    }

    /**
     * get contact data array in dBase III format
     *
     * @param array $contacts
     * @return string
     */
    public function getdBaseData(array $contacts)
    {
        $fritzAdr = new fritzdbf();
        foreach ($contacts as $contact) {
            $fritzAdr->addRecord($contact);
        }

        return $fritzAdr->getDatabase();
    }
}
