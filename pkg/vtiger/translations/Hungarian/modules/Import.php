<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
$languageStrings = array(
	'LBL_IMPORT_STEP_1'            => '1. lépés'                  , 
	'LBL_IMPORT_STEP_1_DESCRIPTION' => 'Válaszd ki a fájlt'        , 
	'LBL_IMPORT_SUPPORTED_FILE_TYPES' => 'Támogatott fájltípusok: .CSV, .VCF', 
	'LBL_IMPORT_STEP_2'            => '2. lépés'                  , 
	'LBL_IMPORT_STEP_2_DESCRIPTION' => 'Határozd meg a formátumot' , 
	'LBL_FILE_TYPE'                => 'Fájl típus'                , 
	'LBL_CHARACTER_ENCODING'       => 'Karakter kódolás'          , 
	'LBL_DELIMITER'                => 'Mezőelválasztó:'          , 
	'LBL_HAS_HEADER'               => 'Van fejléc'                 , 
	'LBL_IMPORT_STEP_3'            => '3. lépés'                  , 
	'LBL_IMPORT_STEP_3_DESCRIPTION' => 'Rekord duplikáció kezelése', 
	'LBL_IMPORT_STEP_3_DESCRIPTION_DETAILED' => 'Engedélyezd ezt a lehetőséget és add meg a duplikáció szűrés feltételeit', 
	'LBL_SPECIFY_MERGE_TYPE'       => 'Válaszd ki, hogyan kezeljük a duplikált rekordokat', 
	'LBL_SELECT_MERGE_FIELDS'      => 'Válaszd ki azokat a mezőket, amiknek egyezniük kell, hogy a rekord duplikáltnak számítson', 
	'LBL_AVAILABLE_FIELDS'         => 'Elérhető mezők'           , 
	'LBL_SELECTED_FIELDS'          => 'Mezők, amiknek egyezniük kell', 
	'LBL_NEXT_BUTTON_LABEL'        => 'Következő'                 , 
	'LBL_IMPORT_STEP_4'            => '4. lépés'                  , 
	'LBL_IMPORT_STEP_4_DESCRIPTION' => 'Rendeld össze az oszlopokat a Modul mezőkkel', 
	'LBL_FILE_COLUMN_HEADER'       => 'Fejléc'                     , 
	'LBL_ROW_1'                    => '1. sor'                      , 
	'LBL_CRM_FIELDS'               => 'CRM mezők'                  , 
	'LBL_DEFAULT_VALUE'            => 'Alapértelmezett érték'    , 
	'LBL_SAVE_AS_CUSTOM_MAPPING'   => 'Mentse el, mint egyedi hozzárendelést', 
	'LBL_IMPORT_BUTTON_LABEL'      => 'Importálás'                , 
	'LBL_RESULT'                   => 'Eredmény'                   , 
	'LBL_TOTAL_RECORDS_IMPORTED'   => 'Importált rekordok száma összesen ', 
	'LBL_NUMBER_OF_RECORDS_CREATED' => 'Létrehozott rekordok száma összesen ', 
	'LBL_NUMBER_OF_RECORDS_UPDATED' => 'Módosított rekordok száma összesen ', 
	'LBL_NUMBER_OF_RECORDS_SKIPPED' => 'A feldolgozás során átlépett rekordok száma összesen ', 
	'LBL_NUMBER_OF_RECORDS_MERGED' => 'Összefűzött rekordok száma összesen ', 
	'LBL_TOTAL_RECORDS_FAILED'     => 'Hibásnak bizonyult rekordok száma összesen ', 
	'LBL_IMPORT_MORE'              => 'További importálás'       , 
	'LBL_VIEW_LAST_IMPORTED_RECORDS' => 'Utoljára importált rekordok', 
	'LBL_UNDO_LAST_IMPORT'         => 'Utolsó importálás visszavonása', 
	'LBL_FINISH_BUTTON_LABEL'      => 'Befejezés'                  , 
	'LBL_UNDO_RESULT'              => 'Utolsó import visszacsinálása', 
	'LBL_TOTAL_RECORDS'            => 'Rekordok száma összesen '  , 
	'LBL_NUMBER_OF_RECORDS_DELETED' => 'Törölt rekordok száma összesen ', 
	'LBL_OK_BUTTON_LABEL'          => 'OK'                          , 
	'LBL_IMPORT_SCHEDULED'         => 'Időzített importálás'    , 
	'LBL_RUNNING'                  => 'Működik'                   , 
	'LBL_CANCEL_IMPORT'            => 'Importálás visszavonása'  , 
	'LBL_ERROR'                    => 'Hiba:'                       , 
	'LBL_CLEAR_DATA'               => 'Adatok törlése'            , 
	'ERR_UNIMPORTED_RECORDS_EXIST' => 'Még vannak a várósorodban fel nem dolgozott rekordok, amelyek megakadályozzák, hogy további adatokat importálhass.<br>Töröld az adatokat a takarításhoz, és egy új importálást kezdeményezz.', 
	'ERR_IMPORT_INTERRUPTED'       => 'Ez az importálás megszakadt. Próbáld meg később.', 
	'ERR_FAILED_TO_LOCK_MODULE'    => 'Nem sikerült lezárni a modult az importáláshoz. Próbáld meg később újra.', 
	'LBL_SELECT_SAVED_MAPPING'     => 'Válassz a mentett egyedi hozzárendelésekből', 
	'LBL_IMPORT_ERROR_LARGE_FILE'  => 'Import Error Large file '    , // TODO: Review
	'LBL_FILE_UPLOAD_FAILED'       => 'File Upload Failed'          , // TODO: Review
	'LBL_IMPORT_CHANGE_UPLOAD_SIZE' => 'Import Change Upload Size'   , // TODO: Review
	'LBL_IMPORT_DIRECTORY_NOT_WRITABLE' => 'Import Directory is not writable', // TODO: Review
	'LBL_IMPORT_FILE_COPY_FAILED'  => 'Import File copy failed'     , // TODO: Review
	'LBL_INVALID_FILE'             => 'Invalid File'                , // TODO: Review
	'LBL_NO_ROWS_FOUND'            => 'No rows found'               , // TODO: Review
	'LBL_SCHEDULED_IMPORT_DETAILS' => 'Your import has been scheduled and will start within 15 minutes. You will receive an email after import is completed.  <br> <br>
										Please make sure that the Outgoing server and your email address is configured to receive email notification', // TODO: Review
	'LBL_DETAILS'                  => 'Details'                     , // TODO: Review
	'skipped'                      => 'Skipped Records'             , // TODO: Review
	'failed'                       => 'Failed Records'              , // TODO: Review
    
        'LBL_IMPORT_LINEITEMS_CURRENCY'=> 'Árfolyam (Sorok)',

	'LBL_SKIP_THIS_STEP' => 'Ugorja át ezt a lépést',
	'LBL_UPLOAD_ICS' => 'Feltöltés ICS fájl',
	'LBL_ICS_FILE' => 'ICS fájl',
	'LBL_IMPORT_FROM_ICS_FILE' => 'Importálás ICS fájl',
	'LBL_SELECT_ICS_FILE' => 'Válassza ki ICS fájl',

  'LBL_USE_SAVED_MAPS' => 'Használja Mentett Térképek',
  'LBL_IMPORT_MAP_FIELDS' => 'A térképen a coloumns, hogy a CRM mezők',
  'LBL_UPLOAD_CSV' => 'CSV-Fájl feltöltés',
  'LBL_UPLOAD_VCF' => 'Feltöltés VCF Fájlt',
  'LBL_DUPLICATE_HANDLING' => 'Ismétlődő Kezelése',
  'LBL_FIELD_MAPPING' => 'Mező Feltérképezése',
  'LBL_IMPORT_FROM_CSV_FILE' => 'Import CSV-fájl',
  'LBL_SELECT_IMPORT_FILE_FORMAT' => 'Hová szeretne importálni ?',
  'LBL_CSV_FILE' => 'CSV-Fájl',
  'LBL_VCF_FILE' => 'VCF Fájlt',
  'LBL_GOOGLE' => 'A Google',
  'LBL_IMPORT_COMPLETED' => 'Az Importálás Befejeződött',
  'LBL_IMPORT_SUMMARY' => 'Import összefoglaló',
  'LBL_DELETION_COMPLETED' => 'Törlés Befejeződött',
  'LBL_TOTAL_RECORDS_SCANNED' => 'Összes beolvasott rekordok',
  'LBL_SKIP_BUTTON' => 'Ugrás',
  'LBL_DUPLICATE_RECORD_HANDLING' => 'Duplikált bejegyzést kezelése',
  'LBL_IMPORT_FROM_VCF_FILE' => 'Az importálás VCF fájlt',
  'LBL_SELECT_VCF_FILE' => 'Válasszuk a VCF fájlt',
  'LBL_DONE_BUTTON' => 'Kész',
  'LBL_DELETION_SUMMARY' => 'Törlés összefoglaló',

);
