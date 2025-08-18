<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class GeneralNotification extends Notification
{
    use Queueable;

    private array $data;

    /**
     * The function constructs a data array for a notification with default values for optional parameters.
     *
     * @param string $username The username that is shown in the email notification
     * @param string $subject The `subject` parameter in the constructor function is used to set the subject of the
     * notification. If a value is provided for `subject`, it will be used; otherwise, the default value 'New notification'
     * will be used.
     * @param string $titel The `title` parameter is the main title of the notification.
     * @param string $message The `message` parameter in the constructor function is used to set the main content or body of
     * the notification message. It allows you to provide the specific message content that you want to include in the
     * notification being constructed. If no message is provided, the default value will be set to `null`.
     * @param string $details_title The `details_title` parameter in the constructor function is used to set the title for
     * additional details in a notification. It is part of the data array that is being constructed to create a
     * notification object. If provided, it will be included in the notification data under the 'details' section with the
     * key
     * @param string $details_title_hint The parameter `details_title_hint` in the constructor function is used to provide a
     * hint or additional information related to the title of the details section in the notification. It can be used to
     * give users a better understanding of what the details section entails or to provide context for the title.
     * @param string $details_message The `details_message` parameter in the constructor function is used to set the message
     * content for the details section of the notification. This message can provide additional information or context
     * related to the notification being sent to the user. It allows you to include more detailed information along with
     * the main message of the notification.
     * @param string $details_link The `details_link` parameter in the constructor function is used to specify a link
     * associated with the notification details. It is part of the `details` array which contains additional information
     * related to the notification being constructed. This link could be a URL pointing to more detailed information or an
     * action that the user can. If no `details_link` is provided, the link button does not appear
     * @param string $details_link_title The `details_link_title` parameter in the constructor function is used to specify
     * the title or label for a link provided in the notification details. This title will be displayed alongside the link
     * in the notification message. If no `details_link_title` is provided, the link button does not appear,
     * @param int $footer_year The `footer_year` parameter in the constructor function is used to specify the year that will
     * be displayed in the footer of the notification. If a value is provided for `footer_year`, that value will be used;
     * otherwise, the current year (obtained using `Carbon::now()->year`)
     * @param string $footer_name The `footer_name` parameter in the constructor function is used to set the name that will
     * appear in the footer of the notification. If a value is provided for `footer_name`, it will be used as the name in
     * the footer. Otherwise, it will default to the value retrieved from the application configuration
     */
    public function __construct(string $username, ?string $subject = null, ?string $titel = null, ?string $message = null, ?string $details_title = null, ?string $details_title_hint = null, ?string $details_message = null, ?string $details_link = null, ?string $details_link_title = null, ?int $footer_year = null, ?string $footer_name = null)
    {
        $transfer_data = [
            'data' => [
                'subject' => $subject ? $subject : 'New notification',
                'username' => $username,
                'title' => $titel,
                'message' => $message,
                'details' => [
                    'title' => $details_title,
                    'title_hint' => $details_title_hint,
                    'message' => $details_message,
                    'link' => $details_link,
                    'link_title' => $details_link_title
                ],
                'footer' => [
                    'year' => $footer_year ? $footer_year : Carbon::now()->year,
                    'name' => $footer_name ? $footer_name : config('app.name')
                ]
            ]
        ];

        $this->data = $transfer_data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->data['data']['subject'])
            ->view('emails.GeneralNotification', $this->data);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->data;
    }
}
