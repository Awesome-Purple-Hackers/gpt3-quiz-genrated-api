<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Models\Quiz;


class OpenAIController extends Controller
{
/**
 * Send a request to the OpenAI GPT model and return the response as an array of JSON objects.
 *
 * @param int $numQuizzes The number of quizzes to generate.
 * @return array
 */
public function sendRequest($numQuizzes = 1, $saveToDb = true)
{
    // Set up the Guzzle client with the appropriate base URI and authentication headers.
    $client = new Client([
        'base_uri' => 'https://api.openai.com/v1/',
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . env('OPENAI_API_SECRET'),
        ],
    ]);

    // Set up the request data.
    $requestData = [
        'model' => 'text-davinci-003',
        'prompt' => 'I want you to create a quiz consisting of 1 question related to anything I tell you. The quiz should have this structure: [Question x][4 answers, numbered a,b,c,d][The letter of the correct answer] The answers should be as factual and accurate as possible, without ambiguity or falsehoods. Only return the quiz in exactly this format: {question:"", answers:["", "", "", ""], correct_answer:""}. Do not modify the format just fill between each "" of each element with the correct values. The subject is math. Do not return anything else, just the list.',
        'temperature' => 0.7,
        'max_tokens' => 656,
        'top_p' => 1,
        'frequency_penalty' => 0,
        'presence_penalty' => 0,
    ];

    // Send the request to the OpenAI API using the Guzzle client.
    $response = $client->post('completions', [
        'json' => $requestData,
    ]);

    // Get the response body.
    $responseBody = json_decode($response->getBody(), true);

    // Extract the generated quiz from the response body.
    $generatedQuiz = $responseBody['choices'][0]['text'];

    // Split the generated quiz string into separate quizzes.
    $quizzes = explode('{', $generatedQuiz);

    // Remove the first element of the quizzes array (which is an empty string).
    array_shift($quizzes);

    // Create a new array to store the JSON objects for each quiz.
    $quizObjects = [];

    // Initialize the $quizModel variable with a default value of null.
    $quizModel = null;

    // Loop through the quizzes and create a new JSON object for each one.
    foreach ($quizzes as $quiz) {
        // Extract the quiz values from the string.
        preg_match_all('/"([^"]*)"/', $quiz, $matches);

        // Check if a quiz with the same question already exists in the database.
        $existingQuiz = Quiz::where('question', $matches[1][0])->first();

        // If a quiz with the same question already exists, skip saving the quiz to avoid duplicates.
        if (!$existingQuiz) {
            // Create a new quiz object and save it to the database.
        $quizModel = Quiz::create([
            'question' => $matches[1][0],
            'options' => [
            [
            'answer' => $matches[1][1],
            'isCorrect' => $matches[1][5] === 'a',
            ],
            [
            'answer' => $matches[1][2],
            'isCorrect' => $matches[1][5] === 'b',
            ],
            [
            'answer' => $matches[1][3],
            'isCorrect' => $matches[1][5] === 'c',
            ],
            [
            'answer' => $matches[1][4],
            'isCorrect' => $matches[1][5] === 'd',
            ],
            ],
            'correct_answer' => $matches[1][5],
            ]);
            }
                // Create a new JSON object for the quiz if $quizModel is not null.
                if ($quizModel !== null) {
                    $quizObject = [
                        'question' => $quizModel->question,
                        'options' => $quizModel->options,
                    ];

                    // Add the quiz object to the array.
                    $quizObjects[] = $quizObject;
                }
            }

            // If the number of quizzes requested is greater than 1, return a random sample of that many quizzes.
            if ($numQuizzes > 1) {
                $quizObjects = array_rand($quizObjects, $numQuizzes);
            }

        // Return the array of quiz objects.
        return $quizObjects;
        } // <-- Add this closing curly brace

        public function getQuizzes(Request $request)
        {
            // Get the $quizDomain parameter from the request query parameters.
            $quizDomain = $request->query('quiz_domain');

            // Retrieve the quizzes from the database.
            $quizzes = $this->sendRequest(1);

            // If the $quizDomain parameter is set, filter the quizzes by domain.
            if ($quizDomain) {
                $quizzes = collect($quizzes)->filter(function ($quiz) use ($quizDomain) {
                    return stripos($quiz['question'], $quizDomain) !== false;
                })->toArray();
            }
            // If the number of quizzes requested is greater than 1, return a random sample of that many quizzes.
            $numQuizzes = $request->query('num_quizzes', 1);
            if ($numQuizzes > 1) {
                $quizzes = collect($quizzes)->random($numQuizzes)->toArray();
            }

            // Return the quizzes as a JSON response with a 200 status code.
            return response()->json($quizzes, 200);
        }       
}