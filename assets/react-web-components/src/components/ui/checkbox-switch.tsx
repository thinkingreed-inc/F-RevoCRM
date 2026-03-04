import * as React from "react";
import { Checkbox } from "@/components/ui/checkbox";
import { cn } from "@/lib/utils";

interface CheckboxSwitchProps extends Omit<React.ButtonHTMLAttributes<HTMLButtonElement>, 'onChange'> {
  onChange?: (event: React.ChangeEvent<HTMLInputElement>) => void;
}

const CheckboxSwitch = React.forwardRef<
  HTMLButtonElement,
  CheckboxSwitchProps
>(({ className, ...props }, ref) => {
  const defaultValue = props.value === "1" ? true : false;

  return (
    <div className={cn("flex w-full items-center space-x-2 justify-start", className)}>
      <Checkbox
        defaultChecked={defaultValue}
        onCheckedChange={(checked) => {
          const name = props.name || "";
          const isChecked = checked === true;
          // onChange プロパティがあれば呼び出す
          if (props.onChange) {
            const event = {
              target: { name, value: isChecked ? "1" : "0" }
            } as React.ChangeEvent<HTMLInputElement>;
            props.onChange(event);
          }
        }}
        ref={ref as React.Ref<HTMLButtonElement>}
        disabled={props.disabled}
      />
    </div>
  );
});
CheckboxSwitch.displayName = "CheckboxSwitch";

export { CheckboxSwitch };
