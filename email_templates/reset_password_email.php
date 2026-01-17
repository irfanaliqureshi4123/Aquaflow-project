<?php
function getResetEmailTemplate($resetLink) {
    return "
    <div style='font-family: Arial, sans-serif; background-color: #f9fafb; padding: 30px;'>
        <table align='center' width='600' cellpadding='0' cellspacing='0' 
               style='background:#ffffff; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.1);'>
            <tr>
                <td style='padding: 20px; text-align: center; background-color: #0891b2; border-radius:10px 10px 0 0;'>
                    <img src='cid:company_logo' alt='AquaFlow Logo' width='120' style='margin-bottom:10px;'/>
                    <h2 style='color:#ffffff; margin:0;'>AquaFlow Account Security</h2>
                </td>
            </tr>
            <tr>
                <td style='padding: 30px; color: #333333;'>
                    <h3 style='color:#0891b2;'>Password Reset Request</h3>
                    <p>Hello,</p>
                    <p>We received a request to reset your AquaFlow account password. 
                    Click the button below to set a new password:</p>
                    <p style='text-align:center; margin: 30px 0;'>
                        <a href='$resetLink' style='background-color:#0891b2;color:#fff;padding:12px 24px;
                           border-radius:6px;text-decoration:none;font-weight:bold;'>Reset My Password</a>
                    </p>
                    <p>This link is valid for <strong>1 hour</strong>. If you didn’t request this, 
                    please ignore this email.</p>
                    <p>Thank you,<br><strong>AquaFlow Support Team</strong></p>
                </td>
            </tr>
            <tr>
                <td style='background-color:#f1f5f9; text-align:center; padding:15px; border-radius:0 0 10px 10px;'>
                    <p style='font-size:13px; color:#6b7280;'>© ".date("Y")." AquaFlow Water Delivery — All rights reserved.</p>
                </td>
            </tr>
        </table>
    </div>
    ";
}
?>
