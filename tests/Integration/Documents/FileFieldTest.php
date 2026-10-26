<?php

declare(strict_types=1);

namespace Tests\Integration\Documents;

use Documents_FieldFile;
use Settings_LayoutEditor_Field_Model;
use Settings_LayoutEditor_Module_Model;
use Tests\Support\DocumentsTestCase;
use Users_Record_Model;
use Vtiger_Field_Model;
use Vtiger_FileField_Api;
use Vtiger_Module_Model;
use Vtiger_Record_Model;
use Vtiger_RecordStructure_Model;
use Vtiger_Viewer;

require_once dirname(__DIR__, 3) . '/tests/Support/DocumentsTestCase.php';

/**
 * ファイルアップロード項目（uitype 998）
 *
 *   - レイアウトエディタで項目を追加でき、手書き入力の指定を保存できる
 *   - ファイルをドキュメントとして登録する（手書き項目は PNG だけ）
 *   - アップロードは一時ファイルに置くだけにし、レコードの保存時にドキュメントにする
 *     （項目に既存のドキュメントがあれば版を足す。手書きの自動保存では版を増やさない）
 *   - レコードの保存でドキュメントを関連付け、参照できないドキュメントは入れさせない
 *   - 表示（詳細・レポート）はファイル名を出す
 *   - 一覧の列検索・フィルターの条件には出さない（値はドキュメントIDのため検索できない）
 *   - サムネイル API は、ドキュメントかレコードのどちらかを参照できれば返す
 *
 * テスト用の項目は顧客企業に作り、クラスの最後に削除する。
 */
final class FileFieldTest extends DocumentsTestCase
{
    /** 一般ユーザー（e2e_rep_a）。テスト用DB（E2E の dump）に含まれる */
    private const REP_USER_ID = 7;

    /** 作成した項目（fieldname => fieldid） */
    private static array $fields = [];

    /** テストで作った一時ファイル */
    private array $tempFiles = [];

    /** テストでアップロードした一時ファイル（項目の値） */
    private array $drafts = [];

    /** テストで作った PDF テンプレート */
    private array $pdfTemplates = [];

    protected function setUp(): void
    {
        parent::setUp();
        require_once 'modules/Documents/utils/FieldFile.php';
        require_once 'modules/Documents/utils/FieldFileDraft.php';
        require_once 'modules/Documents/utils/FolderPermission.php';
        require_once 'modules/Reports/ReportUtils.php';
        require_once 'include/InventoryPDFController.php';
        require_once 'modules/Vtiger/apis/FileField.php';
        require_once 'includes/runtime/Viewer.php';
        $this->cleanAccounts();
        $this->cleanAttachments();
        if (empty(self::$fields)) {
            self::$fields['file'] = $this->addFileField('SPECTEST_ファイル', false);
            self::$fields['handwriting'] = $this->addFileField('SPECTEST_手書き', true);
        }
        Documents_FieldFile::clearCache();
    }

