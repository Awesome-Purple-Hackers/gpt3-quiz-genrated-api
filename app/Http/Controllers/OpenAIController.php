<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class OpenAIController extends Controller
{
    /**
     * Send a request to the OpenAI GPT model and return the response in JSON format.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendRequest()
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
            'prompt' => 'I want you to create a quiz consisting of 1 question related to anything I tell you. The quiz should have this structure: [Question x][4 answers, numbered a,b,c,d][The letter of the correct answer] The answers should be as factual and accurate as possible, without ambiguity or falsehoods. Only return the quiz in this format, to be directly compatible with no-compressed Json Format: [ { \"question\": \"\", \"options\": [\"\"], \"correct\": \"\" } ] Highlight the code with backticks. The subject is math. Do not return anything else, just the json.\n\n',
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

        // Parse the quiz into individual question, options, and correct answer variables.
        preg_match('/\[ \{ \"question\": \"(.+)\", \"options\": \[(.+)\], \"correct\": \"(.+)\" \} \]/s', $generatedQuiz, $matches);

        if (isset($matches[1], $matches[2], $matches[3])) {
            $question = $matches[1];
            $options = explode(', ', $matches[2]);
            $correctAnswer = $matches[3];

            // Compile the variables into a new JSON response.
            $newJsonResponse = [
                'question' => $question,
                'options' => $options,
                'correct_answer' => $correctAnswer,
            ];

            // Return the new JSON response.
            return response()->json($newJsonResponse);
        } else {
            // Handle the case where the expected keys do not exist.
            return response()->json(['error' => 'Unable to parse quiz.']);
        }
    }
}
