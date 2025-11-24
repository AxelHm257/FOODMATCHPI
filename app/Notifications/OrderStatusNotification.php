<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Order;

class OrderStatusNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order, public string $title, public string $message)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->greeting('Hola '.$notifiable->name)
            ->line($this->message)
            ->line('Pedido #'.$this->order->id.' — Estado: '.ucfirst($this->order->status))
            ->action('Ver mis pedidos', url(route('orders.index')));
    }

    public function toArray($notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'status' => $this->order->status,
            'title' => $this->title,
            'message' => $this->message,
        ];
    }
}

