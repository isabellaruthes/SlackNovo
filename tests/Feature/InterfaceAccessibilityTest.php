<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * SLK-09 — accessibility basics found by the axe-core audit of 12/11/2026:
 * dashboard filters without accessible names, low-contrast logout button and no skip link.
 */
class InterfaceAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::create(['nome' => 'operador', 'senha' => Hash::make('secret')]));
    }

    public function test_every_dashboard_filter_has_an_associated_label(): void
    {
        $xpath = $this->xpath($this->get(route('dashboard'))->assertOk()->getContent());

        foreach (['q', 'status', 'categoria', 'tamanho'] as $name) {
            $control = $xpath->query(sprintf('//form[@method="GET"]//*[@name="%s"]', $name))->item(0);
            $this->assertInstanceOf(DOMElement::class, $control, "Filter {$name} not found");

            $id = $control->getAttribute('id');
            $this->assertNotSame('', $id, "Filter {$name} has no id");
            $label = $xpath->query(sprintf('//label[@for="%s"]', $id))->item(0);
            $this->assertInstanceOf(DOMElement::class, $label, "Filter {$name} has no label");
            $this->assertNotSame('', trim($label->textContent));
        }
    }

    public function test_the_logout_button_uses_a_high_contrast_style_on_the_dark_navbar(): void
    {
        $xpath = $this->xpath($this->get(route('dashboard'))->assertOk()->getContent());
        $button = $xpath->query('//nav//form[contains(@action, "logout")]//button')->item(0);

        $this->assertInstanceOf(DOMElement::class, $button);
        $this->assertStringContainsString('btn-outline-light', $button->getAttribute('class'));
        $this->assertStringNotContainsString('btn-outline-danger', $button->getAttribute('class'));
    }

    public function test_pages_offer_a_skip_link_to_the_main_content(): void
    {
        foreach ([route('dashboard'), route('admin.produtos.index'), route('catalog.public')] as $url) {
            $xpath = $this->xpath($this->get($url)->assertOk()->getContent());

            $link = $xpath->query('//body//a[@href="#conteudo"]')->item(0);
            $this->assertInstanceOf(DOMElement::class, $link, "Skip link missing on {$url}");
            $this->assertSame(1, $xpath->query('//*[@id="conteudo"]')->length, "Target missing on {$url}");
        }
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);

        return new DOMXPath($document);
    }
}
