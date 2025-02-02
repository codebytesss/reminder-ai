<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversational Reminder Bot</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/markdown-it/dist/markdown-it.min.js"></script> <!-- Markdown-it -->

    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body class="flex items-start justify-center h-screen bg-gray-100">
    <div class="w-full max-w-md p-6 mt-10 bg-white rounded-lg shadow-lg">
        <h1 class="mb-4 text-2xl font-bold text-center">Reminder Bot</h1>

        <!-- Conversation Area -->
        <div id="conversation" class="p-4 mb-4 overflow-y-auto border border-gray-300 rounded-lg h-60 bg-gray-50">
            <p class="text-center text-gray-500">Start a conversation...</p>
        </div>

        <!-- Voice Button -->
        <button onclick="startListening()"
            class="w-full py-2 text-white transition bg-blue-500 rounded-lg shadow-md hover:bg-blue-600">
            🎤 Speak
        </button>

        <!-- Reminder List -->
        <h2 class="mt-4 text-xl font-semibold">Saved Reminders</h2>
        <ul id="reminderList" class="mt-2 max-h-[300px] overflow-y-scroll"></ul>
    </div>

    <script>
        const md = window.markdownit(); // Initialize markdown-it
        addReminderToList({!! json_encode($reminders) !!});

        function startListening() {
            let recognition = new(window.SpeechRecognition || window.webkitSpeechRecognition)();
            recognition.lang = 'en-US';
            recognition.start();

            recognition.onresult = function(event) {
                let transcript = event.results[0][0].transcript;
                updateConversation('User', transcript);

                $.ajax({
                    url: "{{ route('reminder.create') }}",
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    },
                    dataType: "json",
                    data: JSON.stringify({
                        message: transcript
                    }), // Convert to JSON
                    contentType: 'application/json',
                    success: function(data) {
                        let formattedMessage = md.render(data.message); // Convert to Markdown
                        updateConversation('Bot', formattedMessage, true);
                        addReminderToList(data.reminder);
                        // if (data.reminders) {
                        //     console.log(data.reminders);
                        // }
                    },
                    error: function(xhr) {
                        console.log(xhr.responseText);
                    }
                });
            };
        }

        function updateConversation(sender, message, isMarkdown = false) {
            let conversationDiv = document.getElementById('conversation');
            let newMessage = `<p><strong>${sender}:</strong> ${isMarkdown ? message : escapeHTML(message)}</p>`;
            conversationDiv.innerHTML += newMessage;
            conversationDiv.scrollTop = conversationDiv.scrollHeight;
        }

        function addReminderToList(reminders) {
        console.log(reminders);

            let reminderList = document.getElementById('reminderList');
            if (!Array.isArray(reminders)) {
                reminders = [reminders];
            }
            reminders.forEach(reminder => {
                let listItem = document.createElement('li');
                listItem.className = "bg-gray-200 p-2 rounded-lg mt-2 flex justify-between items-center";
                listItem.innerHTML = `
            <span><strong>${reminder.task}</strong> - ${reminder.date || 'No date'} at ${reminder.time || 'No time'}</span>
            <button onclick="deleteReminder(${reminder.id})" class="ml-4 text-red-500 hover:text-red-700">Delete</button>
            `;
                reminderList.appendChild(listItem);
            });
        }

        function deleteReminder(reminderId) {
            $.ajax({

                url: `/api/reminder/${reminderId}`,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },

                contentType: 'application/json',
                success: function(data) {
                    if (data.success) {
                        document.querySelector(`button[onclick="deleteReminder(${reminderId})"]`).parentElement
                            .remove();
                        // window.reload();
                    } else {
                        console.log('Failed to delete reminder', data);
                    }
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                }
            });
        }

        function escapeHTML(str) {
            return str.replace(/</g, "&lt;").replace(/>/g, "&gt;");
        }
    </script>
</body>

</html>
