<?php

namespace App\Orchid\Screens;

use Auth;
use App\Models\User;
use Orchid\Screen\TD;
use App\Models\Webhook;
use Orchid\Screen\Screen;
use Orchid\Screen\Fields\Input;
use Orchid\Support\Facades\Layout;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use App\Traits\WebhookTrait;
use Orchid\Screen\Actions\Link;
use Orchid\Support\Facades\Alert;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Toast;
use Orchid\Screen\Actions\ModalToggle;

class WebhookScreen extends Screen
{
    use WebhookTrait;

    private $webhooks;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        $webhooks = Webhook::where(['user_id' => Auth::id()])->latest()->get();

        $this->webhooks = $webhooks;

        return [
            'webhooks' => $webhooks,
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
        $canAddWebHooks = false;
        if( $this->webhooks && (count($this->webhooks) < 1 || Auth::user()->subscribed()) ){
            $canAddWebHooks = true;
        }

        return [
            ModalToggle::make('Add WebHook')
                ->modal('webhookModal')
                ->method('create')
                ->icon('plus')
                ->class('btn btn-link add-webhook')
                ->canSee($canAddWebHooks),

            Link::make('Billing')
                ->href('/billing')
                ->icon('credit-card')
                ->help('Subscribe to create unlimited WhatsHooks.')
                ->canSee(!$canAddWebHooks),
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

                TD::make('status', __('Status'))->render(function ($webhook) {
                    return !$webhook->status ? 'Pending Validation, click on the <br> link sent to WhatsApp (' . $webhook->route_value . ') .' : 'Active';
                }),

                TD::make('alias', __('WebHook URL'))->render(function ($webhook) {
                    return env('APP_URL') . '/' . $webhook->alias;
                }),

                TD::make('route_type', __('Channel')),

                TD::make('route_value', __('Value')),

                TD::make('', __('Notifications'))->render(function ($webhook) {
                    return !Auth::user()->subscribed() ? '100 / month' : 'Unlimited';
                }),

                TD::make('custom_message', '')
                ->render(fn (Webhook $webhook) => ModalToggle::make(__('Custom Message'))
                    ->modal('asyncEditCustomMessageModal')
                    ->modalTitle('Custom Message from ' . $webhook->name)
                    ->method('update')
                    ->asyncParameters([
                        'webhook' => $webhook->id,
                ])),

                TD::make('')
                ->alignRight()
                ->render(function (Webhook $webhook) {
                    return Button::make('Delete')
                        ->confirm('After deleting, the webhook will be gone forever.')
                        ->method('delete', ['webhook' => $webhook->id]);
                }),
            ]),

            Layout::modal('asyncEditCustomMessageModal', Layout::rows([
                Textarea::make('webhook.custom_message')
                    ->title('Custom Message')
                    ->rows(5)
                    ->placeholder('Hello, new identified payment from {customer.name} in the amount of {amount}')
                    ->help("Here you can configure a custom message to replace the received default payload. Here's how to set the variables: {json_key}"),
            ]))
            ->title('Update your WhatsHook')
            ->applyButton('Save')
            ->async('asyncGetWebhook'),

            Layout::modal('webhookModal', Layout::rows([
                Input::make('webhook.name')
                    ->title('Name')
                    ->placeholder('Enter webhook name'),
                    // ->help('The name of the webhook to be created.'),

                Input::make('webhook.route_type')
                    ->type('hidden')
                    ->value('WhatsApp'),

                Input::make('webhook.phone_number')
                    ->type('hidden'),

                Input::make('webhook.route_value')
                    ->title('WhatsApp Number')
                    ->class('route_value form-control')
                    // ->mask([
                    //     'numericInput' => true
                    // ])
                    //->type('number')
                    //->placeholder('Ex: 55 11 9999 9999')
                    ->help('You will receive a link on WhatsApp informed for activation.'),
            ]))
            ->title('Create your WhatsHook')
            ->applyButton('Add WebHook'),
        ];
    }

    /**
     * @return array
     */
    public function asyncGetWebhook(Webhook $webhook): iterable
    {
        return [
            'webhook' => $webhook,
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

        $route_value = $request->input('webhook.route_value');
        if($request->input('webhook.route_type') == 'WhatsApp'){
            $route_value = $request->input('webhook.phone_number');
        }

        $webhook = new Webhook();
        $webhook->name = $request->input('webhook.name');
        $webhook->route_type = $request->input('webhook.route_type');
        $webhook->route_value = $route_value;
        $webhook->alias = $this->generateAlias();
        $webhook->save();

        $this->sendWhatsAppLinkVerification($webhook);

        Toast::info(__('Saved successfully.'));
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

    /**
     * @param WebHook $webhook
     *
     * @return void
     */
    public function update(Request $request, WebHook $webhook)
    {
        $request->validate([
            'webhook.custom_message' => 'required',
        ]);

        $webhook->custom_message = $request->input('webhook.custom_message');

        $webhook->save();

        Toast::info(__('Custom message saved successfully.'));
    }
}
