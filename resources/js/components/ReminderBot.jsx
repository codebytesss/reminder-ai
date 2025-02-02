import React, { useState, useEffect } from "react";

export default function ReminderBot({ csrfToken }) {
    const [conversation, setConversation] = useState([
        { sender: "Bot", message: "Start a conversation..." },
    ]);
    const [reminders, setReminders] = useState([]);

    async function fetchReminders() {
        try {
            const response = await fetch("/api/reminder", {
                method: "GET",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
            });
            const data = await response.json();
            setReminders(data.data);
            console.log(data);
        } catch (error) {
            console.error("Error fetching reminders:", error);
        }
    }
    useEffect(() => {
        fetchReminders();
    }, []);

    function startListening() {
        let recognition = new (window.SpeechRecognition ||
            window.webkitSpeechRecognition)();
        recognition.lang = "en-US";
        recognition.start();

        recognition.onresult = (event) => {
            let transcript = event.results[0][0].transcript;
            updateConversation("User", transcript);

            fetch("/api/reminder", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ message: transcript }),
            })
                .then((res) => res.json())
                .then((data) => {
                    updateConversation("Bot", data.message);
                    if (data.reminders) {
                        setReminders([...reminders, ...data.reminders]);
                    }
                })
                .catch((err) => console.error(err));
        };
    }

    function updateConversation(sender, message) {
        setConversation((prev) => [...prev, { sender, message }]);
    }

    async function deleteReminder(reminderId) {
        try {
            const response = await fetch(`/api/reminder/${reminderId}`, {
                method: "DELETE",
                headers: { "X-CSRF-TOKEN": csrfToken },
            });
            const data = await response.json();
            // console.log(data);
            // if (data.success) {
            await fetchReminders();
            // }
        } catch (err) {
            console.error(err);
        }
    }

    return (
        <div className="flex items-start justify-center h-screen bg-gray-100">
            <div className="w-full max-w-md mt-10 p-6 bg-white rounded-lg shadow-lg">
                <h1 className="mb-4 text-2xl font-bold text-center">
                    Reminder Bot
                </h1>

                {/* Conversation Area */}
                <div className="p-4 mb-4 overflow-y-auto border border-gray-300 rounded-lg h-60 bg-gray-50">
                    {conversation.map((msg, index) => (
                        <p
                            key={index}
                            className={
                                msg.sender === "User" ? "text-right" : ""
                            }
                        >
                            <strong>{msg.sender}:</strong> {msg.message}
                        </p>
                    ))}
                </div>

                {/* Voice Button */}
                <button
                    onClick={startListening}
                    className="w-full py-2 text-white transition bg-blue-500 rounded-lg shadow-md hover:bg-blue-600"
                >
                    🎤 Speak
                </button>

                {/* Reminder List */}
                <h2 className="mt-4 text-xl font-semibold">Saved Reminders</h2>
                <ul className="mt-2 max-h-[500px] overflow-y-scroll">
                    {reminders.map((reminder) => (
                        <li
                            key={reminder.id}
                            className="bg-gray-200 p-2 rounded-lg mt-2 flex justify-between items-center"
                        >
                            <span>
                                <strong>{reminder.task}</strong> -{" "}
                                {reminder.date || "No date"} at{" "}
                                {reminder.time || "No time"}
                            </span>
                            <button
                                onClick={() => deleteReminder(reminder.id)}
                                className="ml-4 text-red-500 hover:text-red-700"
                            >
                                Delete
                            </button>
                        </li>
                    ))}
                </ul>
            </div>
        </div>
    );
}
