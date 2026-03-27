import { Injectable, Logger } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import * as nodemailer from 'nodemailer';
import { JwtService } from '@nestjs/jwt';

@Injectable()
export class MailService {
  private readonly logger = new Logger(MailService.name);
  private transporter: nodemailer.Transporter;

  constructor(
    private readonly config: ConfigService,
    private readonly jwtService: JwtService,
  ) {
    this.transporter = nodemailer.createTransport({
      host: config.get<string>('MAIL_HOST', 'smtp.mailtrap.io'),
      port: config.get<number>('MAIL_PORT', 587),
      auth: {
        user: config.get<string>('MAIL_USER', ''),
        pass: config.get<string>('MAIL_PASS', ''),
      },
    });
  }

  /**
   * Generate a signed JWT-based email verification URL (expires in 24h).
   */
  generateVerificationUrl(userId: string, email: string): string {
    const token = this.jwtService.sign(
      { sub: userId, email, type: 'email-verification' },
      { expiresIn: '24h' },
    );
    const appUrl = this.config.get<string>('APP_URL', 'http://localhost:3000');
    return `${appUrl}/email/verify?token=${token}`;
  }

  async sendVerificationEmail(userId: string, email: string, name: string): Promise<void> {
    const verificationUrl = this.generateVerificationUrl(userId, email);
    const fromName = this.config.get<string>('MAIL_FROM_NAME', 'Redis NestJS App');
    const fromAddress = this.config.get<string>('MAIL_FROM', 'noreply@example.com');

    try {
      await this.transporter.sendMail({
        from: `"${fromName}" <${fromAddress}>`,
        to: email,
        subject: 'Verify your email address',
        html: `
          <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto;">
            <h2>Hello ${name},</h2>
            <p>Please click the button below to verify your email address. This link expires in 24 hours.</p>
            <a href="${verificationUrl}"
               style="display: inline-block; padding: 12px 24px; background: #4F46E5;
                      color: white; text-decoration: none; border-radius: 6px; margin: 16px 0;">
              Verify Email Address
            </a>
            <p style="color: #6B7280; font-size: 14px;">
              If you did not create an account, no further action is required.
            </p>
          </div>
        `,
      });
      this.logger.log(`Verification email sent to ${email}`);
    } catch (err) {
      this.logger.error(`Failed to send email to ${email}: ${err.message}`);
      throw err;
    }
  }
}
