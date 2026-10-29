<?php

/**
 * 自動採番項目（uitype 997）の遡及採番 API（管理者のみ）
 *
 * 値が空の既存レコードに、後から番号を付ける。処理の本体はサーバー側のジョブで、
 * 定期ジョブ（cron）が少しずつ進める。画面はこの API で進み具合を読み、開いている間は
 * kick で同じ処理を呼んで進みを速くできる。
 *
 * Usage:
 *   ?module=Accounts&api=AutoNumber&mode=preview&field=cf_123[&created_from=2026-01-01][&created_to=2026-03-31]
 *     → 対象の件数と、先頭・末尾に付く予定の番号（連番は進めない）
 *   POST module=Accounts&api=AutoNumber&mode=start&field=cf_123[&created_from=..][&created_to=..]
 *     → ジョブを登録する
 *   ?module=Accounts&api=AutoNumber&mode=status&field=cf_123
 *     → 最新のジョブの状態（軽い読み取り。ジョブが無ければ job は null）
 *   POST ...&mode=pause|resume|cancel&field=cf_123
 *     → 最新のジョブを一時停止・再開・中止する
 *   POST ...&mode=kick&field=cf_123
 *     → 最新のジョブを少しだけ進める（画面を開いている間の加速用）
 *
 * 作成日時の範囲は、実行ユーザーのタイムゾーンの日付（YYYY-MM-DD）で指定する。
 */
require_once 'modules/Vtiger/utils/AutoNumber.php';
require_once 'modules/Vtiger/utils/AutoNumberValue.php';

/**
 * @phpstan-import-type Job from Vtiger_AutoNumberBackfill
 */
class Vtiger_AutoNumber_Api extends Vtiger_Api_Controller
{
    /** 画面からの加速（kick）で 1 回に進める件数・秒数 */
    private const KICK_RECORDS = 100;
    private const KICK_SECONDS = 8.0;

    private const WRITE_MODES = ['start', 'pause', 'resume', 'cancel', 'kick'];

    public function loginRequired(): bool
    {
        return true;
    }

    public function validateRequest(Vtiger_Request $request): bool
    {
        if (in_array($request->get('mode'), self::WRITE_MODES, true)) {
            return (bool) $request->validateWriteAccess();
        }
        return (bool) $request->validateReadAccess();
    }

    public function checkPermission(Vtiger_Request $request): bool
    {
        $user = Users_Record_Model::getCurrentUserModel();
        if (!$user->isAdminUser()) {
            throw new ApiForbiddenException(vtranslate('LBL_PERMISSION_DENIED'));
        }
        return true;
    }

    protected function processApi(Vtiger_Request $request): Vtiger_Response
    {
        $moduleName = (string) $request->getModule();
        $fieldName = $this->stringParam($request, 'field');
        $configs = Vtiger_AutoNumber::getModuleConfigs($moduleName);
        if (!isset($configs[$fieldName])) {
            throw new ApiNotFoundException(vtranslate('LBL_RECORD_NOT_FOUND'));
        }
        $fieldId = Vtiger_AutoNumberValue::int($configs[$fieldName]['fieldid']);
        $userId = Vtiger_AutoNumberValue::int(Users_Record_Model::getCurrentUserModel()->getId());

        $mode = $this->stringParam($request, 'mode');
        switch ($mode) {
            case 'preview':
                [$from, $to] = $this->getRange($request);
                return $this->sendSuccess($this->preview($fieldId, $from, $to, $userId));
            case 'start':
                [$from, $to] = $this->getRange($request);
                try {
                    Vtiger_AutoNumberBackfill::start($fieldId, $userId, $from, $to);
                } catch (InvalidArgumentException $e) {
                    throw new ApiBadRequestException(vtranslate('LBL_AUTONUMBER_ERR_LOCKED_BY_JOB', 'Vtiger'));
                }
                return $this->sendSuccess($this->status($fieldId));
            case 'status':
                return $this->sendSuccess($this->status($fieldId));
            case 'pause':
            case 'resume':
            case 'cancel':
                $job = $this->latestJob($fieldId);
                if ($mode === 'pause') {
                    Vtiger_AutoNumberBackfill::pause($job['jobid']);
                } elseif ($mode === 'resume') {
                    Vtiger_AutoNumberBackfill::resume($job['jobid']);
                } else {
                    Vtiger_AutoNumberBackfill::cancel($job['jobid']);
                }
                return $this->sendSuccess($this->status($fieldId));
            case 'kick':
                $job = $this->latestJob($fieldId);
                Vtiger_AutoNumberBackfill::processBatch($job['jobid'], self::KICK_RECORDS, self::KICK_SECONDS);
                return $this->sendSuccess($this->status($fieldId));
            default:
                throw new ApiBadRequestException('Invalid mode: ' . $mode);
        }
    }

    /**
     * @return array{total: int, first: string, last: string, warnings: list<string>}
     */
    private function preview(int $fieldId, ?string $from, ?string $to, int $userId): array
    {
        return Vtiger_AutoNumberBackfill::preview($fieldId, $from, $to, $userId);
    }

    /**
     * 最新のジョブの状態（画面の進み具合の表示用）
     *
     * @return array{job: array<string, mixed>|null}
     */
    private function status(int $fieldId): array
    {
        $job = Vtiger_AutoNumberBackfill::getLatestJob($fieldId);
        if ($job === null) {
            return ['job' => null];
        }
        return ['job' => [
            'jobid' => $job['jobid'],
            'status' => $job['status'],
            'status_label' => (string) vtranslate('LBL_AUTONUMBER_STATUS_' . $job['status'], 'Vtiger'),
            'total' => $job['total'],
            'processed' => $job['processed'],
            'failed' => $job['failed'],
            'last_number' => $job['last_number'],
            'unfinished' => in_array($job['status'], Vtiger_AutoNumberBackfill::UNFINISHED, true),
        ]];
    }

    /**
     * @return Job
     */
    private function latestJob(int $fieldId): array
    {
        $job = Vtiger_AutoNumberBackfill::getLatestJob($fieldId);
        if ($job === null) {
            throw new ApiNotFoundException(vtranslate('LBL_RECORD_NOT_FOUND'));
        }
        return $job;
    }

    /**
     * 作成日時の範囲（DB の時刻）。実行ユーザーのタイムゾーンの日付から変換する
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function getRange(Vtiger_Request $request): array
    {
        return [
            $this->toDbTime($this->stringParam($request, 'created_from'), '00:00:00'),
            $this->toDbTime($this->stringParam($request, 'created_to'), '23:59:59'),
        ];
    }

    private function toDbTime(string $date, string $time): ?string
    {
        if ($date === '') {
            return null;
        }
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
            throw new ApiBadRequestException('Invalid date: ' . $date);
        }
        $local = new DateTimeImmutable($date . ' ' . $time, Vtiger_AutoNumber::now()->getTimezone());
        return $local->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    private function stringParam(Vtiger_Request $request, string $name): string
    {
        $value = $request->get($name);
        return is_scalar($value) ? (string) $value : '';
    }
}
