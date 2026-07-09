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
}

export function TestSendPanel({
  getPayload,
  sendTest,
  buttonLabel = "テスト送信",
  sendingLabel = "送信中...",
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
      <Button
        type="button"
        size="sm"
        variant="outline"
        onClick={handleClick}
        disabled={loading}
      >
        {loading ? sendingLabel : buttonLabel}
      </Button>
      {result && (
        <div className="rounded-md border p-2 text-sm">
          <div className={result.success ? "text-green-600" : "text-red-600"}>
            {result.success ? "OK" : "NG"}
            {result.http_code != null && <> (HTTP {result.http_code})</>}
          </div>
          {result.error && (
            <pre className="whitespace-pre-wrap text-red-600">
              {result.error}
            </pre>
          )}
          {result.response != null && (
            <pre className="max-h-48 overflow-auto whitespace-pre-wrap">
              {result.response}
            </pre>
          )}
        </div>
      )}
    </div>
  );
}
