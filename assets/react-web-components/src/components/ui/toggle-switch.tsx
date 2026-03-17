import * as React from "react";
import { Switch } from "@/components/ui/switch";
import { cn } from "@/lib/utils";

const CONTROL_HEIGHT_CLASS = 'h-9 flex items-center';

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
    <div className={cn(`${CONTROL_HEIGHT_CLASS} ${CONTROL_HEIGHT_CLASS}`, className, 'gap-3')}>
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