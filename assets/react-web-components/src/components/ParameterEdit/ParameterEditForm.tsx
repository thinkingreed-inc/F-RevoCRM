import React, { useEffect, useRef } from 'react';
import { Input } from '@/components/ui/input';
import { Textarea } from '../ui/textarea';
import { Label } from '@/components/ui/label';
import { ParameterRecord, ParameterType } from './types';
import { useTranslation } from '@/hooks/useTranslation';
import { ToggleSwitch } from '@/components/ui/toggle-switch';

interface ParameterEditFormProps {
	/** レコードデータ */
	record: ParameterRecord;
	/** 編集中の値 */
	value: string;
	/** 値変更ハンドラ */
	onValueChange: (value: string) => void;
	/** シークレット設定 */
	secret: boolean;
	/** シークレット変更ハンドラ */
	onSecretChange: (secret: boolean) => void;
	/** 説明文 */
	description?: string;
	/** 説明文変更ハンドラ */
	onDescriptionChange?: (description: string) => void;
	/** バリデーションエラー */
	error?: string | null;
	/** 無効状態 */
	disabled?: boolean;
}

// 表示位置調整用 共通クラス
const ROW_CLASS = 'flex items-start gap-2';
const LABEL_WRAP_CLASS = 'flex-shrink-0 w-[130px] pr-6';
const LABEL_CLASS = 'block text-md text-gray-700 text-right leading-none';
const RIGHT_COLUMN_CLASS = 'flex-1 min-w-0';
const CONTROL_HEIGHT_CLASS = 'h-9 flex items-center';

const adjustTextareaHeight = (textarea: HTMLTextAreaElement | null) => {
	if (!textarea) {
		return;
	}

	textarea.style.height = 'auto';
	textarea.style.height = `${textarea.scrollHeight}px`;
};

const isBooleanTrue = (currentValue: string): boolean => {
	const normalized = String(currentValue).toLowerCase();
	return normalized === 'true' || normalized === '1';
};

/**
 * ParameterEditForm - システム変数編集フォーム
 *
 * 型に応じた入力UIを表示:
 * - boolean: トグルスイッチ
 * - integer: 数値入力
 * - string: テキスト入力
 */
export const ParameterEditForm: React.FC<ParameterEditFormProps> = ({
	record,
	value,
	onValueChange,
	secret,
	onSecretChange,
	description,
	onDescriptionChange,
	error,
	disabled = false,
}) => {
	const { t } = useTranslation();
	const descriptionRef = useRef<HTMLTextAreaElement | null>(null);

	useEffect(() => {
		adjustTextareaHeight(descriptionRef.current);
	}, [description]);

	const renderValueInput = (type: ParameterType) => {
		if (type === 'boolean') {
			return (
				<ToggleSwitch
					value={isBooleanTrue(value)}
					onChange={(checked) => onValueChange(checked ? 'true' : 'false')}
					disabled={disabled}
					trueLabel={t('LBL_TRUE')}
					falseLabel={t('LBL_FALSE')}
				/>
			);
		}

		if (type === 'integer') {
			return (
				<Input
					type="number"
					value={value}
					onChange={(e) => onValueChange(e.target.value)}
					disabled={disabled}
					className="w-full max-w-[200px]"
				/>
			);
		}

		return (
			<Input
				type="text"
				value={value}
				onChange={(e) => onValueChange(e.target.value)}
				disabled={disabled}
				maxLength={512}
			/>
		);
	};

	return (
		<div className="flex-1 overflow-auto px-8 py-4">
			{/* キー（読み取り専用） */}
		<div className="px-4">
			<h4 className="fieldBlockHeader font-bold leading-[1.1] mt-0 mb-2 pb-1 border-b border-gray-300">
				{record.key}
			</h4>
		</div>


			{/* 値 */}
			<div className={ROW_CLASS}>
				<div className={`${LABEL_WRAP_CLASS} ${CONTROL_HEIGHT_CLASS} flex items-end justify-end`}>
					<Label className={LABEL_CLASS}>{t('Value')}</Label>
				</div>

				<div className={RIGHT_COLUMN_CLASS}>
					{renderValueInput(record.type)}

					{error && (
						<p className="mt-2 text-sm text-destructive">{error}</p>
					)}
				</div>
			</div>

			{/* 備考 */}
			{record.description !== undefined && (
				<div className={ROW_CLASS}>
					<div className={`${LABEL_WRAP_CLASS} ${CONTROL_HEIGHT_CLASS} flex items-end justify-end`}>
						<Label className={LABEL_CLASS}>{t('Description')}</Label>
					</div>

					<div className={RIGHT_COLUMN_CLASS}>
						<Textarea
							ref={descriptionRef}
							value={description ?? ''}
							onChange={(e) => {
								onDescriptionChange?.(e.target.value);
								adjustTextareaHeight(e.currentTarget);
							}}
							rows={3}
							disabled={disabled}
							className="w-[90%] resize-none overflow-hidden pt-[9px]"
						/>
					</div>
				</div>
			)}

			{/* シークレット設定 */}
			<div className="pt-5">
				<div className={ROW_CLASS}>
					<div className={`${LABEL_WRAP_CLASS} ${CONTROL_HEIGHT_CLASS} flex items-end justify-end`}>
						<Label className={LABEL_CLASS}>{t('LBL_SECRET')}</Label>
					</div>
                    <div className="items-start text-left">
                        <ToggleSwitch
                            value={secret}
                            onChange={onSecretChange}
                            disabled={disabled}
                            trueLabel={t('LBL_SECRET_ON')}
                            falseLabel={t('LBL_SECRET_OFF')}
                        />
                        <p className="mt-1 text-xs text-muted-foreground">
                            {t('LBL_SECRET_HELP')}
                        </p>
                    </div>
				</div>
			</div>
		</div>
	);
};