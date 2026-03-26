import {
  WebSocketGateway,
  WebSocketServer,
  SubscribeMessage,
  OnGatewayInit,
  OnGatewayConnection,
  OnGatewayDisconnect,
} from '@nestjs/websockets';
import { Server, Socket } from 'socket.io';
import { Logger } from '@nestjs/common';
import { DashboardService } from './dashboard.service';
import { UsersService } from '../users/users.service';

@WebSocketGateway({
  cors: { origin: '*' },
  namespace: '/dashboard',
})
export class DashboardGateway
  implements OnGatewayInit, OnGatewayConnection, OnGatewayDisconnect
{
  @WebSocketServer()
  server: Server;

  private readonly logger = new Logger(DashboardGateway.name);

  constructor(
    private readonly dashboardService: DashboardService,
    private readonly usersService: UsersService,
  ) {}

  afterInit() {
    this.logger.log('Dashboard WebSocket gateway initialised');

    // Attach pub/sub refresh callback: rebuild summaries and broadcast
    this.dashboardService.onRefreshCallback = async () => {
      const [queueSummary, rawUserSummary] = await Promise.all([
        this.dashboardService.buildAndCacheQueueSummary(),
        this.usersService.getSummary(),
      ]);
      const usersSummary = await this.dashboardService.cacheUsersSummary(rawUserSummary);

      this.server.emit('queue-summary', queueSummary);
      this.server.emit('users-summary', usersSummary);
    };
  }

  handleConnection(client: Socket) {
    this.logger.log(`Client connected: ${client.id}`);
  }

  handleDisconnect(client: Socket) {
    this.logger.log(`Client disconnected: ${client.id}`);
  }

  /** Client requests latest queue summary */
  @SubscribeMessage('get-queue-summary')
  async onGetQueueSummary(client: Socket) {
    const summary = await this.dashboardService.getQueueSummary();
    client.emit('queue-summary', summary);
  }

  /** Client requests latest users summary */
  @SubscribeMessage('get-users-summary')
  async onGetUsersSummary(client: Socket) {
    let summary = await this.dashboardService.getUsersSummary();
    if (!summary) {
      const raw = await this.usersService.getSummary();
      summary = await this.dashboardService.cacheUsersSummary(raw);
    }
    client.emit('users-summary', summary);
  }

  /** Client triggers a full refresh (mimics the Livewire refresh button) */
  @SubscribeMessage('refresh-dashboard')
  async onRefreshDashboard() {
    await this.dashboardService.publishRefresh();
  }
}
