<?php

$width = 10;
$height = 6;

$symbolProbabilities = [
    'Q' => 0.25,
    'K' => 0.15,
    'J' => 0.5,
    'A' => 0.1
];

$winMultipliers = [
    'Q' => 5,
    'K' => 10,
    'J' => 15,
    'A' => 25
];

class SlotMachine
{
    private int $width;
    private int $height;
    private array $symbols;
    private array $winMultipliers;

    public function __construct(int $width, int $height, array $symbolProbabilities, array $winMultipliers)
    {
        $this->width = $width;
        $this->height = $height;
        $this->winMultipliers = $winMultipliers;
        $this->symbols = $this->generateSymbols($symbolProbabilities);
    }

    private function generateSymbols(array $symbolProbabilities): array
    {
        $symbols = [];
        foreach ($symbolProbabilities as $symbol => $probability) {
            $count = (int)($probability * 100);
            $symbols = array_merge($symbols, array_fill(0, $count, $symbol));
        }
        shuffle($symbols);
        return $symbols;
    }

    public function spin(): array
    {
        $board = [];
        for ($i = 0; $i < $this->height; $i++) {
            $board[] = array_map(fn() => $this->symbols[array_rand($this->symbols)], range(1, $this->width));
        }
        return $board;
    }

    public function displayBoard(array $board, array $winningLines): void
    {
        echo "Board:" . PHP_EOL;
        foreach ($board as $i => $row) {
            foreach ($row as $j => $symbol) {
                echo in_array([$i, $j], $winningLines) ? "\033[0;32m$symbol\033[0m " : "$symbol ";
            }
            echo PHP_EOL;
        }
    }

    public function calculateWinningLines(array $board): array
    {
        $winningLines = [];
        for ($i = 0; $i < $this->height; $i++) {
            if (count(array_unique($board[$i])) === 1) {
                for ($j = 0; $j < $this->width; $j++) {
                    $winningLines[] = [$i, $j];
                }
            }
        }
        for ($j = 0; $j < $this->width; $j++) {
            $column = array_column($board, $j);
            if (count(array_unique($column)) === 1) {
                for ($i = 0; $i < $this->height; $i++) {
                    $winningLines[] = [$i, $j];
                }
            }
        }
        return $winningLines;
    }

    public function calculateWinAmount(array $board, float $betAmount): float
    {
        $winAmount = 0;
        foreach ($this->winMultipliers as $symbol => $multiplier) {
            foreach ($board as $row) {
                if (count(array_unique($row)) === 1 && $row[0] === $symbol) {
                    $winAmount += $multiplier * $betAmount;
                }
            }
            for ($j = 0; $j < $this->width; $j++) {
                $column = array_column($board, $j);
                if (count(array_unique($column)) === 1 && $column[0] === $symbol) {
                    $winAmount += $multiplier * $betAmount;
                }
            }
        }
        return $winAmount;
    }
}

class Game
{
    private SlotMachine $slotMachine;
    private float $coins;
    private float $betAmount;
    private string $colorGreen = "\033[0;32m";
    private string $colorReset = "\033[0m";

    public function __construct(int $width, int $height, array $symbolProbabilities, array $winMultipliers)
    {
        $this->slotMachine = new SlotMachine($width, $height, $symbolProbabilities, $winMultipliers);
    }

    public function start(): void
    {
        $this->coins = $this->getPositiveNumber("Enter the starting amount of virtual coins: ");
        $this->betAmount = $this->getPositiveNumber("Enter the bet amount per single spin: ");

        while ($this->coins >= $this->betAmount) {
            $board = $this->slotMachine->spin();
            $winningLines = $this->slotMachine->calculateWinningLines($board);
            $this->slotMachine->displayBoard($board, $winningLines);

            $winAmount = $this->slotMachine->calculateWinAmount($board, $this->betAmount);

            if ($winAmount > 0) {
                echo $this->colorGreen . "Congratulations! You won!" . $this->colorReset . PHP_EOL;
            }

            $this->coins += $winAmount - $this->betAmount;

            echo "Win Amount: $winAmount" . PHP_EOL;
            echo "Coins Left: $this->coins" . PHP_EOL . PHP_EOL;

            if ($this->coins < $this->betAmount) {
                echo "Game over! You ran out of coins.";
                break;
            } else {
                $input = strtolower(readline("Continue? "));
                if (trim($input) !== '') {
                    echo "Bye! You have $this->coins coins left." . PHP_EOL;
                    break;
                }
            }
        }
    }

    private function getPositiveNumber(string $prompt): float
    {
        do {
            $number = readline($prompt);
            if (is_numeric($number) && $number > 0) {
                return (float)$number;
            }
            echo "Invalid input. Please enter a valid positive number." . PHP_EOL;
        } while (true);
    }
}

$game = new Game($width, $height, $symbolProbabilities, $winMultipliers);
$game->start();
