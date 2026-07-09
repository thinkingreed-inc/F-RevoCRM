import { useState } from "react";
import { Button } from "@/components/ui/button";

export interface TestSendPayload {
  url: string;
  method: string;
  headers: string;
  body: string;
  timeout: string;
}

export interface TestSendResult {
  success: boolean;
  http_code?: number;
  response?: string;
  error?: string;
}

interface Props {
  getPayload: () => TestSendPayload;
  sendTest: (p: TestSendPayload) => Promise<TestSendResult>;
  buttonLabel?: string;
  sendingLabel?: string;
  note?: string;
}

export function TestSendPanel({
  getPayload,
  sendTest,
  buttonLabel = "テスト送信",
  sendingLabel = "送信中...",
  note,
}: Props) {
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState<TestSendResult | null>(null);

  const handleClick = async () => {
    setLoading(true);
    setResult(null);
    try {
      const r = await sendTest(getPayload());
      setResult(r);
    } catch (e) {
      setResult({ success: false, error: (e as Error).message });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="space-y-2">
      {note && <p className="text-xs text-muted-foreground">{note}</p>}
      <Button
        type="button"
        size="sm"
        variant="outline"
        onClick={handleClick}
        disabled={loading}
      >
        {loading ? sendingLabel : buttonLabel}
      </Button>
      {result &&
        (() => {
          // 通信自体は成功でもHTTP 4xx/5xxは失敗として赤表示する
          const failed =
            !result.success ||
            (result.http_code != null && result.http_code >= 400);
          return (
            <div className="space-y-1 rounded-md border p-2 text-sm">
              <div className={failed ? "text-red-600" : "text-green-600"}>
                {failed ? "NG" : "OK"}
                {result.http_code != null && <> (HTTP {result.http_code})</>}
              </div>
              {result.error && (
                <div>
                  <div className="text-xs font-medium text-muted-foreground">
                    エラー
                  </div>
                  <pre className="max-h-48 overflow-auto whitespace-pre-wrap break-all text-red-600">
                    {result.error}
                  </pre>
                </div>
              )}
              {result.response != null && result.response !== "" && (
                <div>
                  <div className="text-xs font-medium text-muted-foreground">
                    レスポンス
                  </div>
                  <pre className="max-h-48 overflow-auto whitespace-pre-wrap break-all">
                    {result.response}
                  </pre>
                </div>
              )}
            </div>
          );
        })()}
    </div>
  );
}
