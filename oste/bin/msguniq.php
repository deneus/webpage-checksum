#!/usr/bin/env php
<?php declare(strict_types=1);

use Gettext\Generator\PoGenerator;
use Gettext\Loader\PoLoader;
use Gettext\Translation;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

include __DIR__ . "/../lib/bootstrap.php";

$inputDefinition = new InputDefinition([
  new InputArgument('input-file', InputArgument::OPTIONAL, "input PO file", '-'),
  new InputOption('output-file', 'o', InputOption::VALUE_REQUIRED, "write output to specified file", '-'),
  new InputOption('help', 'h', InputOption::VALUE_NONE, "display this help and exit"),
  new InputOption('sort-output', 's', InputOption::VALUE_NONE, "generate sorted output")
]);

try {
  $input = new ArgvInput($argv, $inputDefinition);
} catch (RuntimeException $e) {
  echo "Error: " . $e->getMessage() . PHP_EOL . PHP_EOL;
  echo "Usage: {$argv[0]} --help" . PHP_EOL;
  exit(1);
}

if ($input->getOption('help')) {
  echo "Usage: {$argv[0]} [OPTION] [INPUTFILE]" . PHP_EOL;
  echo PHP_EOL;
  echo "A subset of the gettext msguniq tool that's compatible with Drupal's PO output." . PHP_EOL;
  echo PHP_EOL;
  echo "Mandatory arguments to long options are mandatory for short options too." . PHP_EOL;
  echo PHP_EOL;
  echo "Input file location:" . PHP_EOL;
  echo "  INPUTFILE                   input PO file" . PHP_EOL;
  echo "If no input file is given or if it is -, standard input is read." . PHP_EOL;
  echo PHP_EOL;
  echo "Output file location:" . PHP_EOL;
  echo "  -o, --output-file=FILE      write output to specified file" . PHP_EOL;
  echo "The results are written to standard output if no output file is specified" . PHP_EOL;
  echo "or if it is -." . PHP_EOL;
  echo PHP_EOL;
  echo "Output details:" . PHP_EOL;
  echo "  -s, --sort-output           generate sorted output" . PHP_EOL;
  echo PHP_EOL;
  echo "Informative output:" . PHP_EOL;
  echo "  -h, --help                  display this help and exit" . PHP_EOL;
  echo PHP_EOL;
  exit(0);
}

$sort_output = $input->getOption('sort-output');
$output_file = $input->getOption('output-file');
$input_file = $input->getArgument('input-file');

$loader = new PoLoader();

if ($input_file !== "-") {
  if (!is_readable($input_file)) {
    echo "Error: Can't read '$input_file'" . PHP_EOL;
    exit(2);
  }

  $translations = $loader->loadFile($input_file);
}
else {
  $data = "";
  while (false !== ($line = fgets(STDIN))) {
    $data .= $line;
  }

  $translations = $loader->loadString($data);
}

if ($sort_output) {
  // We can't actually sort in place, we can only sort a derivative iterator.
  // We do this on a cloned copy so that we don't lose data if we modify the
  // underlying translations.
  $backup_translations = clone $translations;
  $sorted_iterator = $backup_translations->getIterator();
  // Sort by `msgid` which is "original" in PHP Gettext. The `id` in PHP Gettext
  // includes the context. C Gettext tooling doesn't take context into account
  // when sorting, so we don't want to do here either for cleaner diffs.
  $sorted_iterator->uasort(function (Translation $a, Translation $b) {
    if ($a->getOriginal() === $b->getOriginal()) {
      // If the messages are the same, we want no-context before any-context.
      return $a->getContext() <=> $b->getContext();
    }

    return $a->getOriginal() <=> $b->getOriginal();
  });

  // We can't get all the metadata easily onto a new translations object (we
  // could copy some things with merge but that doesn't get all). It's easier to
  // empty the translations object with the correct metadata and then add the
  // translations in the right order from our sorted iterator.
  foreach ($translations->getIterator() as $translation) {
    $translations->remove($translation);
  }

  foreach ($sorted_iterator as $translation) {
    $translations->add($translation);
  }
}

$generator = new PoGenerator();
if ($output_file !== "-") {
  if (!is_writeable($output_file)) {
    echo "Error: Can't write '$output_file'" . PHP_EOL;
    exit(3);
  }

  $generator->generateFile($translations, $output_file);
}
else {
  echo $generator->generateString($translations) . PHP_EOL;
}
