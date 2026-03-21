<?php

namespace App\Services;

class IntencaoService
{
    /**
     * @param array<int, array<string, mixed>> $intencoes
     * @param array<int, array<string, mixed>> $faqs
     * @return array{intent: array<string,mixed>|null, faq: array<string,mixed>|null, score: float, origem: string}
     */
    public function detectar(string $mensagem, array $intencoes = [], array $faqs = []): array
    {
        $texto = $this->normalizar($mensagem);
        if ($texto === '') {
            return [
                'intent' => null,
                'faq' => null,
                'score' => 0.0,
                'origem' => 'none',
            ];
        }

        $bestIntent = null;
        $bestIntentScore = 0.0;
        foreach ($intencoes as $intencao) {
            $gatilhos = $this->decodeArray($intencao['gatilhos_json'] ?? null);
            if (empty($gatilhos)) {
                continue;
            }
            $score = $this->calcularScore($texto, $gatilhos);
            if ($score > $bestIntentScore) {
                $bestIntentScore = $score;
                $bestIntent = $intencao;
            }
        }

        $bestFaq = null;
        $bestFaqScore = 0.0;
        foreach ($faqs as $faq) {
            $palavras = $this->decodeArray($faq['palavras_chave_json'] ?? null);
            if (empty($palavras)) {
                $palavras = preg_split('/\s+/', $this->normalizar((string) ($faq['pergunta'] ?? ''))) ?: [];
            }
            $score = $this->calcularScore($texto, $palavras);
            if ($score > $bestFaqScore) {
                $bestFaqScore = $score;
                $bestFaq = $faq;
            }
        }

        if ($bestIntentScore >= $bestFaqScore) {
            return [
                'intent' => $bestIntent,
                'faq' => null,
                'score' => $bestIntentScore,
                'origem' => $bestIntent ? 'intent' : 'none',
            ];
        }

        return [
            'intent' => null,
            'faq' => $bestFaq,
            'score' => $bestFaqScore,
            'origem' => $bestFaq ? 'faq' : 'none',
        ];
    }

    /**
     * @param array<int, string> $gatilhos
     */
    private function calcularScore(string $texto, array $gatilhos): float
    {
        if (empty($gatilhos)) {
            return 0.0;
        }

        $pontos = 0.0;
        $maxPontos = 0.0;
        foreach ($gatilhos as $gatilho) {
            $g = $this->normalizar((string) $gatilho);
            if ($g === '') {
                continue;
            }
            $peso = str_contains($g, ' ') ? 2.0 : 1.0;
            $maxPontos += $peso;
            if (str_contains($texto, $g)) {
                $pontos += $peso;
            }
        }

        if ($maxPontos <= 0) {
            return 0.0;
        }

        $score = $pontos / $maxPontos;
        return max(0.0, min(1.0, $score));
    }

    /**
     * @return array<int, string>
     */
    private function decodeArray($raw): array
    {
        if (is_array($raw)) {
            return array_values(array_filter(array_map('strval', $raw)));
        }
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        return array_values(array_filter(array_map('strval', $decoded)));
    }

    private function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto), 'UTF-8');
        if ($texto === '') {
            return '';
        }

        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        if ($ascii !== false) {
            $texto = $ascii;
        }

        $texto = preg_replace('/[^a-z0-9\s]/', ' ', $texto) ?? $texto;
        $texto = preg_replace('/\s+/', ' ', $texto) ?? $texto;
        return trim($texto);
    }
}
