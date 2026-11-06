<?php

/**
 * 共有リンク API
 *
 * 管理画面（Web コンポーネント <shared-links-page>）から呼び出す JSON API。
 * システム管理者のみ利用できる。
 *
 * Usage:
 *   ?module=SharedLinks&parent=Settings&api=SharedLinkAPI&mode=info
 *     → 差し込める項目・公開範囲の選択肢・画面の文言を返す
 *   ?module=SharedLinks&parent=Settings&api=SharedLinkAPI&mode=list
 *     → すべてのリンク（無効も含む）を返す
 *   POST mode=save&link=<JSON>[&id=1] … 追加・更新。入力に誤りがあれば saved=false と項目ごとのエラーを返す
 *   POST mode=delete&id=1            … 削除
 *   POST mode=reorder&ids=3,1,2      … 並び替え
 */
class Settings_SharedLinks_SharedLinkAPI_Api extends Vtiger_Api_Controller
{
    private const QUALIFIED_MODULE = Settings_SharedLinks_Module_Model::QUALIFIED_MODULE;

    /** 更新を伴う mode */
    private const WRITE_MODES = ['save', 'delete', 'reorder'];

    public function loginRequired(): bool
    {
        return true;
    }

    /**
     * @return array<mixed>
     */
    public function requiresPermission(Vtiger_Request $request): array
    {
        // 設定機能のため権限判定は checkPermission で管理者チェックのみ行う
        return [];
    }

