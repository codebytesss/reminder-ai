<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use GeminiAPI\Laravel\Facades\Gemini;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class ReminderController extends Controller
{
    public function index()
    {
        $reminders = Reminder::get();
        return view('reminder', compact('reminders'));
    }

    public function deleteReminder($id)
    {

        $reminder = Reminder::find($id);

        $reminder->delete();

        return response()->json(['message' => 'Reminder deleted successfully', 'success' => true]);
    }


    public function createReminder(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['message'])) {
            return response()->json(['error' => 'Invalid request'], 400);
        }

        $userMessage = $data['message'];

        // Check for reset command
        $resetPatterns = ['/\breset\b/i', '/\bclear\b/i', '/\bstart over\b/i', '/\bdelete all\b/i'];
        foreach ($resetPatterns as $pattern) {
            if (preg_match($pattern, strtolower($userMessage))) {
                Session::forget(['reminder_task_question', 'reminder_date_question', 'reminder_time_question', 'reminder_task', 'reminder_date', 'reminder_time', 'matched', 'titleMatched', 'dateMatched', 'timeMatched']);
                return response()->json(['message' => 'All reminders have been cleared.']);
            }
        }

        // Existing code for handling reminders
        $setReminderPatterns = ['/\bset a reminder to\b/i', '/\bremind me to\b/i', '/\bplease remind me to\b/i', '/\bcan you remind me to\b/i', '/\bcan you set a reminder\b/i', '/\bdon\'t let me forget to\b/i', '/\bmake sure I remember to\b/i', '/\bhelp me remember to\b/i', '/\balert me to\b/i', '/\bnotify me to\b/i', '/\bremind me\b/i', '/\bcreate a reminder for\b/i', '/\badd a reminder to\b/i', '/\bset an alarm for\b/i', '/\bping me to\b/i', '/\btell me to\b/i', '/\bkeep me on track to\b/i', '/\bmake a note to\b/i', '/\bset up a reminder for\b/i', '/\bremind me about\b/i', '/\bremind me at\b/i', '/\bremind me on\b/i', '/\bremind me in\b/i', '/\bremind me when\b/i', '/\bset reminder\b/i', '/\bset a reminder\b/i', '/\bset an alarm\b/i', '/\bhey set reminder for me\b/i'];

        $setTitlePatterns = ['/\b I want to add\b/i', '/\bi wanna go to university\b/i', '/\bi want to\b/i', '/\bi wanna\b/i', '/\bjust wake me\b/i', '/\bwana go to \b/i', '/\bfor lunch\b/i', '/\bcan you remind me to\b/i', '/\bwake me to\b/i', '/\bfor office\b/i', '/\bi would like to add for \b/i', '/\bmake a note of\b/i', '/\bWake me up \b/i', '/\bfor the meeting\b/i', '/\bfor the call\b/i', '/\bfor the event\b/i', '/\bfor the party\b/i', '/\bfor the appointment\b/i', '/\bfor the interview\b/i', '/\bfor the presentation\b/i', '/\bfor the seminar\b/i', '/\bfor the conference\b/i', '/\bfor the webinar\b/i', '/\bfor the workshop\b/i', '/\bfor the training\b/i', '/\bfor the class\b/i', '/\bfor the lecture\b/i', '/\bfor the exam\b/i', '/\bfor the test\b/i', '/\bfor the quiz\b/i', '/\bfor the assignment\b/i', '/\bMake sure I dont miss \b/i', '/\bDont let me\b/i', '/\bI need to\b/i', '/\bI have a\b/i', '/\bLet me know when \b/i', '/\bcell me to \b/i'];

        $dayPatterns = [
            '/\b(monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b/i', // Matches "Monday" to "Sunday"
            '/\b(this|next|upcoming)?\s*(monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b/i', // Matches "this Monday", "next Friday"
            '/\btomorrow\b/i', // Matches "tomorrow"
            '/\btoday\b/i', // Matches "today"
            '/\bnext week\b/i', // Matches "next week"
            '/\bthis weekend\b/i', // Matches "this weekend"
            '/\btonight\b/i', // Matches "tonight"
            '/\bnext day\b/i', // Matches "next day"
            '/\bevery (morning|afternoon|evening|night|midnight)\b/i', // Matches "every morning", "every midnight"
        ];

        $timePatterns = [
            '/\b\d{1,2}:\d{2}\s?(AM|PM|am|pm)?\b/', // Matches "10:30 AM", "5:45 pm"
            '/\b\d{1,2}\s?(AM|PM|am|pm)\b/', // Matches "10 AM", "5 pm"
            '/\bin \d+ (minutes|hours|mins|hrs)\b/', // Matches "in 10 minutes", "in 2 hours"
            '/\bnoon\b/', // Matches "noon"
            '/\bmidnight\b/', // Matches "midnight"
            '/\bafter \d{1,2}\b/', // Matches "after 10"
            '/\baround \d{1,2}( AM| PM|am|pm)?\b/', // Matches "around 10 AM"
            '/\b\d{1,2}\s?(am|pm|AM|PM)\b/', // Matches "10 am", "10 pm"
        ];

        $taskQuestion = Session::get('reminder_task_question');
        $dateQuestion = Session::get('reminder_date_question');
        $timeQuestion = Session::get('reminder_time_question');
        $task = Session::get('reminder_task');
        $date = Session::get('reminder_date');
        $time = Session::get('reminder_time');

        $matched = Session::get('matched', false);
        $titleMatched = Session::get('titleMatched', false);
        $dateMatched = Session::get('dateMatched', false);
        $timeMatched = Session::get('timeMatched', false);

        foreach ($setReminderPatterns as $pattern) {
            if (preg_match($pattern, strtolower($userMessage))) {
                Session::put('matched', true);
                Session::put('reminder_task_question', false);
                $userMessage = preg_replace($pattern, '', strtolower($userMessage));
                $userMessage = trim($userMessage);
                break;
            }
        }

        if (Session::get('matched') === true && Session::get('reminder_task_question') === false) {
            $taskQuestion = 'What reminder would you like to add?';
            Session::put('reminder_task_question', $taskQuestion);
            return response()->json(['message' => $taskQuestion]);
        }

        foreach ($setTitlePatterns as $pattern) {
            if (preg_match($pattern, strtolower($userMessage))) {
                Session::put('titleMatched', true);
                $taskMessage = preg_replace($pattern, '', strtolower($userMessage));
                $taskMessage = trim($taskMessage);
                Session::put('reminder_task', $taskMessage);
                break;
            }
        }

        foreach ($dayPatterns as $pattern) {
            if (preg_match($pattern, $userMessage, $day)) {
                $dateString = isset($day[0]) ? ucfirst(trim($day[0])) : '';
                $dateString = $this->extractDate($dateString);

                Session::put('dateMatched', true);
                Session::put('reminder_date_question', $dateString);
                Session::put('reminder_date', $dateString);
                break;
            }
        }

        foreach ($timePatterns as $pattern) {
            if (preg_match($pattern, $userMessage, $time)) {
                $timeString = isset($time[0]) ? ucfirst(trim($time[0])) : '';
                $timeString = Carbon::parse($timeString)->format('H:i:s');
                Session::put('timeMatched', true);
                Session::put('reminder_time_question', $timeString);
                Session::put('reminder_time', $timeString);
                break;
            }
        }

        if (Session::get('dateMatched') && Session::get('timeMatched') && Session::get('titleMatched')) {
            $task = Session::get('reminder_task');
            $date = Session::get('reminder_date');
            $time = Session::get('reminder_time');
            $finalMessage = 'Alright, your reminder is set! 📌' . "\n📌 **Task:** " . $task . "\n📅 **Date:** " . $date . "\n⏰ **Time:** " . $time;

            $reminder = new Reminder();
            $reminder->task = $task;
            $reminder->date = $date;
            $reminder->time = $time;
            $reminder->save();

            Session::put('matched', false);
            Session::put('titleMatched', false);
            Session::put('dateMatched', false);
            Session::put('timeMatched', false);

            return response()->json(['message' => $finalMessage, 'reminder' => $reminder]);
        } elseif (Session::get('dateMatched') && Session::get('titleMatched') && !Session::get('timeMatched')) {
            $timeQuestion = 'Could you please specify the time?';
            Session::put('reminder_time_question', $timeQuestion);
            return response()->json(['message' => $timeQuestion]);
        } elseif (Session::get('timeMatched') && Session::get('titleMatched') && !Session::get('dateMatched')) {
            $dateQuestion = 'Could you please specify the day?';
            Session::put('reminder_date_question', $dateQuestion);
            return response()->json(['message' => $dateQuestion]);
        } elseif (Session::get('titleMatched') && !Session::get('dateMatched') && !Session::get('timeMatched')) {
            $dateQuestion = 'Could you please specify the day?';
            return response()->json(['message' => $dateQuestion]);
        } else {
            $response = Gemini::generateText($userMessage);
            return response()->json(['message' => $response]);
        }

    }

    private function extractDate($message, $time = null)
    {
        try {
            $carbonDate = Carbon::parse($message); // Tries to parse the input
            return $carbonDate->toDateString(); // Returns date and time in YYYY-MM-DD HH:MM:SS format (or any format you want)
        } catch (\Exception $e) {
            // Handle parsing errors (e.g., invalid date format)
            return 'No date specified'; // Or return an error message
        }
    }

    private function formatResponse($responseText)
    {
        if (stripos($responseText, 'set a reminder') !== false) {
            $formattedResponse =
                "
            <strong>Reminder Details:</strong><br>
            <ul>
                <li><strong>Task:</strong> " .
                $this->extractTask($responseText) .
                "</li>
                <li><strong>Date:</strong> " .
                $this->extractDate($responseText) .
                "</li>
                <li><strong>Time:</strong> " .
                $this->extractTime($responseText) .
                "</li>
            </ul>
            ";
            return $formattedResponse;
        }

        return $responseText;
    }

    private function extractTask($message)
    {
        $message = strtolower($message);

        $patterns = ['/\bi want to\b/i', '/\bi wanna\b/i', '/\bset a reminder to\b/i', '/\bremind me to\b/i', '/\bplease remind me to\b/i', '/\bcan you remind me to\b/i'];

        $cleanedMessage = preg_replace($patterns, '', $message);
        $cleanedMessage = trim($cleanedMessage);

        return !empty($cleanedMessage) ? ucfirst($cleanedMessage) : 'No task specified';
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
