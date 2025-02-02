<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use GeminiAPI\Laravel\Facades\Gemini; // Or use the Client directly
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ReminderController extends Controller
{
    public function index()
    {
        $reminders = Reminder::all();
        return response()->json($reminders);
    }

    public function createReminder(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return response()->json(['error' => 'Invalid request'], 400);
        }

        $userMessage = $data['message']; // Don't lowercase yet, Gemini might need the case
        $conversationHistory = Session::get('conversation_history', []); // Get history

        $reminderTask = Session::get('reminder_task');
        $reminderDate = Session::get('reminder_date');
        $reminderTime = Session::get('reminder_time');
        $reminderSet = Session::get('reminder_set', false);

        // Gemini API call (Chat Completion)
        $geminiResponse = Gemini::Chat([ // Or use the Client directly
            'messages' => array_merge($conversationHistory, [['role' => 'user', 'content' => $userMessage]]),
        ]);

        $botReply = $geminiResponse->choices[0]->message->content;

        $userMessageLower = strtolower($userMessage); // Now lowercase for keyword matching

        if (str_contains($userMessageLower, 'reset')) {
            Session::forget(['reminder_task', 'reminder_date', 'reminder_time', 'reminder_set', 'conversation_history']); // Clear all
            $botReply = "Reminder reset.";
            return response()->json(['message' => $botReply]); // Return immediately after reset
        }

        if (!str_contains($userMessageLower, 'remind') && !str_contains($userMessageLower, 'set reminder')) {
            Session::forget(['reminder_task', 'reminder_date', 'reminder_time', 'reminder_set']); // Clear only reminder data
            // Let Gemini handle the non-reminder conversation
            return response()->json(['message' => $botReply]);
        }

        if (!$reminderSet) {
            if (!$reminderTask) {
                $reminderTask = $this->extractTask($userMessageLower); // Use lower case for extracting
                if ($reminderTask !== 'No task specified') {
                    Session::put('reminder_task', $reminderTask);
                    $botReply = "Okay, task set to: " . $reminderTask . ". " . $botReply; // Combine Gemini reply
                }
            }

            if (!$reminderDate) {
                $reminderDate = $this->extractDate($userMessageLower); // Use lower case for extracting
                if ($reminderDate !== 'No date specified') {
                    Session::put('reminder_date', $reminderDate);
                    $botReply = "Okay, date set to: " . $reminderDate . ". " . $botReply; // Combine Gemini reply
                }
            }

            if (!$reminderTime) {
                $reminderTime = $this->extractTime($userMessageLower); // Use lower case for extracting
                if ($reminderTime !== 'No time specified') {
                    Session::put('reminder_time', $reminderTime);
                    Session::put('reminder_set', true);

                    $finalMessage = "Reminder set! 📌 Task: $reminderTask, Date: $reminderDate, Time: $reminderTime";
                    $finalMessage .= "\n(Reminder would be scheduled now)";

                    Session::forget(['reminder_task', 'reminder_date', 'reminder_time']);

                    $botReply = $finalMessage; // Override Gemini reply with final message
                }
            }
        } else {
            $botReply = "Reminder is already set. You can say 'reset' to clear it. " . $botReply; // Combine with Gemini reply
        }

        // Store conversation history
        $conversationHistory[] = ['role' => 'user', 'content' => $userMessage];
        $conversationHistory[] = ['role' => 'assistant', 'content' => $botReply];
        Session::put('conversation_history', $conversationHistory);

        return response()->json(['message' => $botReply]);
    }




    private function extractTask($message)
    {
        $message = strtolower($message);

        // Remove common phrases to extract task
        $patterns = ['/\bi want to\b/i', '/\bi wanna\b/i', '/\bset a reminder to\b/i', '/\bremind me to\b/i', '/\bplease remind me to\b/i', '/\bcan you remind me to\b/i'];

        $cleanedMessage = preg_replace($patterns, '', $message);
        $cleanedMessage = trim($cleanedMessage);

        return !empty($cleanedMessage) ? ucfirst($cleanedMessage) : 'No task specified';
    }

    private function extractDate($message)
    {
        // List of possible days
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday', 'tomorrow', 'today'];

        // Convert message to lowercase for easier matching
        $message = strtolower($message);

        // Check if any day is mentioned in the message
        foreach ($days as $day) {
            if (str_contains($message, $day)) {
                return ucfirst($day); // Return capitalized day (e.g., "Monday")
            }
        }

        return 'No date specified';
    }

    private function extractTime($responseText)
    {
        // Logic to find and parse the time (for example, '10:00 AM')
        if (preg_match('/\b(?:\d{1,2}:\d{2} [APM]{2})\b/i', $responseText, $matches)) {
            return $matches[0];
        }
        return 'No time specified';
    }
}
