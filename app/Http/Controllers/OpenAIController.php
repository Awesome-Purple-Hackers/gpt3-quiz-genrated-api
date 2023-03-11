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
            'prompt' => 'I want you to create a quiz consisting of 1 question related to anything I tell you. The quiz should have this structure:[Question x][4 answers, numbered a,b,c,d][The letter of the correct answer]The answers should be as factual and accurate as possible, without ambiguity or falsehoods.Only return the quiz in this format, to be directly compatible with Json Format:[  {    \"question\": \"\",    \"options\": [\"\"],    \"correct\": \"\"  }]Highlight the code with backticksThe subject is math.Do not return anything else, just the json.\n\n{    \"question\": \"What is the value of pi?\",    \"options\": [\"3\",\"3.14\",\"6.28\",\"6\"],    \"correct\": \"b\"  }',
            'temperature' => 0.7,
            'max_tokens' => 256,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
        ];
    
        // Send the request to the OpenAI GPT model.
        $response = $client->request('POST', 'completions', [
            'json' => $requestData,
        ]);
    
        // Return the response in JSON format.
        return response()->json(json_decode($response->getBody()));
    }
    
}
