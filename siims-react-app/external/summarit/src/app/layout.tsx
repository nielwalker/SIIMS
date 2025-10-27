import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "Summarize Application",
  description: "Student practicum reporting system",
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  );
}
