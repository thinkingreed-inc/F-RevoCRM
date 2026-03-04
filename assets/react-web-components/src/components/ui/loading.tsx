import { cn } from "@/lib/utils";

interface LoadingProps extends React.HTMLAttributes<HTMLDivElement> {
  size?: "sm" | "md" | "lg";
  showText?: boolean;
}

export function Loading({ size = "md", className, showText = true, ...props }: LoadingProps) {
  const sizeClasses = {
    sm: "w-4 h-4 border-2",
    md: "w-6 h-6 border-2",
    lg: "w-8 h-8 border-3",
  };

  const textSizeClasses = {
    sm: "text-sm",
    md: "text-base",
    lg: "text-lg",
  };

  return (
    <div className="flex flex-col items-center gap-2" {...props}>
      <div
        className={cn(
          "animate-spin rounded-full border-gray-300 border-t-primary",
          sizeClasses[size],
          className
        )}
      />
      {showText && (
        <span className={cn("text-muted-foreground", textSizeClasses[size])}>Loading...</span>
      )}
    </div>
  );
}
