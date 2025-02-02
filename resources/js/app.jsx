import React from "react";
import { createRoot } from "react-dom/client";
import ReminderBot from "./components/ReminderBot";

const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    .getAttribute("content");

createRoot(document.getElementById("reminder-bot")).render(
    <ReminderBot csrfToken={csrfToken} />
);
