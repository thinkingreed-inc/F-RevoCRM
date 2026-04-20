const VOID_ELEMENTS = new Set([
  "area","base","br","col","embed","hr","img","input",
  "link","meta","param","source","track","wbr",
]);

export function formatHtml(html: string): string {
  let result = "";
  let indent = 0;
  const tab = "  ";

  const tokens = html.replace(/>\s+</g, "><").split(/(<[^>]+>)/);

  for (const token of tokens) {
    if (!token) continue;

    if (token.startsWith("</")) {
      indent = Math.max(0, indent - 1);
      result += `${tab.repeat(indent)}${token}\n`;
    } else if (token.startsWith("<")) {
      const tagName = (token.match(/^<(\w+)/)?.[1] || "").toLowerCase();
      const selfClosing = token.endsWith("/>") || VOID_ELEMENTS.has(tagName);
      result += `${tab.repeat(indent)}${token}\n`;
      if (!selfClosing) indent++;
    } else {
      const text = token.trim();
      if (text) result += `${tab.repeat(indent)}${text}\n`;
    }
  }
  return result.trimEnd();
}
