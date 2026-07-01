import * as React from "react"
import { ChevronDownIcon } from "lucide-react"
import { cn } from "@/lib/utils"

/**
 * Collapsible - Simple collapsible component
 */
interface CollapsibleContextType {
  open: boolean
  setOpen: (open: boolean) => void
}

const CollapsibleContext = React.createContext<CollapsibleContextType | undefined>(undefined)

function useCollapsible() {
  const context = React.useContext(CollapsibleContext)
  if (!context) {
    throw new Error("useCollapsible must be used within a Collapsible")
  }
  return context
}

interface CollapsibleProps {
  children: React.ReactNode
  defaultOpen?: boolean
  open?: boolean
  onOpenChange?: (open: boolean) => void
  className?: string
}

function Collapsible({
  children,
  defaultOpen = false,
  open: controlledOpen,
  onOpenChange,
  className
}: CollapsibleProps) {
  const [internalOpen, setInternalOpen] = React.useState(defaultOpen)
  const isControlled = controlledOpen !== undefined
  const open = isControlled ? controlledOpen : internalOpen

  const setOpen = React.useCallback((newOpen: boolean) => {
    if (!isControlled) {
      setInternalOpen(newOpen)
    }
    onOpenChange?.(newOpen)
  }, [isControlled, onOpenChange])

  return (
    <CollapsibleContext.Provider value={{ open, setOpen }}>
      <div className={className}>
        {children}
      </div>
    </CollapsibleContext.Provider>
  )
}

interface CollapsibleTriggerProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  children: React.ReactNode
  className?: string
  showIcon?: boolean
}

function CollapsibleTrigger({
  children,
  className,
  showIcon = true,
  ...props
}: CollapsibleTriggerProps) {
  const { open, setOpen } = useCollapsible()

  return (
    <button
      type="button"
      onClick={() => setOpen(!open)}
      className={cn(
        "flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900",
        "focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 rounded",
        "transition-colors",
        className
      )}
      aria-expanded={open}
      {...props}
    >
      {showIcon && (
        <ChevronDownIcon
          className={cn(
            "h-4 w-4 transition-transform duration-200",
            open && "rotate-180"
          )}
          aria-hidden="true"
        />
      )}
      {children}
    </button>
  )
}

interface CollapsibleContentProps {
  children: React.ReactNode
  className?: string
}

function CollapsibleContent({ children, className }: CollapsibleContentProps) {
  const { open } = useCollapsible()

  return (
    <div
      className={cn(
        "overflow-hidden transition-all duration-200",
        open ? "max-h-[1000px] opacity-100 mt-2" : "max-h-0 opacity-0"
      )}
    >
      <div className={className}>
        {children}
      </div>
    </div>
  )
}

export { Collapsible, CollapsibleTrigger, CollapsibleContent }
