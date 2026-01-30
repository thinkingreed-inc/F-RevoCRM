import React from "react";
import * as NavigationMenuPrimitive from "@radix-ui/react-navigation-menu";
import { ChevronDown } from "lucide-react";
import { cn } from "@/lib/utils";

// 型定義
interface ModuleInfo {
  name: string;
  label: string;
  url: string;
  icon: string;
}

interface AppMenuType {
  name: string;
  label: string;
  icon: string;
  modules: ModuleInfo[];
}

export interface AppMenuProps {
  appMenus?: AppMenuType[];
}

// アイコンのHTMLをReactコンポーネントに変換
const ModuleIcon: React.FC<{ iconHtml: string }> = ({ iconHtml }) => {
  if (!iconHtml) return null;
  return (
    <span
      className="inline-flex items-center justify-center w-5 h-5 mr-2 text-gray-500"
      dangerouslySetInnerHTML={{ __html: iconHtml }}
    />
  );
};

// スタイル定義（コンポーネント内で完結）
// ※ triggerの色はTailwind preflightが無効なため、インラインスタイルで直接指定
const styles = {
  root: "flex items-center border-0 bg-transparent h-[42px] gap-0",
  trigger: cn(
    "cursor-pointer flex items-center px-3 py-2 text-[13px] font-normal rounded-none transition-colors",
    "text-[#b3c0ce] hover:text-white hover:bg-transparent",
    "data-[state=open]:bg-transparent data-[state=open]:text-white",
    "outline-none select-none"
  ),
  content: cn(
    "absolute top-full left-0 mt-0.5",
    "min-w-[160px] bg-white border border-gray-300 p-0 z-[9999]",
    "data-[state=open]:animate-in data-[state=closed]:animate-out",
    "data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0"
  ),
  link: cn(
    "cursor-pointer flex items-center px-3 py-1.5 text-[13px] text-gray-700",
    "hover:bg-gray-100 hover:text-gray-900",
    "focus:bg-gray-100 focus:text-gray-900 focus:outline-none",
    "flex py-6 px-8 gap-4 items-center"
  ),
};

export const AppMenu: React.FC<AppMenuProps> = ({ appMenus }) => {
  const menus = appMenus && appMenus.length > 0 ? appMenus : [];

  return (
    <NavigationMenuPrimitive.Root className={styles.root}>
      <NavigationMenuPrimitive.List className="flex items-center gap-0 list-none m-0 p-0 h-[42px] ">
        {menus.map((app) => (
          <NavigationMenuPrimitive.Item key={app.name} className="relative">
            <NavigationMenuPrimitive.Trigger
              className={styles.trigger}
              style={{ color: "#333" }}
            >
              {app.label}
              <ChevronDown className="ml-1 h-3 w-3 opacity-90" />
            </NavigationMenuPrimitive.Trigger>
            <NavigationMenuPrimitive.Content className={styles.content}>
              {app.modules.map((module) => (
                <div key={module.name} className="hover:bg-gray-100">
                  <NavigationMenuPrimitive.Link
                    className={styles.link}
                    href={module.url}
                  >
                    <ModuleIcon iconHtml={module.icon} />
                    <span className="truncate">{module.label}</span>
                  </NavigationMenuPrimitive.Link>
                </div>
              ))}
            </NavigationMenuPrimitive.Content>
          </NavigationMenuPrimitive.Item>
        ))}
      </NavigationMenuPrimitive.List>
    </NavigationMenuPrimitive.Root>
  );
};

export default AppMenu;
