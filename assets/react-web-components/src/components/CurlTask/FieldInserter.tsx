import { useState } from "react";
import { Button } from "@/components/ui/button";
import { FieldOption } from "./types";

interface Props {
  fields: FieldOption[];
  onInsert: (variable: string) => void;
  placeholder?: string;
}

export function FieldInserter({
  fields,
  onInsert,
  placeholder = "フィールド挿入",
}: Props) {
  const [open, setOpen] = useState(false);

  const choose = (name: string) => {
    onInsert("$" + name);
    setOpen(false);
  };

  return (
    <div className="relative inline-block">
      <Button
        type="button"
        variant="outline"
        size="sm"
        onClick={() => setOpen((v) => !v)}
        disabled={fields.length === 0}
      >
        {placeholder}
      </Button>
      {open && (
        <div className="absolute z-50 mt-1 max-h-64 w-56 overflow-auto rounded-md border bg-background p-1 shadow-md">
          {fields.map((f) => (
            <button
              key={f.name}
              type="button"
              className="block w-full rounded px-2 py-1 text-left text-sm hover:bg-accent"
              onClick={() => choose(f.name)}
            >
              {f.label}
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
