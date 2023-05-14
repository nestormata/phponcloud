<?php

namespace PHPOnCloud\App\Managers;

use PHPOnCloud\App\Application;
use PHPOnCloud\App\Extensions\TwigCustomExtension;
use Twig\Environment;
use Twig\Extension\CoreExtension;
use Twig\Extension\EscaperExtension;
use Twig\Loader\FilesystemLoader;

class TemplateManager
{
    protected Environment $twig;

    public function __construct(Application $application)
    {
        $loader = new FilesystemLoader($application->getTemplatesPath());
        $this->twig = new Environment($loader, [
            'cache' => $application->getCachePath(),
            'auto_reload' => true,
        ]);
        $this->twig->addExtension(new TwigCustomExtension($application));
    }

    /**
     * Loads the requested template and renders it.
     * @param string $template_name The name of the template.
     * @param array $data The data to be passed to the template.
     * @return string The rendered output.
     */
    public function render(string $template_name, array $data): string
    {
        return $this->twig->render($template_name . '.twig', $data);
    }
}
