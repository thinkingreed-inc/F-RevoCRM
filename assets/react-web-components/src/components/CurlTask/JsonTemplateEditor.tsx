import { useRef, useState } from "react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { FieldInserter } from "./FieldInserter";
import { formatJson, validateJson, insertAtCursor } from "./jsonEditorUtils";
import { FieldOption } from "./types";

interface Props {
  value: string;
  onChange: (v: string) => void;
  fields: FieldOption[];
  rows?: number;
  validate?: boolean;
  formatLabel?: string;
  insertLabel?: string;
}

export function JsonTemplateEditor({
  value,
  onChange,
  fields,
  rows = 8,
  validate,
  formatLabel = "整形",
  insertLabel,
}: Props) {
  const ref = useRef<HTMLTextAreaElement>(null);
  const [formatError, setFormatError] = useState<string | null>(null);
  const status = validate ? validateJson(value) : { valid: true };

  const handleFormat = () => {
    const r = formatJson(value);
    if (r.ok) {
      setFormatError(null);
      onChange(r.text);
    } else {
      setFormatError(r.error);
    }
  };

  const handleInsert = (variable: string) => {
    const el = ref.current;
    const start = el?.selectionStart ?? value.length;
    const end = el?.selectionEnd ?? value.length;
    const { text, caret } = insertAtCursor(value, start, end, variable);
    onChange(text);
    requestAnimationFrame(() => {
      if (el) {
        el.focus();
        el.setSelectionRange(caret, caret);
      }
    });
  };

  return (
    <div className="space-y-1">
      <div className="flex items-center gap-2">
        <Button type="button" size="sm" variant="outline" onClick={handleFormat}>
          {formatLabel}
        </Button>
        <FieldInserter
          fields={fields}
          onInsert={handleInsert}
          placeholder={insertLabel}
        />
        {validate && (
          <span
            className={cn(
              "text-xs",
              status.valid ? "text-green-600" : "text-red-600",
            )}
          >
            {status.valid ? "JSON OK" : "不正なJSON"}
          </span>
        )}
      </div>
      <textarea
        ref={ref}
        value={value}
        rows={rows}
        onChange={(e) => onChange(e.target.value)}
        className={cn(
          "border-input flex w-full rounded-sm border bg-transparent px-2 py-1 font-mono text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50",
        )}
      />
      {formatError && <p className="text-xs text-red-600">{formatError}</p>}
    </div>
  );
}
