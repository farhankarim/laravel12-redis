import { NestFactory } from '@nestjs/core';
import { AppModule } from './app.module';
import { ValidationPipe } from '@nestjs/common';
import { DocumentBuilder, SwaggerModule } from '@nestjs/swagger';
import { NestExpressApplication } from '@nestjs/platform-express';
import { join } from 'path';
import { createBullBoard } from '@bull-board/api';
import { BullAdapter } from '@bull-board/api/bullAdapter';
import { ExpressAdapter } from '@bull-board/express';
import Bull from 'bull';

async function bootstrap() {
  const app = await NestFactory.create<NestExpressApplication>(AppModule);

  // Serve static assets (dashboard HTML)
  app.useStaticAssets(join(__dirname, '..', 'public'));

  // Global validation pipe
  app.useGlobalPipes(
    new ValidationPipe({
      whitelist: true,
      transform: true,
      forbidNonWhitelisted: true,
    }),
  );

  // -----------------------------------------------------------------
  // Horizon (Bull Board) – visual queue monitoring at /horizon
  // -----------------------------------------------------------------
  const redisConfig = {
    host: process.env.REDIS_HOST || '127.0.0.1',
    port: parseInt(process.env.REDIS_PORT || '6379', 10),
    password: process.env.REDIS_PASSWORD || undefined,
  };

  const queueNames = (
    process.env.QUEUE_NAMES || 'default,user-imports,email-verifications'
  ).split(',');

  const bullAdapters = queueNames.map(
    (name) =>
      new BullAdapter(
        new Bull(name.trim(), { redis: redisConfig }),
      ),
  );

  const serverAdapter = new ExpressAdapter();
  serverAdapter.setBasePath('/horizon');

  createBullBoard({ queues: bullAdapters, serverAdapter });

  // Mount the Bull Board Express router at /horizon
  app.use('/horizon', serverAdapter.getRouter());

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
  console.log(`📊 Queue Dashboard:         http://localhost:${port}/dashboard/queue.html`);
  console.log(`👥 Users Dashboard:         http://localhost:${port}/dashboard/users.html`);
  console.log(`🔭 Horizon (Bull Board):    http://localhost:${port}/horizon`);
}
bootstrap();
