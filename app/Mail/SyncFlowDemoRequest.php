<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SyncFlowDemoRequest extends Mailable
{
    use Queueable, SerializesModels;

    public $demoRequest;
    public $isInternal;

    /**
     * Create a new message instance.
     */
    public function __construct($demoRequest, $isInternal = false)
    {
        $this->demoRequest = $demoRequest;
        $this->isInternal = $isInternal;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): array
    {
        return [
            'subject' => $this->isInternal 
                ? 'New SyncFlow Demo Request - ' . $this->demoRequest->company 
                : 'SyncFlow Demo Request Confirmed - ' . $this->demoRequest->company,
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
                <h2 style="color: #2c3e50; margin: 0 0 15px 0; font-size: 20px;">Permintaan Demo Anda Telah Diterima!</h2>
                <p style="color: #5a6c7d; line-height: 1.6; margin: 0 0 20px 0;">
                    Terima kasih telah mengajukan permintaan demo untuk <strong>' . $this->demoRequest->company . '</strong>. 
                    Tim kami akan menghubungi Anda untuk konfirmasi jadwal demo yang Anda pilih.
                </p>
                
                <div style="background: white; padding: 20px; border-radius: 6px; margin-bottom: 20px;">
                    <h3 style="color: #4A90E2; margin: 0 0 15px 0; font-size: 16px;">Detail Demo:</h3>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Nama:</td>
                            <td style="padding: 8px 0; color: #2c3e50;">' . $this->demoRequest->name . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Email:</td>
                            <td style="padding: 8px 0; color: #2c3e50;">' . $this->demoRequest->email . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Perusahaan:</td>
                            <td style="padding: 8px 0; color: #2c3e50;">' . $this->demoRequest->company . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Telepon:</td>
                            <td style="padding: 8px 0; color: #2c3e50;">' . $this->demoRequest->phone . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Jumlah Karyawan:</td>
                            <td style="padding: 8px 0; color: #2c3e50;">' . $this->demoRequest->employees . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Tanggal Demo:</td>
                            <td style="padding: 8px 0; color: #2c3e50;">' . $this->demoRequest->preferred_date->format('d F Y') . '</td>
                        </tr>';
                        
                        if ($this->demoRequest->preferred_time) {
                            echo '
                        <tr>
                            <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Waktu Demo:</td>
                            <td style="padding: 8px 0; color: #2c3e50;">' . $this->demoRequest->preferred_time . '</td>
                        </tr>';
                        }
                        
                        if ($this->demoRequest->notes) {
                            echo '
                        <tr>
                            <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold; vertical-align: top;">Catatan:</td>
                            <td style="padding: 8px 0; color: #2c3e50;">' . nl2br($this->demoRequest->notes) . '</td>
                        </tr>';
                        }
                        
                        echo '
                    </table>
                </div>

                <div style="background: #e3f2fd; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #4A90E2;">
                    <h4 style="color: #2c3e50; margin: 0 0 10px 0; font-size: 14px;">📅 Yang Akan Terjadi Selanjutnya:</h4>
                    <ul style="color: #5a6c7d; margin: 0; padding-left: 20px; line-height: 1.6;">
                        <li>Tim kami akan menghubungi Anda dalam 2 jam kerja</li>
                        <li>Konfirmasi jadwal demo via telepon atau email</li>
                        <li>Dapatkan link meeting virtual demo</li>
                        <li>Demo berlangsung selama 45-60 menit</li>
                        <li>Dapatkan penawaran khusus untuk perusahaan Anda</li>
                    </ul>
                </div>

                <div style="text-align: center; margin: 30px 0;">
                    <a href="https://syncflow.id" style="background: #4A90E2; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">
                        Pelajari Lebih Lanjut
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
                <h1 style="color: white; margin: 0; font-size: 24px;">🎯 New Demo Request!</h1>
                <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0;">High-priority follow up required</p>
            </div>

            <div style="background: #f8f9fa; padding: 25px; border-radius: 8px; margin-bottom: 20px;">
                <h2 style="color: #2c3e50; margin: 0 0 15px 0;">Customer Information</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Name:</td>
                        <td style="padding: 8px 0; color: #2c3e50;">' . $this->demoRequest->name . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Email:</td>
                        <td style="padding: 8px 0; color: #2c3e50;">' . $this->demoRequest->email . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Company:</td>
                        <td style="padding: 8px 0; color: #2c3e50;">' . $this->demoRequest->company . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Phone:</td>
                        <td style="padding: 8px 0; color: #2c3e50;">' . $this->demoRequest->phone . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Employees:</td>
                        <td style="padding: 8px 0; color: #2c3e50;">' . $this->demoRequest->employees . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Preferred Date:</td>
                        <td style="padding: 8px 0; color: #2c3e50;">' . $this->demoRequest->preferred_date->format('Y-m-d H:i') . '</td>
                    </tr>';
                    
                    if ($this->demoRequest->preferred_time) {
                        echo '
                    <tr>
                        <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Preferred Time:</td>
                        <td style="padding: 8px 0; color: #2c3e50;">' . $this->demoRequest->preferred_time . '</td>
                    </tr>';
                    }
                    
                    if ($this->demoRequest->notes) {
                        echo '
                    <tr>
                        <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold; vertical-align: top;">Notes:</td>
                        <td style="padding: 8px 0; color: #2c3e50;">' . nl2br($this->demoRequest->notes) . '</td>
                    </tr>';
                    }
                    
                    echo '
                    <tr>
                        <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">IP Address:</td>
                        <td style="padding: 8px 0; color: #2c3e50;">' . $this->demoRequest->ip_address . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #5a6c7d; font-weight: bold;">Submitted:</td>
                        <td style="padding: 8px 0; color: #2c3e50;">' . $this->demoRequest->created_at->format('Y-m-d H:i:s') . '</td>
                    </tr>
                </table>
            </div>

            <div style="background: #fff3cd; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
                <h4 style="color: #856404; margin: 0 0 10px 0; font-size: 14px;">⚡ Action Required:</h4>
                <ol style="color: #856404; margin: 0; padding-left: 20px; line-height: 1.6;">
                    <li>Contact customer within 2 business hours</li>
                    <li>Confirm demo schedule and availability</li>
                    <li>Send calendar invitation with meeting link</li>
                    <li>Prepare personalized demo based on company size</li>
                    <li>Follow up after demo with proposal</li>
                </ol>
            </div>

            <div style="text-align: center; margin: 20px 0;">
                <a href="mailto:' . $this->demoRequest->email . '" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; margin-right: 10px;">
                    📧 Contact Customer
                </a>
                <a href="tel:' . $this->demoRequest->phone . '" style="background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">
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
