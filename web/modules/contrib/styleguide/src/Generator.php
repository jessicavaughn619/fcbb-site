<?php

namespace Drupal\styleguide;

use Drupal\Component\Utility\Random;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class Generator.
 *
 * @package Drupal\styleguide
 */
class Generator implements GeneratorInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The utility class for creating random data.
   *
   * @var \Drupal\Component\Utility\Random
   */
  protected $random;

  /**
   * The page manager service.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pagerManager;

  /**
   * The configuration entry point.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Constructor.
   */
  public function __construct(RequestStack $request_stack, PagerManagerInterface $page_manager, ConfigFactoryInterface $config) {
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->random = new Random();
    $this->pagerManager = $page_manager;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function wordList($size = 5, $words = 3) {
    $items = [];
    for ($i = 0; $i < $size; $i++) {
      $items[] = $this->words($words, 'ucfirst');
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function words($size = 1, $case = 'strtolower') {
    $words = [];
    for ($i = 0; $i < $size; $i++) {
      $words[] = $this->random->word(rand(4, 12));
    }
    $words = implode(' ', $words);
    $functions = ['ucfirst', 'ucwords', 'strtoupper', 'strtolower'];
    if (!is_null($case) && function_exists($case) && in_array($case, $functions)) {
      $words = $case($words);
    }
    return $words;
  }

  /**
   * {@inheritdoc}
   */
  public function tableHeader($size = 5) {
    $header = $this->wordList($size);
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function tableRows($size = 5) {
    $rows = [];
    for ($i = 0; $i < $size; $i++) {
      $rows[] = $this->wordList($size);
    }
    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function lorem($size = 5, $words = 0, $case = 'mixed', $returns = TRUE, $punctuation = TRUE, $array = FALSE) {
    $text = $this->random->paragraphs($size, TRUE);
    if (!$punctuation) {
      $text = str_replace([',', '.'], '', $text);
    }
    switch ($case) {
      case 'mixed':
        break;

      case 'upper':
        $text = strtoupper($text);
        break;

      case 'lower':
        $text = strtolower($text);
        break;
    }
    $graphs = explode("\n\n", $text);
    $text = array_slice($graphs, 0, $size);
    $spacer = ' ';
    if ($returns) {
      $spacer = "\n\n";
    }
    if ($words > 0) {
      $elements = explode(' ', implode(' ', $text));
      $output = [];
      for ($i = 0; $i < $words; $i++) {
        $val = array_rand($elements);
        $output[] = $elements[$val];
      }
      return implode(' ', $output);
    }
    if (!$array) {
      return implode($spacer, $text);
    }
    return $text;
  }

  /**
   * {@inheritdoc}
   */
  public function paragraphs($size = 5, $render = FALSE) {
    $text = $this->lorem($size, 0, 'mixed', TRUE, TRUE, TRUE);
    $output = [];
    foreach ($text as $item) {
      $output[] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => trim($item),
      ];
    }

    return $render ? \Drupal::service('renderer')->render($output) : $output;
  }

  /**
   * {@inheritdoc}
   */
  public function image($image = 'vertical', $type = 'jpg', $min_resolution = '240x320', $max_resolution = '240x320') {
    $file_path = $this->config->get('system.file')
      ->get('default_scheme') . '://styleguide-image-' . $image . '.' . $type;

    if ($image == 'horizontal') {
      $min_resolution = $max_resolution = '320x240';
    }

    $file_path = $this->random->image($file_path, $min_resolution, $max_resolution);

    return file_exists($file_path) ? $file_path : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function sentence($link = FALSE) {
    $sentence = $this->random->sentences(10);
    if ($link) {
      $words = explode(' ', $sentence);
      $link_word_count = random_int(2, floor(count($words) / 2));
      $link_words = array_slice($words, 0, $link_word_count);
      $link = [
        '#type' => 'link',
        '#title' => implode(' ', $link_words),
        '#url' => $link,
      ];
      $rendered_link = \Drupal::service('renderer')->renderPlain($link);
      array_splice($words, 0, $link_word_count, [$rendered_link]);
      $sentence = implode(' ', $words);
    }
    return Markup::create($sentence);
  }

  /**
   * {@inheritdoc}
   */
  public function pager($size = 8, $total = 20) {
    $this->pagerManager->createPager($total, $size)->getCurrentPage();
    return ['#type' => 'pager'];
  }

  /**
   * {@inheritdoc}
   */
  public function links($url, $size = 4) {
    $links = [];
    for ($i = 0; $i < 5; $i++) {
      $links[] = [
        'title' => $this->words(3),
        'url' => Url::fromUserInput($url),
      ];
    }
    return $links;
  }

  /**
   * {@inheritdoc}
   */
  public function menuItem($url) {
    $menu_item = [
      '#type' => 'link',
      '#title' => $this->sentence(),
      '#url' => Url::fromUserInput($url),
    ];
    return $menu_item;
  }

  /**
   * {@inheritdoc}
   */
  public function ulLinks() {
    $links = [];

    for ($i = 0; $i <= 10; $i++) {
      $word = $this->words();
      $links[$word] = [
        'title' => $word,
        'url' => Url::fromUserInput($this->currentRequest->getRequestUri()),
        'fragment' => 'ul_links',
      ];
    }

    return $links;
  }

}
