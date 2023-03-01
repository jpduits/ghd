<?php

namespace App\Traits;

use Symfony\Component\Console\Output\OutputInterface;

trait Terminal
{

    /**
     * @param string $message
     * @param string|null $style
     * @return void
     */
    public function writeToTerminal(string $message, string $style = null) : void
    {

        if ($this->output instanceof OutputInterface === false) {
            return;
        }

        $styles = [
            'info-green' => '<options=bold;fg=green>',
            'info-yellow' => '<options=bold;fg=yellow>',
            'info-red' => '<options=bold;fg=red>',
            'info' => '<options=bold>',
            'comment' => '<fg=black;bg=blue>',
            'question' => '<fg=black;bg=green>',
            'error' => '<fg=black;bg=red>',
            'warning' => '<fg=black;bg=yellow>',
        ];

        if (array_key_exists($style, $styles)) {
            $message = $styles[$style] . $message . '</>';
        }
        $this->output->writeLn($message, OutputInterface::OUTPUT_NORMAL);
    }

    public function writeHorizontalLineToTerminal($length = 80, $style = null) : void
    {
        $this->writeToTerminal(str_repeat('-', $length), $style);
    }

}
