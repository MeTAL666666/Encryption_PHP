<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class MainController extends AbstractController
{
    public function index(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $wordCode = $request->request->get('code');
                $mask = $request->request->get('mask');
                $result = $this->createWords($wordCode, ['mask' => $mask]);

                if ($wordCode && $mask) {
                    $result = $this->createWords($wordCode, ['mask' => $mask]);
                    $flag = 0;

                    foreach ($result[1] as $word) {
                        if (mb_strlen($word) <= count($result[0])) {
                            $word = mb_str_split(mb_strtolower($word));
                            $flag = 1;
                            $isValid = true;
                            foreach ($result[0] as $key => $charSet) {
                                $char = isset($word[$key]) ? $word[$key] : '';
                                if (!in_array($char, $charSet)) {
                                    $isValid = false;
                                    break;
                                }
                            }
                            if ($isValid) {
                                return new JsonResponse([
                                    'output' => $word,
                                    'word_code' => $wordCode,
                                    'mask' => $mask,
                                ]);
                            }
                        }
                    }
                    
                    if ($flag == 0) {
                        return new JsonResponse(['output' => 'Не найдено подходящих слов!']);
                    }
                }
            } catch (\Throwable $e) {
                $letters = $request->request->get('letters');
                return new JsonResponse(['keyword' => $this->getKeyword($letters)]);
            }
            
            return new JsonResponse(['error' => 'Missing data!']);
        }
        
        return $this->render('index.html.twig');
    }

    private function createWords($wordCode, $mask)
    {
        $code = [
            '1' => ['а', 'в', 'г'],
            '2' => ['е', 'и', 'к'],
            '3' => ['л', 'н', 'о'],
            '4' => ['п', 'р', 'с'],
            '5' => ['т', 'у', 'щ'],
            '6' => ['ь', 'я', ''],
        ];
        
        $url = "https://poncy.ru/crossword/crossword-solve.jsn";

        $headers = [
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36' .
                ' (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36',
        ];

        $session = new \GuzzleHttp\Client(['verify' => false ]);
        // $session->setDefaultOption('verify', false);

        $response = $session->get($url, ['query' => $mask, ]);
        $content = (string) $response->getBody();

        $data = json_decode($content, true);
        $receivedWords = array_map('strtolower', $data['words']);

        return [array_map(function($e) use ($code) {
            return $code[$e];
        }, str_split($wordCode)), $receivedWords];
    }

    private function getKeyword($letters)
    {
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36' .
                ' (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36',
        ];
        
        $url = "https://sanstv.ru/find_words/word-" . $letters . "/strong-2";

        // echo $url;
        
        $session = new \GuzzleHttp\Client(['verify' => false ]);
        // $session->setDefaultOption('verify', false);
        
        $response = $session->get($url, ['headers' => $headers]);
        $content = (string) $response->getBody();

        // echo $content;

        $match = [];
        preg_match_all('/<a class=\'bold\'.+>(.+)<\/a>/mu', $content, $match);

        $keyword = count($match) ? $match[1][0] : null;

        return $keyword;
    }
}