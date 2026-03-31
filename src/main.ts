import { NestFactory } from '@nestjs/core';
import { AppModule } from './app.module';
import { ValidationPipe } from '@nestjs/common';
import { DocumentBuilder, SwaggerModule } from '@nestjs/swagger';
import { NestExpressApplication } from '@nestjs/platform-express';
import { join } from 'path';

async function bootstrap() {
  const app = await NestFactory.create<NestExpressApplication>(AppModule);

  // Enable CORS for the React admin panel dev server
  app.enableCors();

  // Serve static assets (dashboard HTML + React admin panel)
  app.useStaticAssets(join(__dirname, '..', 'public'));

  // Global validation pipe
  app.useGlobalPipes(
    new ValidationPipe({
      whitelist: true,
      transform: true,
      forbidNonWhitelisted: true,
    }),
  );

  // Swagger API docs
  const config = new DocumentBuilder()
    .setTitle('Redis NestJS Dashboard API')
    .setDescription(
      'NestJS + MongoDB + Redis application converted from Laravel 12. ' +
        'Provides user management, bulk queue-based user generation, email verification, ' +
        'and real-time Redis queue dashboards.',
    )
    .setVersion('1.0')
    .addBearerAuth()
    .build();
  const document = SwaggerModule.createDocument(app, config);
  SwaggerModule.setup('api/docs', app, document);

  const port = process.env.APP_PORT || 3000;
  await app.listen(port);
  console.log(`🚀 Application running on: http://localhost:${port}`);
  console.log(`📖 Swagger docs:            http://localhost:${port}/api/docs`);
  console.log(`🖥️  React Admin Panel:       http://localhost:${port}/admin/`);
  console.log(`📊 Queue Dashboard:         http://localhost:${port}/dashboard/queue.html`);
  console.log(`👥 Users Dashboard:         http://localhost:${port}/dashboard/users.html`);
}
bootstrap();
