<?php

namespace App\Http\Livewire;

use Aws\Textract\TextractClient;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;
use OpenAI\Laravel\Facades\OpenAI;

class IrsOcr extends Component
{
    use WithFileUploads;

    /** @var \Livewire\TemporaryUploadedFile */
    public $image;

    public string $summary = '';

    public string $contents = '';

    public function render(): View
    {
        return view('livewire.irs-ocr');
    }

    public function updatedImage(): void
    {
        $this->validate([
            'image' => 'image|max:1024', // 1MB Max
        ]);

        $this->contents = $this->parseImageText();

        $response = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => "given the user's input, output in markdown three sections, who it's from, a short summary (1 paragraph), and if any actions are necessary (as bullet points)"],
                ['role' => 'user', 'content' => $this->contents],
            ],
        ]);

        $this->summary = $response->choices[0]->message->content;
    }

    protected function parseImageText(): string
    {
        // Process the image here
        $client = App::make('aws')->createClient('Textract');
        assert($client instanceof TextractClient);

        $contents = $this->image->get();

        $options = [
            'Document' => [
                'Bytes' => $contents,
            ],
            'FeatureTypes' => ['FORMS'], // REQUIRED
        ];

        $return = '';

        $result = $client->analyzeDocument($options);

        $blocks = $result['Blocks'];
        assert(is_array($blocks));
        // Loop through all the blocks:
        foreach ($blocks as $key => $value) {
            if (isset($value['BlockType']) && $value['BlockType']) {
                $blockType = $value['BlockType'];
                if (isset($value['Text']) && $value['Text']) {
                    $text = $value['Text'];
                    if ($blockType == 'WORD') {
                        // $return .= $text;
                    } elseif ($blockType == 'LINE') {
                        $return .= $text.PHP_EOL;
                    }
                }
            }
        }

        return $return;
    }
}