    public function checkPermission(Vtiger_Request $request): bool
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        if (empty($currentUser) || !$currentUser->isAdminUser()) {
            throw new ApiForbiddenException(vtranslate('LBL_PERMISSION_DENIED'));
        }
        return true;
    }

    public function validateRequest(Vtiger_Request $request): bool
    {
        if (in_array($request->get('mode'), self::WRITE_MODES, true)) {
            $request->validateWriteAccess();
        } else {
            $request->validateReadAccess();
        }
        return true;
    }

    protected function processApi(Vtiger_Request $request): Vtiger_Response
    {
        $mode = Settings_SharedLinks_Rules_Model::toString($request->get('mode'));
        switch ($mode) {
            case 'info':
                return $this->sendSuccess($this->getInfo());
            case 'list':
                return $this->sendSuccess(['links' => $this->getList()]);
            case 'save':
                return $this->sendSuccess($this->save($request));
            case 'delete':
                return $this->sendSuccess($this->delete($request));
            case 'reorder':
                return $this->sendSuccess($this->reorder($request));
            default:
                throw new ApiBadRequestException('Invalid mode: ' . $mode);
        }
    }

    /**
     * 画面の初期表示に必要な情報
     *
     * @return array<string, mixed>
     */
    private function getInfo(): array
    {
        $userFields = [];
        foreach (Settings_SharedLinks_Module_Model::getInsertableUserFields() as $name => $label) {
            $userFields[] = ['name' => $name, 'label' => $label];
        }

        return [
            'user_fields' => $userFields,
            'roles' => Settings_SharedLinks_Module_Model::getRoles(),
            'groups' => Settings_SharedLinks_Module_Model::getGroups(),
            'users' => Settings_SharedLinks_Module_Model::getActiveUsers(),
            'limits' => [
                'title_max' => Settings_SharedLinks_Rules_Model::TITLE_MAX,
                'url_max' => Settings_SharedLinks_Rules_Model::URL_MAX,
                'description_max' => Settings_SharedLinks_Rules_Model::DESCRIPTION_MAX,
                'params_max' => Settings_SharedLinks_Rules_Model::PARAMS_MAX,
                'param_name_max' => Settings_SharedLinks_Rules_Model::PARAM_NAME_MAX,
                'param_value_max' => Settings_SharedLinks_Rules_Model::PARAM_VALUE_MAX,
                'window_min' => Settings_SharedLinks_Rules_Model::WINDOW_MIN,
                'window_max' => Settings_SharedLinks_Rules_Model::WINDOW_MAX,
                'default_window_width' => Settings_SharedLinks_Rules_Model::DEFAULT_WINDOW_WIDTH,
                'default_window_height' => Settings_SharedLinks_Rules_Model::DEFAULT_WINDOW_HEIGHT,
            ],
            'labels' => $this->getLabels(),
        ];
    }

    /**
     * 画面で使う文言（言語ファイルの内容）
     *
     * @return array<string, string>
     */
    private function getLabels(): array
    {
        // キーの一覧は必ずある英語のファイルから取り、文言はユーザーの言語で引く
        $labels = [];
        $strings = Vtiger_Language_Handler::getModuleStringsFromFile('en_us', self::QUALIFIED_MODULE);
        $languageStrings = is_array($strings) && is_array($strings['languageStrings'] ?? null)
            ? $strings['languageStrings'] : [];
        foreach (array_keys($languageStrings) as $key) {
            $key = (string) $key;
            $labels[$key] = Settings_SharedLinks_Module_Model::translate($key, self::QUALIFIED_MODULE);
        }
        return $labels;
    }

    /**
     * すべてのリンクに、管理画面の表示用の情報（対象の名前・項目のラベル・警告）を付けて返す
     *
     * @return list<array<string, mixed>>
     */
    private function getList(): array
    {
        $userFields = Settings_SharedLinks_Module_Model::getInsertableUserFields();
        $nameCache = [];

        $links = [];
        foreach (Settings_SharedLinks_Module_Model::getAllLinks() as $link) {
            $hasWarning = false;

            $params = [];
            foreach ($link['params'] as $param) {
                $isUser = $param['value_type'] === 'user';
                $label = $isUser ? ($userFields[$param['value']] ?? null) : null;
                $missing = $isUser && $label === null;
                $params[] = $param + ['label' => $label, 'missing' => $missing];
                $hasWarning = $hasWarning || $missing;
            }

            $targets = [];
            foreach ($link['targets'] as $target) {
                $cacheKey = $target['type'] . ':' . $target['id'];
                if (!array_key_exists($cacheKey, $nameCache)) {
                    $nameCache[$cacheKey] = Settings_SharedLinks_Module_Model::getTargetName(
                        $target['type'],
                        $target['id']
                    );
                }
                $name = $nameCache[$cacheKey];
                $targets[] = $target + ['name' => $name, 'deleted' => $name === null];
                // 削除された対象は管理者に外してもらう（すべて削除されると誰にも表示されない）
                $hasWarning = $hasWarning || $name === null;
            }

            $links[] = array_merge($link, [
                'params' => $params,
                'targets' => $targets,
                'has_warning' => $hasWarning,
                // POST の送り先が CSP（form-action）で許可されていない
                'post_blocked' => Settings_SharedLinks_Module_Model::isPostBlocked($link['method'], $link['url']),
            ]);
        }
        return $links;
    }

    /**
     * 追加・更新
     *
     * @return array<string, mixed>
     */
    private function save(Vtiger_Request $request): array
    {
        $id = $this->getLinkId($request, false);

        // URL の & などがエンティティ化されないよう、加工前の値を受け取る
        $input = json_decode(Settings_SharedLinks_Rules_Model::toString($request->getRaw('link')), true);
        if (!is_array($input)) {
            throw new ApiBadRequestException(Settings_SharedLinks_Module_Model::translate('LBL_ERROR_INVALID_VALUE', self::QUALIFIED_MODULE));
        }

        $savedTargets = [];
        if ($id !== null) {
            foreach (Settings_SharedLinks_Module_Model::getAllLinks() as $saved) {
                if ($saved['id'] === $id) {
                    $savedTargets = $saved['targets'];
                }
            }
        }

        $link = Settings_SharedLinks_Rules_Model::normalize($input);
        $errors = Settings_SharedLinks_Rules_Model::validate(
            $link,
            array_keys(Settings_SharedLinks_Module_Model::getInsertableUserFields()),
            function (string $type, string $targetId) use ($savedTargets): bool {
                return Settings_SharedLinks_Module_Model::isSelectableTarget($type, $targetId, $savedTargets);
            }
        );
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $field => $labelKey) {
                $messages[$field] = Settings_SharedLinks_Module_Model::translate($labelKey, self::QUALIFIED_MODULE);
            }
            return ['saved' => false, 'errors' => $messages];
        }

        $savedId = Settings_SharedLinks_Module_Model::saveLink($link, $id);
        return ['saved' => true, 'id' => $savedId];
    }

    /**
     * 削除
     *
     * @return array<string, mixed>
     */
    private function delete(Vtiger_Request $request): array
    {
        $id = $this->getLinkId($request, true);
        Settings_SharedLinks_Module_Model::deleteLink($id);
        return ['id' => $id, 'deleted' => true];
    }

    /**
     * 並び替え
     *
     * @return array<string, mixed>
     */
    private function reorder(Vtiger_Request $request): array
    {
        $ids = [];
        foreach (explode(',', Settings_SharedLinks_Rules_Model::toString($request->get('ids'))) as $value) {
            $value = trim($value);
            if (!preg_match('/^\d+$/', $value)) {
                throw new ApiBadRequestException(Settings_SharedLinks_Module_Model::translate('LBL_ERROR_INVALID_VALUE', self::QUALIFIED_MODULE));
            }
            $ids[] = (int) $value;
        }
        Settings_SharedLinks_Module_Model::reorder(array_values(array_unique($ids)));
        return ['ids' => $ids];
    }

    /**
     * リクエストの id を取り出す。存在しないリンクの id はエラー
     *
     * @return ($required is true ? int : int|null)
     */
    private function getLinkId(Vtiger_Request $request, bool $required): ?int
    {
        $raw = Settings_SharedLinks_Rules_Model::toString($request->get('id'));
        if ($raw === '' && !$required) {
            return null;
        }
        if (!preg_match('/^\d+$/', $raw) || !Settings_SharedLinks_Module_Model::linkExists((int) $raw)) {
            throw new ApiNotFoundException(Settings_SharedLinks_Module_Model::translate('LBL_ERROR_LINK_NOT_FOUND', self::QUALIFIED_MODULE));
        }
        return (int) $raw;
    }
}
