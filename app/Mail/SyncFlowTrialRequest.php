<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SyncFlowTrialRequest extends Mailable
{
    use Queueable, SerializesModels;

    public $trialRequest;
    public $isInternal;

    /**
     * Create a new message instance.
     */
    public function __construct($trialRequest, $isInternal = false)
    {
        $this->trialRequest = $trialRequest;
        $this->isInternal = $isInternal;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): array
    {
        return [
            'subject' => $this->isInternal 
                ? 'New SyncFlow Trial Request - ' . $this->trialRequest->company 
                : 'SyncFlow Trial Request Received - ' . $this->trialRequest->company,
        ];
    }

    /**
     * Get the message content definition.
     */
    public function content(): array
    {
        return [
            'html' => $this->isInternal 
                ? $this->internalHtmlView() 
                : $this->customerHtmlView(),
        ];
    }

    /**
     * Customer HTML view
     */
    private function customerHtmlView()
    {
        return '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #4A90E2, #357ABD); padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 30px;">
                <h1 style="color: white; margin: 0; font-size: 28px;">SyncFlow</h1>
                <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 16px;">Platform Manajemen Proyek Terpadu</p>
            </div>

            <div style="background: #f8f9fa; padding: 25px; border-radius: 8px; margin-bottom: 20px;">
                <h2 style="color: #2c3e50; margin: 0 0 15px 0; font-size: 20px;">Terima Kasih atas Permintaan Trial Anda!</h2>
                <p style="color: #5a6c7d; line-height: 1.6; margin: 0 0 20px 0;">
                    Kami telah menerima permintaan trial untuk <strong>' . $this->trialRequest->company . '</strong>. 
                    Tim kami akan segera menghubungi Anda dalam 24 jam untuk setup akun trial Anda.
                </p>
                
                <div style="background: white; padding: 20px; border-radius: 6px; margin-bottom: 20px;">
                    <h3 style="color: #4A90E2; margin: 0 0 15px 0; font-size: 16px;">Detail Permintaan:</h3>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Nama:</td>
                            <td style="padding: 8px 0; color: #2c3e50;">' . $this->trialRequest->name . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Email:</td>
                            <td style="padding: 8px 0; color: #2c3e50;">' . $this->trialRequest->email . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Perusahaan:</td>
                            <td style="padding: 8px 0; color: #2c3e50;">' . $this->trialRequest->company . '</td>
                        </tr>';
                        
                        if ($this->trialRequest->phone) {
                            echo '
                        <tr>
                            <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Telepon:</td>
                            <td style="padding: 8px 0; color: #2c3e50;">' . $this->trialRequest->phone . '</td>
                        </tr>';
                        }
                        
                        if ($this->trialRequest->employees) {
                            echo '
                        <tr>
                            <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Jumlah Karyawan:</td>
                            <td style="padding: 8px 0; color: #2c3e50;">' . $this->trialRequest->employees . '</td>
                        </tr>';
                        }
                        
                        if ($this->trialRequest->industry) {
                            echo '
                        <tr>
                            <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Industri:</td>
                            <td style="padding: 8px 0; color: #2c3e50;">' . $this->trialRequest->industry . '</td>
                        </tr>';
                        }
                        
                        echo '
                    </table>
                </div>

                <div style="text-align: center; margin: 30px 0;">
                    <a href="https://syncflow.id" style="background: #4A90E2; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">
                        Kunjungi SyncFlow
                    </a>
                </div>
            </div>

            <div style="text-align: center; padding: 20px; border-top: 1px solid #e9ecef; color: #6c757d; font-size: 14px;">
                <p style="margin: 0 0 10px 0;">Butuh bantuan? Hubungi kami:</p>
                <p style="margin: 0;">
                    Email: <a href="mailto:support@syncflow.id" style="color: #4A90E2;">support@syncflow.id</a> | 
                    Telepon: <a href="tel:+622150912345" style="color: #4A90E2;">+62 21 509 12345</a>
                </p>
            </div>
        </div>';
    }

    /**
     * Internal HTML view for sales team
     */
    private function internalHtmlView()
    {
        return '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: #ff6b6b; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px;">
                <h1 style="color: white; margin: 0; font-size: 24px;">🔥 New Trial Request!</h1>
                <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0;">Follow up immediately</p>
            </div>

            <div style="background: #f8f9fa; padding: 25px; border-radius: 8px; margin-bottom: 20px;">
                <h2 style="color: #2c3e50; margin: 0 0 15px 0;">Customer Information</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Name:</td>
                        <td style="padding: 8px 0; color: #2c3e50;">' . $this->trialRequest->name . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Email:</td>
                        <td style="padding: 8px 0; color: #2c3e50;">' . $this->trialRequest->email . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Company:</td>
                        <td style="padding: 8px 0; color: #2c3e50;">' . $this->trialRequest->company . '</td>
                    </tr>';
                    
                    if ($this->trialRequest->phone) {
                        echo '
                    <tr>
                        <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Phone:</td>
                        <td style="padding: 8px 0; color: #2c3e50;">' . $this->trialRequest->phone . '</td>
                    </tr>';
                    }
                    
                    if ($this->trialRequest->employees) {
                        echo '
                    <tr>
                        <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Employees:</td>
                        <td style="padding: 8px 0; color: #2c3e50;">' . $this->trialRequest->employees . '</td>
                    </tr>';
                    }
                    
                    if ($this->trialRequest->industry) {
                        echo '
                    <tr>
                        <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Industry:</td>
                        <td style="padding: 8px 0; color: #2c3e50;">' . $this->trialRequest->industry . '</td>
                    </tr>';
                    }
                    
                    echo '
                    <tr>
                        <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">IP Address:</td>
                        <td style="padding: 8px 0; color: #2c3e50;">' . $this->trialRequest->ip_address . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Submitted:</td>
                        <td style="padding: 8px 0; color: #2c3e50;">' . $this->trialRequest->created_at->format('Y-m-d H:i:s') . '</td>
                    </tr>
                </table>
            </div>

            <div style="text-align: center; margin: 20px 0;">
                <a href="mailto:' . $this->trialRequest->email . '" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; margin-right: 10px;">
                    📧 Contact Customer
                </a>
                <a href="tel:' . $this->trialRequest->phone . '" style="background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">
                    📞 Call Customer
                </a>
            </div>
        </div>';
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
