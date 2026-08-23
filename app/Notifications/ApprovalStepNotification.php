<?php

namespace App\Notifications;

use App\Models\approval_transaction;
use App\Models\approval_workflow_step;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;

class ApprovalStepNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public approval_transaction $transaction,
        public approval_workflow_step $step,
        public string $event, // pending_approval | returned | rejected
        public ?string $remarks = null,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $approvable = $this->transaction->approvable;
        $moduleLabel = $this->transaction->workflow->moduleLabel();
        $reference = $approvable->getKey();

        $mail = (new MailMessage)
            ->subject($this->subjectFor($moduleLabel, $reference));

        if ($this->event === 'pending_approval') {
            $mail->line("A {$moduleLabel} (#{$reference}) is waiting for your approval at step \"{$this->step->step_name}\".")
                ->action('Review Request', url("/approvals/{$this->transaction->id}"));
        } elseif ($this->event === 'returned') {
            $mail->line("Your {$moduleLabel} (#{$reference}) was returned by {$this->step->step_name}.")
                ->line($this->remarks ?? '')
                ->action('View Request', url("/rfd/{$reference}/edit"));
        } elseif ($this->event === 'rejected') {
            $mail->line("Your {$moduleLabel} (#{$reference}) was rejected at step \"{$this->step->step_name}\".")
                ->line($this->remarks ?? '');
        }

        return $mail;
    }

    public function toArray($notifiable): array
    {
        return [
            'transaction_id' => $this->transaction->id,
            'step_id' => $this->step->id,
            'event' => $this->event,
            'remarks' => $this->remarks,
        ];
    }

    protected function subjectFor(string $moduleLabel, $reference): string
    {
        return match ($this->event) {
            'pending_approval' => "Approval needed: {$moduleLabel} #{$reference}",
            'returned' => "{$moduleLabel} #{$reference} returned to you",
            'rejected' => "{$moduleLabel} #{$reference} rejected",
            default => "{$moduleLabel} #{$reference} update",
        };
    }
}
