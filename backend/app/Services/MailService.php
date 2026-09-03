<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;

class MailService
{
    /**
     * Send a general transactional HTML email.
     */
    public function sendTransactional(string $toEmail, string $subject, string $htmlContent, ?string $toName = null): array
    {
        try {
            Mail::html($htmlContent, function ($message) use ($toEmail, $subject, $toName) {
                $fromAddress = config('mail.from.address', 'support@pixelneuron.net');
                $fromName = config('mail.from.name', 'TripBD Support');

                $message->from($fromAddress, $fromName)
                        ->to($toEmail, $toName)
                        ->subject($subject);
            });

            return [
                'success' => true,
                'message' => 'Email sent successfully via SMTP server.',
                'recipient' => $toEmail,
                'sender' => config('mail.from.address', 'support@pixelneuron.net')
            ];
        } catch (Exception $e) {
            Log::error('SMTP Transactional Email Failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage(),
                'recipient' => $toEmail
            ];
        }
    }

    /**
     * Send trip booking confirmation receipt.
     */
    public function sendTripInvoice(string $toEmail, string $customerName, array $bookingData): array
    {
        $subject = "TripBD Invoice & Confirmation - Booking #{$bookingData['booking_number']}";
        $html = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;'>
                <div style='background: #0f172a; padding: 20px; text-align: center; color: #ffffff;'>
                    <h2 style='margin: 0;'>TripBD</h2>
                    <p style='margin: 4px 0 0; font-size: 13px; color: #94a3b8;'>Bangladesh Transport & Logistics</p>
                </div>
                <div style='padding: 24px; color: #334155;'>
                    <h3 style='color: #0f172a; margin-top: 0;'>Trip Completed & Paid</h3>
                    <p>Dear <strong>{$customerName}</strong>,</p>
                    <p>Thank you for traveling with TripBD. Here are your trip details and receipt:</p>
                    
                    <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                        <tr style='border-bottom: 1px solid #f1f5f9;'>
                            <td style='padding: 8px 0; color: #64748b;'>Booking No:</td>
                            <td style='padding: 8px 0; font-weight: bold; text-align: right;'>{$bookingData['booking_number']}</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #f1f5f9;'>
                            <td style='padding: 8px 0; color: #64748b;'>Pickup Location:</td>
                            <td style='padding: 8px 0; text-align: right;'>{$bookingData['pickup_address']}</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #f1f5f9;'>
                            <td style='padding: 8px 0; color: #64748b;'>Destination:</td>
                            <td style='padding: 8px 0; text-align: right;'>{$bookingData['destination_address']}</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #f1f5f9;'>
                            <td style='padding: 8px 0; color: #64748b;'>Distance:</td>
                            <td style='padding: 8px 0; text-align: right;'>{$bookingData['distance_km']} km</td>
                        </tr>
                        <tr style='border-bottom: 2px solid #0f172a;'>
                            <td style='padding: 12px 0; font-weight: bold; font-size: 16px;'>Total Fare:</td>
                            <td style='padding: 12px 0; font-weight: bold; font-size: 18px; color: #16a34a; text-align: right;'>BDT {$bookingData['final_fare']}</td>
                        </tr>
                    </table>

                    <p style='font-size: 13px; color: #64748b; margin-top: 24px;'>
                        Need help? Contact our 24/7 helpline or write to <a href='mailto:support@pixelneuron.net' style='color: #2563eb;'>support@pixelneuron.net</a>.
                    </p>
                </div>
            </div>
        ";

        return $this->sendTransactional($toEmail, $subject, $html, $customerName);
    }

    /**
     * Send driver KYC verification notice.
     */
    public function sendDriverKycStatus(string $toEmail, string $driverName, string $status, ?string $reason = null): array
    {
        $isApproved = $status === 'approved';
        $subject = $isApproved ? "Congratulations! Your TripBD Driver Profile is Approved" : "TripBD Driver Profile Update Required";
        
        $html = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px;'>
                <h3 style='color: " . ($isApproved ? '#16a34a' : '#dc2626') . ";'>" . ($isApproved ? 'Profile Approved' : 'Action Required') . "</h3>
                <p>Hello <strong>{$driverName}</strong>,</p>
                " . ($isApproved ? 
                    "<p>Your driver documents, National ID, and vehicle documents have been verified and approved by the TripBD safety team. You can now toggle your status to <strong>Online</strong> and start accepting trips!</p>" : 
                    "<p>Your profile verification could not be completed. Reason: <em>" . htmlspecialchars($reason ?? 'Document re-upload needed') . "</em>. Please log in to your account and update your verification documents.</p>"
                ) . "
                <p style='font-size: 13px; color: #64748b; margin-top: 24px;'>
                    TripBD Operations Team &bull; <a href='mailto:support@pixelneuron.net'>support@pixelneuron.net</a>
                </p>
            </div>
        ";

        return $this->sendTransactional($toEmail, $subject, $html, $driverName);
    }
}
