import { useState } from "react";
import { Button } from "@/components/ui/button";
import { CURL_PRESETS, applyPreset } from "./presets";
import { CurlPresetKey } from "./types";

interface Props {
  hasExistingContent: boolean;
  onApply: (r: { method: string; headers: string; body: string }) => void;
  labelFor?: (key: CurlPresetKey) => string;
  presetLabel?: string;
  confirmMessage?: string;
  okLabel?: string;
  cancelLabel?: string;
}

export function PresetSelector({
  hasExistingContent,
  onApply,
  labelFor,
  presetLabel = "プリセット:",
  confirmMessage = "既存の内容を上書きします。よろしいですか？",
  okLabel = "OK",
  cancelLabel = "キャンセル",
}: Props) {
  const [pending, setPending] = useState<CurlPresetKey | null>(null);

  const choose = (key: CurlPresetKey) => {
    if (hasExistingContent) setPending(key);
    else onApply(applyPreset(key));
  };
  const confirm = () => {
    if (pending) {
      onApply(applyPreset(pending));
      setPending(null);
    }
  };

  return (
    <div className="flex flex-wrap items-center gap-2">
      <span className="text-sm">{presetLabel}</span>
      {(Object.keys(CURL_PRESETS) as CurlPresetKey[]).map((k) => (
        <Button
          key={k}
          type="button"
          size="sm"
          variant="secondary"
          onClick={() => choose(k)}
        >
          {labelFor ? labelFor(k) : k}
        </Button>
      ))}
      {pending && (
        <div role="dialog" className="flex items-center gap-2 text-sm">
          <span>{confirmMessage}</span>
          <Button type="button" size="sm" onClick={confirm}>
            {okLabel}
          </Button>
          <Button
            type="button"
            size="sm"
            variant="outline"
            onClick={() => setPending(null)}
          >
            {cancelLabel}
          </Button>
        </div>
      )}
    </div>
  );
}
