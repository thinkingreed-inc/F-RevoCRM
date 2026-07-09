import { describe, it, expect, vi } from "vitest";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { TestSendPanel } from "./TestSendPanel";

describe("TestSendPanel", () => {
  it("shows http code after test send", async () => {
    const sendTest = vi
      .fn()
      .mockResolvedValue({ success: true, http_code: 200, response: "ok" });
    const getPayload = () => ({
      url: "https://e.com",
      method: "POST",
      headers: "",
      body: "{}",
      timeout: "30",
    });
    render(<TestSendPanel getPayload={getPayload} sendTest={sendTest} />);
    await userEvent.click(
      screen.getByRole("button", { name: /テスト送信|test/i }),
    );
    expect(await screen.findByText(/200/)).toBeInTheDocument();
    expect(sendTest).toHaveBeenCalledWith(getPayload());
  });

  it("shows error message on failure", async () => {
    const sendTest = vi
      .fn()
      .mockResolvedValue({ success: false, error: "Invalid or unsafe URL" });
    render(
      <TestSendPanel
        getPayload={() => ({
          url: "",
          method: "POST",
          headers: "",
          body: "",
          timeout: "30",
        })}
        sendTest={sendTest}
      />,
    );
    await userEvent.click(
      screen.getByRole("button", { name: /テスト送信|test/i }),
    );
    expect(
      await screen.findByText(/Invalid or unsafe URL/),
    ).toBeInTheDocument();
  });
});
