<?php

namespace App\Notifications\Expense;

use App\Channels\FirebaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\HRCore\app\Models\ExpenseRequest;

class NewExpenseRequest extends Notification implements ShouldQueue
{
  use Queueable;

  private ExpenseRequest $request;
  private string $title;
  private string $message;

  /**
   * Create a new notification instance.
   */
  public function __construct(ExpenseRequest $request)
  {
    $this->request = $request;
    $this->title = __('New Expense Request');
    $this->message = __('New expense request from :employee for :amount', [
      'employee' => $request->user->getFullName(),
      'amount' => $request->currency . ' ' . number_format($request->amount, 2)
    ]);
  }

  /**
   * Get the notification's delivery channels.
   */
  public function via(object $notifiable): array
  {
    $channels = ['database'];
    
    // Check user notification preferences
    $preferences = $notifiable->notification_preferences->preferences ?? [];
    $hrPrefs = $preferences['hrcore'] ?? [];
    $expensePrefs = $hrPrefs['expense'] ?? [];
    
    if ($expensePrefs['email'] ?? true) {
      $channels[] = 'mail';
    }
    
    if ($expensePrefs['push'] ?? true) {
      $channels[] = FirebaseChannel::class;
    }
    
    return $channels;
  }

  /**
   * Get the mail representation of the notification.
   */
  public function toMail(object $notifiable): MailMessage
  {
    $actionUrl = route('hrcore.expenses.show', $this->request->id);
    
    return (new MailMessage)
      ->subject($this->title)
      ->greeting(__('Hello :name,', ['name' => $notifiable->getFullName()]))
      ->line(__('A new expense request requires your attention.'))
      ->line(__('Employee: :employee', ['employee' => $this->request->user->getFullName()]))
      ->line(__('Expense Type: :type', ['type' => $this->request->expenseType->name]))
      ->line(__('Amount: :amount', ['amount' => $this->request->currency . ' ' . number_format($this->request->amount, 2)]))
      ->line(__('Date: :date', ['date' => $this->request->expense_date->format('M j, Y')]))
      ->line(__('Title: :title', ['title' => $this->request->title]))
      ->when($this->request->description, function ($mail) {
        return $mail->line(__('Description: :description', ['description' => $this->request->description]));
      })
      ->action(__('Review Expense Request'), $actionUrl)
      ->line(__('Please review and take appropriate action on this expense request.'));
  }

  /**
   * Get the database representation of the notification.
   */
  public function toDatabase($notifiable): array
  {
    return [
      'type' => 'new_expense_request',
      'title' => $this->title,
      'message' => $this->message,
      'action_url' => route('hrcore.expenses.show', $this->request->id),
      'metadata' => [
        'expense_request_id' => $this->request->id,
        'expense_number' => $this->request->expense_number,
        'employee_id' => $this->request->user_id,
        'employee_name' => $this->request->user->getFullName(),
        'expense_type' => $this->request->expenseType->name,
        'amount' => $this->request->amount,
        'currency' => $this->request->currency,
        'expense_date' => $this->request->expense_date->toDateString(),
        'title' => $this->request->title,
        'status' => $this->request->status,
        'created_at' => $this->request->created_at->toISOString()
      ],
      'priority' => $this->request->amount > 1000 ? 'high' : 'normal'
    ];
  }

  /**
   * Get the Firebase representation of the notification.
   */
  public function toFirebase($notifiable)
  {
    return [
      'title' => $this->title,
      'body' => $this->message,
      'data' => [
        'type' => 'expense_request',
        'action' => 'new_request',
        'expense_request_id' => $this->request->id,
        'amount' => $this->request->amount,
        'click_action' => route('hrcore.expenses.show', $this->request->id)
      ]
    ];
  }
}
