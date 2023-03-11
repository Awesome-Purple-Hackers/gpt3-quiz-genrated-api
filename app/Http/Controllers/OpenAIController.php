<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class OpenAIController extends Controller
{
/**
 * Send a request to the OpenAI GPT model and return the response as an array of JSON objects.
 *
 * @param int $numQuizzes The number of quizzes to generate.
 * @return array
 */
public function sendRequest($numQuizzes = 1)
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
            'prompt' => 'I want you to create a quiz consisting of 1 question related to anything I tell you. The quiz should have this structure: [Question x][4 answers, numbered a,b,c,d][The letter of the correct answer] The answers should be as factual and accurate as possible, without ambiguity or falsehoods. Only return the quiz in exactly this format: {question:"", answer1:"", answer2:"", answer3:"", answer4:"", correct_answer:""}. Do not modify the format just fill between each "" of each element with the correct values.  The subject is math. Do not return anything else, just the json.\n\n',
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

        // Loop through the quizzes and create a new JSON object for each one.
        foreach ($quizzes as $quiz) {
            // Extract the quiz values from the string.
            preg_match_all('/"([^"]*)"/', $quiz, $matches);

            // Create a new JSON object for the quiz.
            $quizObject = [
                'question' => $matches[1][0],
                'answer1' => $matches[1][1],
                'answer2' => $matches[1][2],
                'answer3' => $matches[1][3],
                'answer4' => $matches[1][4],
                'correct_answer' => $matches[1][5],
            ];

            // Add the quiz object to the array.
            $quizObjects[] = $quizObject;
        }

        // If the number of quizzes requested is greater than 1, return a random sample of that many quizzes.
        if ($numQuizzes > 1) {
            $quizObjects = array_rand($quizObjects, $numQuizzes);
        }

        // Return the array of quiz objects.
        return $quizObjects;
    }

    public function getQuizzes(Request $request)
    {
        // Call the `sendRequest` method to generate the quizzes.
        $quizzes = $this->sendRequest();

        // Return the quizzes as a JSON response with a 200 status code.
        return response()->json($quizzes, 200);
    }
}
