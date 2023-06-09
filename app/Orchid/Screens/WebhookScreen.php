<?php

namespace App\Orchid\Screens;

use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Screen;
use Orchid\Screen\Fields\Input;
use Orchid\Support\Facades\Layout;
use App\Models\Webhook;
use Illuminate\Http\Request;
use Orchid\Screen\TD;
use Orchid\Screen\Actions\Button;
use App\Traits\WebhookTrait;
use Auth;

class WebhookScreen extends Screen
{
    use WebhookTrait;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'webhooks' => Webhook::where(['id' => Auth::id()])->latest()->get(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'WhatsHook';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Add WebHook')
                ->modal('webhookModal')
                ->method('create')
                ->icon('plus'),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            Layout::table('webhooks', [
                TD::make('name', __('Name')),

                TD::make('alias', __('URL'))->render(function ($webhook) {
                    return env('APP_URL') . '/' . $webhook->alias;
                }),

                TD::make('route_type', __('Type')),
                
                TD::make('route_value', __('Value')),

                TD::make('Actions')
                ->alignRight()
                ->render(function (Webhook $webhook) {
                    return Button::make('Delete Webhook')
                        ->confirm('After deleting, the webhook will be gone forever.')
                        ->method('delete', ['webhook' => $webhook->id]);
                }),
            ]),

            Layout::modal('webhookModal', Layout::rows([
                Input::make('webhook.name')
                    ->title('Name')
                    ->placeholder('Enter webhook name'),
                    // ->help('The name of the webhook to be created.'),

                Input::make('webhook.route_type')
                    ->type('hidden')
                    ->value('whatsapp'),

                Input::make('webhook.route_value')
                    ->title('WhatsApp Number')
                    ->placeholder('Enter your WhatsApp number'),
            ]))
            ->title('Create your WhatsHook')
            ->applyButton('Add WebHook'),
        ];
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return void
     */
    public function create(Request $request)
    {
        // Validate form data, save webhook to database, etc.
        $request->validate([
            'webhook.name' => 'required|max:255',
            'webhook.route_type' => 'required|max:255',
            'webhook.route_value' => 'required|max:255',
        ]);

        $webhook = new Webhook();
        $webhook->name = $request->input('webhook.name');
        $webhook->route_type = $request->input('webhook.route_type');
        $webhook->route_value = $request->input('webhook.route_value');
        $webhook->alias = $this->generateAlias();
        $webhook->save();
    }

    /**
     * @param WebHook $webhook
     *
     * @return void
     */
    public function delete(WebHook $webhook)
    {
        $webhook->delete();
    }
}
