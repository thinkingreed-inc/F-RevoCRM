import * as React from "react";
import { Switch } from "@/components/ui/switch";
import { cn } from "@/lib/utils";

// 他の入力（Input / Textarea）と同じ高さにして、ラベル（leading-[30px]）と
// 縦の中心を揃える
const CONTROL_HEIGHT_CLASS = "h-[30px] flex items-center";

type ToggleSwitchProps = {
  value: boolean;
  onChange: (value: boolean) => void;
  disabled?: boolean;
  trueLabel: string;
  falseLabel: string;
  className?: string;
};

export const ToggleSwitch: React.FC<ToggleSwitchProps> = ({
  value,
  onChange,
  disabled = false,
  trueLabel,
  falseLabel,
  className = "",
}) => {
  return (
    <div className={cn(CONTROL_HEIGHT_CLASS, className, "gap-3")}>
      <Switch
        checked={value}
        onCheckedChange={onChange}
        disabled={disabled}
        aria-label={value ? trueLabel : falseLabel}
      />
      <span className="text-md text-gray-700 leading-none">
        {value ? trueLabel : falseLabel}
      </span>
    </div>
  );
};

export default ToggleSwitch;