    protected function tearDown(): void
    {
        $this->loginAs(self::ADMIN_USER_ID);
        $this->cleanAccounts();
        $this->cleanAttachments();
        $this->cleanFolders();
        foreach ($this->tempFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        $this->tempFiles = [];
        foreach ($this->drafts as $draft) {
            \Documents_FieldFileDraft::delete($draft);
        }
        $this->drafts = [];
        foreach ($this->pdfTemplates as $templateId) {
            $this->db->pquery('DELETE FROM vtiger_pdftemplates WHERE templateid = ?', [$templateId]);
        }
        $this->pdfTemplates = [];
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        foreach (self::$fields as $field) {
            $fieldModel = Settings_LayoutEditor_Field_Model::getInstance($field['fieldid']);
            if ($fieldModel) {
                $fieldModel->delete();
            }
        }
        self::$fields = [];
        parent::tearDownAfterClass();
    }

    // ---- レイアウトエディタ -----------------------------------------------

    public function test_レイアウトエディタでファイルアップロード項目を追加できる(): void
    {
        $file = self::$fields['file'];
        $row = $this->fieldRow($file['fieldid']);

        $this->assertSame('998', (string) $row['uitype']);
        $this->assertSame('I~O', $row['typeofdata']);
        $this->assertSame(0, (int) $row['handwriting']);
        $this->assertSame(
            1,
            (int) $this->fieldRow(self::$fields['handwriting']['fieldid'])['handwriting'],
            '手書き入力の指定を保存する'
        );

        $fieldModel = Vtiger_Field_Model::getInstance($file['fieldid']);
        $this->assertSame('fileupload', $fieldModel->getFieldDataType());
        $this->assertInstanceOf(\Vtiger_Fileupload_UIType::class, $fieldModel->getUITypeModel());

        $columnType = $this->columnType('vtiger_accountscf', $file['fieldname']);
        $this->assertStringStartsWith('int', $columnType, '値（ドキュメントID）は整数の列に持つ');
    }

    public function test_レイアウトエディタの型一覧にファイルアップロードがあり手書き入力を指定できる(): void
    {
        $moduleModel = Settings_LayoutEditor_Module_Model::getInstanceByName('Accounts');
        $this->assertContains('FileUpload', $moduleModel->getAddSupportedFieldTypes());
        $typeInfo = $moduleModel->getAddFieldTypeInfo();
        $this->assertTrue($typeInfo['FileUpload']['handwritingsupported']);

        $details = $moduleModel->getTypeDetailsForAddField('FileUpload', []);
        $this->assertSame(['uitype' => 998, 'typeofdata' => 'I~O', 'dbType' => 'INT(19)'], $details);
    }

    public function test_項目の編集で手書き入力の指定を切り替えられる(): void
    {
        $fieldId = self::$fields['file']['fieldid'];
        $fieldModel = Settings_LayoutEditor_Field_Model::getInstance($fieldId);
        $this->assertFalse($fieldModel->isHandwriting());
        $this->assertSame('FileUpload', $fieldModel->getFieldDataTypeLabel());
        $this->assertTrue($fieldModel->isDefaultValueOptionDisabled(), 'ファイルに既定値は無い');
        $this->assertTrue($fieldModel->isMassEditOptionDisabled(), '一括編集ではファイルを扱わない');

        try {
            Documents_FieldFile::setHandwriting($fieldId, true);
            $this->assertTrue(Documents_FieldFile::isHandwritingField($fieldId));
            $this->assertTrue(Documents_FieldFile::getFileField('Accounts', self::$fields['file']['fieldname'])['handwriting']);
        } finally {
            Documents_FieldFile::setHandwriting($fieldId, false);
        }
        $this->assertFalse(Documents_FieldFile::isHandwritingField($fieldId));
    }

    public function test_ファイルアップロード項目以外は手書き入力にできない(): void
    {
        $result = $this->db->pquery(
            "SELECT fieldid FROM vtiger_field WHERE tabid = ? AND fieldname = 'accountname'",
            [getTabid('Accounts')]
        );
        $fieldId = (int) $this->db->query_result($result, 0, 'fieldid');

        Documents_FieldFile::setHandwriting($fieldId, true);

        $this->assertSame(0, (int) $this->fieldRow($fieldId)['handwriting']);
    }

    // ---- ドキュメントの登録 -------------------------------------------------

    public function test_ファイルからドキュメントを作り他の入力欄のファイルを巻き込まない(): void
    {
        $other = $this->tempFile('txt', 'other');
        $_FILES = ['other' => $this->fileEntry($other, 'other.txt', 'text/plain')];
        $png = $this->tempFile('png', $this->pngBinary(40, 20));

        $notesId = Documents_FieldFile::createDocument(
            $this->fileEntry($png, self::PREFIX . 'image.png', 'image/png'),
            $this->fieldInfo('file')
        );

        $this->assertGreaterThan(0, $notesId);
        $this->assertSame(self::PREFIX . 'image', $this->notesColumn($notesId, 'title'));
        $this->assertSame('I', $this->notesColumn($notesId, 'filelocationtype'));
        $this->assertSame(Documents_FieldFile::DEFAULT_FOLDER_ID, (int) $this->notesColumn($notesId, 'folderid'));

        $info = Documents_FieldFile::getFileInfo($notesId);
        $this->assertSame(self::PREFIX . 'image.png', $info['filename']);
        $this->assertTrue($info['is_image']);
        $this->assertGreaterThan(0, $info['attachment_id']);
        $this->assertFileExists($info['path'], '添付ファイルを保存する');

        $result = $this->db->pquery('SELECT COUNT(*) AS cnt FROM vtiger_seattachmentsrel WHERE crmid = ?', [$notesId]);
        $this->assertSame(1, (int) $this->db->query_result($result, 0, 'cnt'), '他の入力欄のファイルは添付しない');
        $this->assertSame(['other'], array_keys($_FILES), '$_FILES は元に戻す');
    }

    public function test_手書き項目にはPNG以外を登録できずドキュメントも作らない(): void
    {
        $text = $this->tempFile('png', 'not a png');
        $before = $this->countDocuments();

        try {
            Documents_FieldFile::createDocument(
                $this->fileEntry($text, self::PREFIX . 'fake.png', 'image/png'),
                $this->fieldInfo('handwriting')
            );
            $this->fail('PNG でないファイルは手書き項目に登録できない');
        } catch (\Exception $e) {
            $this->assertSame(vtranslate('LBL_FILE_FIELD_HANDWRITING_PNG_ONLY', 'Documents'), $e->getMessage());
        }
        $this->assertSame($before, $this->countDocuments());
    }

    public function test_上限を超えるファイルは登録できない(): void
    {
        $png = $this->tempFile('png', $this->pngBinary(4, 4));
        $entry = $this->fileEntry($png, self::PREFIX . 'big.png', 'image/png');
        $entry['size'] = \Documents_Module_Model::getEffectiveMaxUploadSizeInBytes() + 1;

        $this->expectException(\Exception::class);
        Documents_FieldFile::createDocument($entry, $this->fieldInfo('file'));
    }

    // ---- レコードの保存 -----------------------------------------------------

    public function test_レコードを保存すると項目のドキュメントを関連付ける(): void
    {
        $notesId = $this->createImageDocument('link');
        $accountId = $this->saveAccount('link', [self::$fields['file']['fieldname'] => (string) $notesId]);

        $this->assertSame($notesId, Documents_FieldFile::getStoredDocumentId($accountId, $this->fieldInfo('file'), 'Accounts'));
        $this->assertTrue($this->isLinked($accountId, $notesId));

        // もう一度保存しても関連付けは増やさない
        $this->saveAccount('link', [self::$fields['file']['fieldname'] => (string) $notesId], $accountId);
        $result = $this->db->pquery('SELECT COUNT(*) AS cnt FROM vtiger_senotesrel WHERE crmid = ? AND notesid = ?', [$accountId, $notesId]);
        $this->assertSame(1, (int) $this->db->query_result($result, 0, 'cnt'));
    }

    public function test_参照できないドキュメントは項目に入れられない(): void
    {
        $notesId = $this->createRestrictedDocument('denied');

        $this->loginAs(self::REP_USER_ID);
        $this->assertFalse(Documents_FieldFile::canAttachDocument($notesId));

        try {
            $this->saveAccount('denied', [self::$fields['file']['fieldname'] => (string) $notesId], null, self::REP_USER_ID);
            $this->fail('参照できないドキュメントは入れられない');
        } catch (\Exception $e) {
            $this->assertSame(vtranslate('LBL_FILE_FIELD_DOCUMENT_DENIED', 'Documents'), $e->getMessage());
        }
        $this->assertSame(0, $this->countAccounts('denied'), 'レコードも作らない');
    }

    public function test_値が変わらなければ参照できないドキュメントのままでも保存できる(): void
    {
        $notesId = $this->createRestrictedDocument('keep');
        $fieldName = self::$fields['file']['fieldname'];
        $accountId = $this->saveAccount('keep', [$fieldName => (string) $notesId], null, self::REP_USER_ID);

        $this->loginAs(self::REP_USER_ID);
        $this->saveAccount('keep', [$fieldName => (string) $notesId, 'description' => 'updated'], $accountId, self::REP_USER_ID);

        $this->assertSame($notesId, Documents_FieldFile::getStoredDocumentId($accountId, $this->fieldInfo('file'), 'Accounts'));
    }

    public function test_存在しない値やドキュメント以外のIDは入れられない(): void
    {
        $accountId = $this->saveAccount('other');
        $this->assertFalse(Documents_FieldFile::canAttachDocument($accountId), 'ドキュメント以外のレコードは入れられない');
        $this->assertFalse(Documents_FieldFile::canAttachDocument(999999999));

        $this->expectException(\Exception::class);
        $this->saveAccount('other2', [self::$fields['file']['fieldname'] => (string) $accountId]);
    }

    // ---- 一時ファイルと保存時の版 -------------------------------------------

    public function test_アップロードは一時ファイルに置くだけでドキュメントを作らない(): void
    {
        $before = $this->countDocuments();

        $result = $this->uploadDraft('file', 'draft.png');

        $this->assertMatchesRegularExpression('/^draft:[0-9a-f]{32}$/', $result['draft']);
        $this->assertSame(self::PREFIX . 'draft.png', $result['filename']);
        $this->assertTrue($result['is_image']);
        $this->assertStringStartsWith('data:image/png;base64,', $result['thumbnail']['data_uri']);
        $this->assertSame($before, $this->countDocuments(), 'レコードを保存するまでドキュメントは作らない');
    }

    public function test_手書きの自動保存は同じ一時ファイルを上書きする(): void
    {
        $before = $this->countDocuments();
        $first = $this->uploadDraft('handwriting', 'sign1.png');

        $second = $this->uploadDraft('handwriting', 'sign2.png', $first['draft'], 80);

        $this->assertSame($first['draft'], $second['draft']);
        $this->assertSame(self::PREFIX . 'sign2.png', $second['filename']);
        $this->assertSame($before, $this->countDocuments());
    }

    public function test_他人の一時ファイルは上書きせず別の一時ファイルにする(): void
    {
        $adminDraft = $this->uploadDraft('handwriting', 'admin.png');

        $this->loginAs(self::REP_USER_ID);
        $repDraft = $this->uploadDraft('handwriting', 'rep.png', $adminDraft['draft']);

        $this->assertNotSame($adminDraft['draft'], $repDraft['draft']);
        $this->loginAs(self::ADMIN_USER_ID);
        $this->assertNotNull(
            \Documents_FieldFileDraft::get($adminDraft['draft'], self::ADMIN_USER_ID, self::$fields['handwriting']['fieldid']),
            '他人の一時ファイルは書き換えない'
        );
    }

    public function test_一時ファイルを入れて新しいレコードを保存するとドキュメントを作って値をそのIDにする(): void
    {
        $draft = $this->uploadDraft('handwriting', 'new.png')['draft'];

        $accountId = $this->saveAccount('new', [self::$fields['handwriting']['fieldname'] => $draft]);

        $notesId = Documents_FieldFile::getStoredDocumentId($accountId, $this->fieldInfo('handwriting'), 'Accounts');
        $this->assertGreaterThan(0, $notesId);
        $this->assertHandwritingFileName($notesId, $accountId);
        $this->assertSame(1, $this->countVersions($notesId));
        $this->assertTrue($this->isLinked($accountId, $notesId));
        $this->assertNull(
            \Documents_FieldFileDraft::get($draft, self::ADMIN_USER_ID, self::$fields['handwriting']['fieldid']),
            '保存したら一時ファイルは消す'
        );
    }

    public function test_ファイルを変えて保存すると同じドキュメントに版を足す(): void
    {
        $fieldName = self::$fields['handwriting']['fieldname'];
        $accountId = $this->saveAccount('version', [$fieldName => $this->uploadDraft('handwriting', 'v1.png')['draft']]);
        $notesId = Documents_FieldFile::getStoredDocumentId($accountId, $this->fieldInfo('handwriting'), 'Accounts');
        $before = $this->countDocuments();

        $this->saveAccount('version', [$fieldName => $this->uploadDraft('handwriting', 'v2.png', null, 80)['draft']], $accountId);

        $this->assertSame($notesId, Documents_FieldFile::getStoredDocumentId($accountId, $this->fieldInfo('handwriting'), 'Accounts'));
        $this->assertSame(2, $this->countVersions($notesId));
        $this->assertSame($before, $this->countDocuments(), 'ドキュメントは増やさない');
        $this->assertHandwritingFileName($notesId, $accountId);
    }

    public function test_手書きのファイル名は項目名_レコードID_項目ID_保存日時にする(): void
    {
        $accountId = $this->saveAccount('naming', [
            self::$fields['handwriting']['fieldname'] => $this->uploadDraft('handwriting', 'naming.png')['draft'],
            self::$fields['file']['fieldname'] => $this->uploadDraft('file', 'naming-file.png')['draft'],
        ]);

        $notesId = Documents_FieldFile::getStoredDocumentId($accountId, $this->fieldInfo('handwriting'), 'Accounts');
        $fileName = $this->assertHandwritingFileName($notesId, $accountId);
        $this->assertSame(pathinfo($fileName, PATHINFO_FILENAME), $this->notesColumn($notesId, 'title'), 'タイトルも合わせる');
        $result = $this->db->pquery(
            'SELECT a.name FROM vtiger_attachments a INNER JOIN vtiger_seattachmentsrel r ON r.attachmentsid = a.attachmentsid WHERE r.crmid = ?',
            [$notesId]
        );
        $this->assertSame($fileName, $this->db->query_result($result, 0, 'name'), 'ダウンロードするときの名前も合わせる');

        $fileNotesId = Documents_FieldFile::getStoredDocumentId($accountId, $this->fieldInfo('file'), 'Accounts');
        $this->assertSame(self::PREFIX . 'naming-file.png', Documents_FieldFile::getFileInfo($fileNotesId)['filename'], '手書きでない項目は元のファイル名のまま');
    }

    public function test_ファイルが変わらなければ版を増やさない(): void
    {
        $fieldName = self::$fields['handwriting']['fieldname'];
        $accountId = $this->saveAccount('same', [$fieldName => $this->uploadDraft('handwriting', 'same.png')['draft']]);
        $notesId = Documents_FieldFile::getStoredDocumentId($accountId, $this->fieldInfo('handwriting'), 'Accounts');

        $this->saveAccount('same', [$fieldName => (string) $notesId, 'description' => 'updated'], $accountId);

        $this->assertSame(1, $this->countVersions($notesId));
    }

    public function test_既存のドキュメントを編集できなければ新しいドキュメントにする(): void
    {
        $restrictedId = $this->createRestrictedDocument('readonly');
        $fieldName = self::$fields['file']['fieldname'];
        $accountId = $this->saveAccount('readonly', [$fieldName => (string) $restrictedId], null, self::REP_USER_ID);

        $this->loginAs(self::REP_USER_ID);
        $draft = $this->uploadDraft('file', 'mine.png')['draft'];
        $this->saveAccount('readonly', [$fieldName => $draft], $accountId, self::REP_USER_ID);

        $notesId = Documents_FieldFile::getStoredDocumentId($accountId, $this->fieldInfo('file'), 'Accounts');
        $this->assertNotSame($restrictedId, $notesId);
        $this->assertSame(self::PREFIX . 'mine.png', Documents_FieldFile::getFileInfo($notesId)['filename']);
        $this->assertSame(1, $this->countVersions($restrictedId), '編集できないドキュメントには触らない');
    }

    public function test_他人や別の項目の一時ファイルではレコードを保存できない(): void
    {
        $draft = $this->uploadDraft('handwriting', 'others.png')['draft'];

        foreach ([
            [self::REP_USER_ID, self::$fields['handwriting']['fieldname']],
            [self::ADMIN_USER_ID, self::$fields['file']['fieldname']],
        ] as [$userId, $fieldName]) {
            $this->loginAs($userId);
            try {
                $this->saveAccount('others', [$fieldName => $draft], null, $userId);
                $this->fail('自分がこの項目に置いた一時ファイルでなければ保存できない');
            } catch (\Exception $e) {
                $this->assertSame(vtranslate('LBL_FILE_FIELD_DRAFT_INVALID', 'Documents'), $e->getMessage());
            }
        }
        $this->assertSame(0, $this->countAccounts('others'), 'レコードも作らない');
    }

    // ---- 表示 ---------------------------------------------------------------

    public function test_詳細とレポートではファイル名を出す(): void
    {
        $notesId = $this->createImageDocument('display');
        $fieldModel = Vtiger_Field_Model::getInstance(self::$fields['file']['fieldid']);
        $uiType = $fieldModel->getUITypeModel();

        $this->assertSame(self::PREFIX . 'display.png', $uiType->getDisplayValue((string) $notesId));
        $this->assertSame('', $uiType->getDisplayValue(''));
        $this->assertSame('', $uiType->getDisplayValue('0'));
        $this->assertSame("\u{1F4CE} " . self::PREFIX . 'display.png', getReportFileFieldValue((string) $notesId));
        $this->assertSame('', getReportFileFieldValue(''));

        $props = $uiType->getWebComponentProps((string) $notesId, 'detail');
        $this->assertSame('detail', $props['mode']);
        $this->assertSame('Accounts', $props['module']);
        $this->assertSame(self::$fields['file']['fieldname'], $props['fieldName']);
        $this->assertFalse($props['handwriting']);
        $this->assertSame($notesId, $props['file']['id']);
        $this->assertArrayNotHasKey('path', $props['file'], 'サーバー上のパスは画面に渡さない');
    }

    public function test_一覧の列検索にはファイルアップロード項目の入力欄を出さない(): void
    {
        $fieldModel = Vtiger_Field_Model::getInstance(self::$fields['file']['fieldid']);
        $template = $fieldModel->getUITypeModel()->getListSearchTemplateName();

        $html = $this->renderTemplate($template, [
            'FIELD_MODEL' => $fieldModel,
            'SEARCH_INFO' => [],
            'USER_MODEL' => Users_Record_Model::getCurrentUserModel(),
        ]);

        $this->assertStringNotContainsString('listSearchContributor', $html, '値はドキュメントIDのため検索できない');
        $this->assertStringNotContainsString('<input', $html);
    }

    public function test_フィルターの条件にはファイルアップロード項目を出さない(): void
    {
        $recordStructure = Vtiger_RecordStructure_Model::getInstanceForModule(
            Vtiger_Module_Model::getInstance('Accounts'),
            Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_FILTER
        )->getStructure();

        $html = $this->renderTemplate('AdvanceFilterCondition.tpl', [
            'RECORD_STRUCTURE' => $recordStructure,
            'CONDITION_INFO' => [],
            'MODULE' => 'Accounts',
            'SOURCE_MODULE' => 'Accounts',
            'ADVANCED_FILTER_OPTIONS' => [],
            'ADVANCED_FILTER_OPTIONS_BY_TYPE' => [],
            'DATE_FILTERS' => [],
        ]);

        $this->assertStringContainsString('data-field-name="accountname"', $html, '他の項目は出す');
        $this->assertStringNotContainsString('data-field-name="' . self::$fields['file']['fieldname'] . '"', $html);
        $this->assertStringNotContainsString('data-field-name="' . self::$fields['handwriting']['fieldname'] . '"', $html);
    }

    public function test_削除したドキュメントは表示しない(): void
    {
        $notesId = $this->createImageDocument('deleted');
        $this->db->pquery('UPDATE vtiger_crmentity SET deleted = 1 WHERE crmid = ?', [$notesId]);

        $this->assertNull(Documents_FieldFile::getFileInfo($notesId));
        $this->assertSame([], Documents_FieldFile::getFileInfos([$notesId]));
    }

    // ---- PDF テンプレート ---------------------------------------------------

    public function test_PDFテンプレートでファイル名と画像のタグと画像のURLを出せる(): void
    {
        $fieldName = self::$fields['handwriting']['fieldname'];
        $accountId = $this->saveAccount('pdf', [$fieldName => $this->uploadDraft('handwriting', 'pdf.png')['draft']]);
        $notesId = Documents_FieldFile::getStoredDocumentId($accountId, $this->fieldInfo('handwriting'), 'Accounts');
        $info = Documents_FieldFile::getFileInfo($notesId);

        $html = $this->renderPdfTemplate(
            '<p>[$accounts-' . $fieldName . '$]</p><div>$accounts-' . $fieldName . '#img$</div>'
            . '<img width="120" src="$accounts-' . $fieldName . '#src$">',
            $accountId
        );

        $dataUri = 'data:image/png;base64,' . base64_encode((string) file_get_contents($info['path']));
        $this->assertStringContainsString('[' . $info['filename'] . ']', $html, '変数そのままはファイル名');
        $this->assertStringContainsString('<div><div><img src="' . $dataUri . '"', $html, '#img は画像のタグ（div で囲む）');
        $this->assertStringContainsString('<img width="120" src="' . $dataUri . '">', $html, '#src は画像の URL（data:）だけ');
    }

    public function test_PDFを出力すると手書きの画像が入る(): void
    {
        require_once 'modules/Vtiger/helpers/PDF.php';
        $fieldName = self::$fields['handwriting']['fieldname'];
        $accountId = $this->saveAccount('pdf-output', [$fieldName => $this->uploadDraft('handwriting', 'out.png')['draft']]);
        $withImage = $this->createPdfTemplate('<p>$accounts-' . $fieldName . '$</p>$accounts-' . $fieldName . '#img$<p>after</p>');
        $withoutImage = $this->createPdfTemplate('<p>$accounts-' . $fieldName . '$</p><p>after</p>');

        $pdf = (string) \PDF_helper::createPDF($withImage, $accountId, 'Accounts', true);
        $plain = (string) \PDF_helper::createPDF($withoutImage, $accountId, 'Accounts', true);

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(0, substr_count($pdf, '/Subtype /Image'), '画像が埋め込まれる');
        $this->assertSame(0, substr_count($plain, '/Subtype /Image'));
        $this->assertStringNotContainsString('__FR_FILE_FIELD_', $pdf, '目印を残さない');
    }

    public function test_PDFテンプレートの画像の変数は項目が空や画像でなければ何も出さない(): void
    {
        $fileField = self::$fields['file']['fieldname'];
        $handwritingField = self::$fields['handwriting']['fieldname'];
        $text = $this->tempFile('txt', 'hello');
        $notesId = Documents_FieldFile::createDocument($this->fileEntry($text, self::PREFIX . 'memo.txt', 'text/plain'), $this->fieldInfo('file'));
        $accountId = $this->saveAccount('pdf-empty', [$fileField => (string) $notesId]);

        $html = $this->renderPdfTemplate(
            '[$accounts-' . $fileField . '#img$][$accounts-' . $fileField . '#src$]'
            . '[$accounts-' . $handwritingField . '#img$][$accounts-' . $handwritingField . '#src$]',
            $accountId
        );

        $this->assertSame('[][][][]', $html);
    }

    public function test_PDFのファイル名の設定が空でも出力できる(): void
    {
        // PDF のファイル名（pdffilename）が未設定のテンプレートでは null で展開が呼ばれる
        $accountId = $this->saveAccount('pdf-null');

        $this->assertSame('', (string) \Vtiger_InventoryPDFController::getMergedDescription(null, $accountId, 'Accounts'));
        $this->assertSame('', (string) \Vtiger_InventoryPDFController::applyingSpecialSymbol(null));
    }

    public function test_テンプレートの画面では画像のURLの変数のimgを代わりの画像にする(): void
    {
        require_once 'modules/Documents/utils/FieldFileTemplate.php';
        $html = '<img width="120" src="$accounts-cf_1#src$"><img src=\'$accounts-cf_2#src$\' alt="b">'
            . '<img src="logo.png"><p>$accounts-cf_3#src$</p>';

        $this->assertSame(
            '<img width="120" src="' . \Documents_FieldFileTemplate::PREVIEW_IMAGE_URL . '">'
            . '<img src=\'' . \Documents_FieldFileTemplate::PREVIEW_IMAGE_URL . '\' alt="b">'
            . '<img src="logo.png"><p>$accounts-cf_3#src$</p>',
            \Documents_FieldFileTemplate::replaceSrcVariablesForPreview($html)
        );
        $this->assertFileExists('public/' . \Documents_FieldFileTemplate::PREVIEW_IMAGE_URL);
    }

    public function test_PDFテンプレートの変数一覧にファイル名と画像と画像のURLを出す(): void
    {
        $fieldName = self::$fields['handwriting']['fieldname'];
        $options = \PDFTemplates_Module_Model::getInstance('PDFTemplates')->getAllModulePDFTemplateFields()['Accounts'];
        $values = array_column($options, 1);

        foreach (['', '#img', '#src'] as $suffix) {
            $this->assertContains('$accounts-' . $fieldName . $suffix . '$', $values);
        }
    }

    // ---- API ----------------------------------------------------------------

    public function test_設定APIは手書き入力の指定と上限を返す(): void
    {
        $result = $this->callApi([
            'module' => 'Accounts', 'api' => 'FileField', 'mode' => 'config',
            'field' => self::$fields['handwriting']['fieldname'],
        ]);

        $this->assertTrue($result['handwriting']);
        $this->assertSame(\Documents_Module_Model::getEffectiveMaxUploadSizeInBytes(), $result['max_upload_size']);
    }

    public function test_ファイルアップロード項目でない項目はAPIで扱わない(): void
    {
        $this->expectException(\ApiException::class);
        $this->callApi(['module' => 'Accounts', 'api' => 'FileField', 'mode' => 'config', 'field' => 'accountname']);
    }

    public function test_サムネイルAPIはドキュメントを参照できれば返す(): void
    {
        $notesId = $this->createImageDocument('thumb');

        $result = $this->callApi([
            'module' => 'Accounts', 'api' => 'FileField', 'mode' => 'thumbnail',
            'field' => self::$fields['file']['fieldname'], 'document' => $notesId,
        ]);

        $this->assertSame($notesId, $result['file']['id']);
        $this->assertStringStartsWith('data:image/png;base64,', $result['thumbnail']['data_uri']);
    }

    public function test_サムネイルAPIはレコードの項目に入っているドキュメントならレコードの参照権限で返す(): void
    {
        $notesId = $this->createRestrictedDocument('via-record');
        $fieldName = self::$fields['file']['fieldname'];
        $accountId = $this->saveAccount('via-record', [$fieldName => (string) $notesId], null, self::REP_USER_ID);
        $otherAccountId = $this->saveAccount('via-other', [], null, self::REP_USER_ID);

        $this->loginAs(self::REP_USER_ID);
        $params = ['module' => 'Accounts', 'api' => 'FileField', 'mode' => 'thumbnail', 'field' => $fieldName, 'document' => $notesId];

        $result = $this->callApi($params + ['record' => $accountId]);
        $this->assertSame($notesId, $result['file']['id'], '項目に入っているレコードから辿れば見られる');

        foreach ([[], ['record' => $otherAccountId]] as $extra) {
            try {
                $this->callApi($params + $extra);
                $this->fail('ドキュメントもレコードの項目も参照できなければ返さない');
            } catch (\ApiForbiddenException $e) {
                $this->assertSame(403, $e->getHttpStatusCode());
            }
        }
    }

    public function test_サムネイルAPIはドキュメントを指定しなければレコードの項目に入っているドキュメントを返す(): void
    {
        // インライン編集の保存直後、画面は一時ファイルを指す値しか知らないため、レコードから引き直す
        $fieldName = self::$fields['handwriting']['fieldname'];
        $accountId = $this->saveAccount('stored', [$fieldName => $this->uploadDraft('handwriting', 'stored.png')['draft']]);
        $notesId = Documents_FieldFile::getStoredDocumentId($accountId, $this->fieldInfo('handwriting'), 'Accounts');
        $emptyAccountId = $this->saveAccount('stored-empty');
        $params = ['module' => 'Accounts', 'api' => 'FileField', 'mode' => 'thumbnail', 'field' => $fieldName];

        $result = $this->callApi($params + ['record' => $accountId]);
        $this->assertSame($notesId, $result['file']['id']);
        $this->assertStringStartsWith('data:image/png;base64,', $result['thumbnail']['data_uri']);

        $empty = $this->callApi($params + ['record' => $emptyAccountId]);
        $this->assertNull($empty['file'], '項目が空なら file は null');
        $this->assertNull($empty['thumbnail']);
    }

    public function test_ファイル情報にドキュメント自体を参照できるかを含める(): void
    {
        // 画面はこれを見て、参照できればドキュメントの詳細を、できなければ画像の拡大表示だけを出す
        $notesId = $this->createRestrictedDocument('viewable');
        $fieldName = self::$fields['file']['fieldname'];
        $accountId = $this->saveAccount('viewable', [$fieldName => (string) $notesId], null, self::REP_USER_ID);
        $params = ['module' => 'Accounts', 'api' => 'FileField', 'mode' => 'thumbnail', 'field' => $fieldName, 'document' => $notesId, 'record' => $accountId];
        $uiType = Vtiger_Field_Model::getInstance(self::$fields['file']['fieldid'])->getUITypeModel();

        $this->assertTrue($this->callApi($params)['file']['viewable']);
        $this->assertTrue($uiType->getWebComponentProps((string) $notesId, 'detail')['file']['viewable']);

        $this->loginAs(self::REP_USER_ID);
        $this->assertFalse($this->callApi($params)['file']['viewable'], 'レコードからは見られるがドキュメント自体は参照できない');
        $this->assertFalse($uiType->getWebComponentProps((string) $notesId, 'detail')['file']['viewable']);
    }

    public function test_サムネイルAPIは拡大表示用の大きな画像も返す(): void
    {
        $png = $this->tempFile('png', $this->pngBinary(1200, 600));
        $notesId = Documents_FieldFile::createDocument($this->fileEntry($png, self::PREFIX . 'large.png', 'image/png'), $this->fieldInfo('file'));
        $params = ['module' => 'Accounts', 'api' => 'FileField', 'mode' => 'thumbnail', 'field' => self::$fields['file']['fieldname'], 'document' => $notesId];

        $this->assertSame(Documents_FieldFile::THUMBNAIL_MAX_SIZE, $this->callApi($params)['thumbnail']['width']);
        $this->assertSame(1200, $this->callApi($params + ['size' => 'large'])['thumbnail']['width'], '元の大きさまで（拡大はしない）');
    }

    // ---- ヘルパー -----------------------------------------------------------

    /**
     * レイアウトエディタと同じ処理で顧客企業にファイルアップロード項目を追加する
     *
     * @return array{fieldid:int, fieldname:string}
     */
    private function addFileField(string $label, bool $handwriting): array
    {
        $moduleModel = Settings_LayoutEditor_Module_Model::getInstanceByName('Accounts');
        $result = $this->db->pquery('SELECT blockid FROM vtiger_blocks WHERE tabid = ? ORDER BY sequence LIMIT 1', [getTabid('Accounts')]);
        $blockId = (int) $this->db->query_result($result, 0, 'blockid');

        $fieldModel = $moduleModel->addField('FileUpload', $blockId, [
            'fieldType' => 'FileUpload',
            'fieldLabel' => $label,
            'mandatory' => 'O',
            'quickcreate' => 1,
            'masseditable' => 2,
            'summaryfield' => 0,
            'headerfield' => 0,
            'fieldDefaultValue' => '',
            'handwriting' => $handwriting ? '1' : '0',
        ]);

        return ['fieldid' => (int) $fieldModel->getId(), 'fieldname' => $fieldModel->getName()];
    }

    private function fieldInfo(string $key): array
    {
        return Documents_FieldFile::getFileField('Accounts', self::$fields[$key]['fieldname']);
    }

    private function fieldRow(int $fieldId): array
    {
        $result = $this->db->pquery('SELECT * FROM vtiger_field WHERE fieldid = ?', [$fieldId]);
        return $this->db->fetchByAssoc($result, -1, false);
    }

    private function columnType(string $table, string $column): string
    {
        $result = $this->db->pquery(
            'SELECT DATA_TYPE AS data_type FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        );
        return (string) $this->db->query_result($result, 0, 'data_type');
    }

    private function createImageDocument(string $suffix, ?int $folderId = null): int
    {
        $png = $this->tempFile('png', $this->pngBinary(60, 30));
        return Documents_FieldFile::createDocument(
            $this->fileEntry($png, self::PREFIX . $suffix . '.png', 'image/png'),
            $this->fieldInfo('file'),
            $folderId
        );
    }

    /** 管理者だけが参照できるフォルダにドキュメントを作る */
    private function createRestrictedDocument(string $suffix): int
    {
        $folderId = $this->createFolder('restricted_' . $suffix);
        $this->setFolderPermissions($folderId, [['owner', 'user', self::ADMIN_USER_ID]]);
        \Documents_FolderPermission::clearCache();
        return $this->createImageDocument($suffix, $folderId);
    }

    /**
     * 顧客企業を保存する（イベントハンドラが動く経路）
     *
     * @return int レコードID
     */
    private function saveAccount(string $suffix, array $values = [], ?int $recordId = null, int $ownerId = self::ADMIN_USER_ID): int
    {
        $recordModel = $recordId
            ? Vtiger_Record_Model::getInstanceById($recordId, 'Accounts')
            : Vtiger_Record_Model::getCleanInstance('Accounts');
        if ($recordId) {
            $recordModel->set('mode', 'edit');
        } else {
            $recordModel->set('accountname', self::PREFIX . $suffix);
            $recordModel->set('assigned_user_id', $ownerId);
        }
        foreach ($values as $name => $value) {
            $recordModel->set($name, $value);
        }
        $recordModel->save();
        return (int) $recordModel->getId();
    }

    private function isLinked(int $recordId, int $notesId): bool
    {
        $result = $this->db->pquery('SELECT 1 FROM vtiger_senotesrel WHERE crmid = ? AND notesid = ?', [$recordId, $notesId]);
        return $this->db->num_rows($result) > 0;
    }

    private function countDocuments(): int
    {
        $result = $this->db->pquery("SELECT COUNT(*) AS cnt FROM vtiger_crmentity WHERE setype = 'Documents'", []);
        return (int) $this->db->query_result($result, 0, 'cnt');
    }

    private function countAccounts(string $suffix): int
    {
        $result = $this->db->pquery(
            'SELECT COUNT(*) AS cnt FROM vtiger_account a INNER JOIN vtiger_crmentity c ON c.crmid = a.accountid
              WHERE c.deleted = 0 AND a.accountname = ?',
            [self::PREFIX . $suffix]
        );
        return (int) $this->db->query_result($result, 0, 'cnt');
    }

    /**
     * API を呼び、応答（sendSuccess に渡した配列）を返す
     */
    private function callApi(array $params): array
    {
        $api = new Vtiger_FileField_Api();
        $request = $this->request($params);
        $api->checkPermission($request);
        $method = new \ReflectionMethod($api, 'processApi');
        $response = $method->invoke($api, $request);
        return $this->responseResult($response);
    }

    /**
     * アップロード API（mode=upload）で一時ファイルを置き、応答を返す
     *
     * @param string|null $draft 上書きする一時ファイル（手書きの自動保存）
     * @return array<string, mixed>
     */
    private function uploadDraft(string $fieldKey, string $name, ?string $draft = null, int $width = 60): array
    {
        $png = $this->tempFile('png', $this->pngBinary($width, 30));
        $_FILES = ['file' => $this->fileEntry($png, self::PREFIX . $name, 'image/png')];
        $params = ['module' => 'Accounts', 'api' => 'FileField', 'mode' => 'upload', 'field' => self::$fields[$fieldKey]['fieldname']];
        if ($draft !== null) {
            $params['draft'] = $draft;
        }
        try {
            $result = $this->callApi($params);
        } finally {
            $_FILES = [];
        }
        $this->drafts[] = (string) $result['draft'];
        return $result;
    }

    /**
     * 手書きのファイル名（項目名_レコードID_項目ID_年月日-時分秒.png）になっているか確かめる
     *
     * @return string ファイル名
     */
    private function assertHandwritingFileName(int $notesId, int $recordId): string
    {
        $fileName = Documents_FieldFile::getFileInfo($notesId)['filename'];
        $this->assertMatchesRegularExpression(
            '/^SPECTEST_手書き_' . $recordId . '_' . self::$fields['handwriting']['fieldid'] . '_\d{8}-\d{6}\.png$/u',
            $fileName
        );
        return $fileName;
    }

    /**
     * PDF テンプレートの変数を PDF の出力と同じ順で展開する（modules/Vtiger/helpers/PDF.php）
     */
    private function renderPdfTemplate(string $template, int $recordId): string
    {
        $template = \Vtiger_InventoryPDFController::getMergedDescription($template, $recordId, 'Accounts');
        $template = \Vtiger_InventoryPDFController::applyingAggrFunctions($template, $recordId, 'Accounts');
        $template = \Vtiger_InventoryPDFController::applyingFunctions($template, $recordId, 'Accounts');
        return \Vtiger_InventoryPDFController::applyingSpecialSymbol($template);
    }

    /**
     * PDF テンプレートを作る（クラスの最後ではなくテストごとに消す）
     */
    private function createPdfTemplate(string $body): int
    {
        $templateId = $this->db->getUniqueID('vtiger_pdftemplates');
        $this->db->pquery(
            'INSERT INTO vtiger_pdftemplates (templateid, templatename, module, body, deleted, systemtemplate) VALUES (?, ?, ?, ?, 0, 0)',
            [$templateId, self::PREFIX . 'pdf' . $templateId, 'Accounts', $body]
        );
        $this->pdfTemplates[] = $templateId;
        return $templateId;
    }

    private function countVersions(int $notesId): int
    {
        $result = $this->db->pquery('SELECT COUNT(*) AS cnt FROM vtiger_notes_file_versions WHERE notesid = ?', [$notesId]);
        return (int) $this->db->query_result($result, 0, 'cnt');
    }

    /**
     * 画面のテンプレート（layouts/v7/modules/Vtiger）を描画した HTML を返す
     *
     * @param array<string, mixed> $values
     */
    private function renderTemplate(string $template, array $values): string
    {
        $viewer = new Vtiger_Viewer();
        foreach ($values as $name => $value) {
            $viewer->assign($name, $value);
        }
        return (string) $viewer->view($template, 'Accounts', true);
    }

    private function tempFile(string $extension, string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'fieldfile') . '.' . $extension;
        file_put_contents($path, $content);
        $this->tempFiles[] = $path;
        return $path;
    }

    private function fileEntry(string $path, string $name, string $type): array
    {
        return ['name' => $name, 'type' => $type, 'tmp_name' => $path, 'error' => UPLOAD_ERR_OK, 'size' => filesize($path)];
    }

    /** 単色の PNG を作る */
    private function pngBinary(int $width, int $height): string
    {
        $row = "\x00" . str_repeat("\x00\x80\xff\xff", $width);
        $chunk = static function (string $type, string $data): string {
            return pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
        };
        return "\x89PNG\r\n\x1a\n"
            . $chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 6, 0, 0, 0))
            . $chunk('IDAT', (string) gzcompress(str_repeat($row, $height)))
            . $chunk('IEND', '');
    }

    /** テストで作った顧客企業を消す */
    private function cleanAccounts(): void
    {
        $result = $this->db->pquery('SELECT accountid FROM vtiger_account WHERE accountname LIKE ?', [self::PREFIX . '%']);
        for ($i = 0; $i < $this->db->num_rows($result); $i++) {
            $accountId = (int) $this->db->query_result($result, $i, 'accountid');
            foreach (['vtiger_senotesrel' => 'crmid', 'vtiger_accountscf' => 'accountid', 'vtiger_accountbillads' => 'accountaddressid',
                      'vtiger_accountshipads' => 'accountaddressid', 'vtiger_account' => 'accountid', 'vtiger_crmentity' => 'crmid'] as $table => $column) {
                $this->db->pquery("DELETE FROM {$table} WHERE {$column} = ?", [$accountId]);
            }
        }
    }

    /** テストで作ったドキュメントの添付（行とファイル）を消す */
    private function cleanAttachments(): void
    {
        $result = $this->db->pquery(
            'SELECT a.attachmentsid, a.path, a.storedname, a.name FROM vtiger_notes n
              INNER JOIN vtiger_seattachmentsrel r ON r.crmid = n.notesid
              INNER JOIN vtiger_attachments a ON a.attachmentsid = r.attachmentsid
              WHERE n.title LIKE ?',
            [self::PREFIX . '%']
        );
        for ($i = 0; $i < $this->db->num_rows($result); $i++) {
            $row = $this->db->query_result_rowdata($result, $i);
            $attachmentId = (int) $row['attachmentsid'];
            $path = $row['path'] . $attachmentId . '_' . ($row['storedname'] ?: $row['name']);
            if (is_file($path)) {
                unlink($path);
            }
            $this->db->pquery('DELETE FROM vtiger_seattachmentsrel WHERE attachmentsid = ?', [$attachmentId]);
            $this->db->pquery('DELETE FROM vtiger_attachments WHERE attachmentsid = ?', [$attachmentId]);
            $this->db->pquery('DELETE FROM vtiger_crmentity WHERE crmid = ?', [$attachmentId]);
        }
    }
}
