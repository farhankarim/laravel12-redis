"use strict";
var __assign = (this && this.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
var __esDecorate = (this && this.__esDecorate) || function (ctor, descriptorIn, decorators, contextIn, initializers, extraInitializers) {
    function accept(f) { if (f !== void 0 && typeof f !== "function") throw new TypeError("Function expected"); return f; }
    var kind = contextIn.kind, key = kind === "getter" ? "get" : kind === "setter" ? "set" : "value";
    var target = !descriptorIn && ctor ? contextIn["static"] ? ctor : ctor.prototype : null;
    var descriptor = descriptorIn || (target ? Object.getOwnPropertyDescriptor(target, contextIn.name) : {});
    var _, done = false;
    for (var i = decorators.length - 1; i >= 0; i--) {
        var context = {};
        for (var p in contextIn) context[p] = p === "access" ? {} : contextIn[p];
        for (var p in contextIn.access) context.access[p] = contextIn.access[p];
        context.addInitializer = function (f) { if (done) throw new TypeError("Cannot add initializers after decoration has completed"); extraInitializers.push(accept(f || null)); };
        var result = (0, decorators[i])(kind === "accessor" ? { get: descriptor.get, set: descriptor.set } : descriptor[key], context);
        if (kind === "accessor") {
            if (result === void 0) continue;
            if (result === null || typeof result !== "object") throw new TypeError("Object expected");
            if (_ = accept(result.get)) descriptor.get = _;
            if (_ = accept(result.set)) descriptor.set = _;
            if (_ = accept(result.init)) initializers.unshift(_);
        }
        else if (_ = accept(result)) {
            if (kind === "field") initializers.unshift(_);
            else descriptor[key] = _;
        }
    }
    if (target) Object.defineProperty(target, contextIn.name, descriptor);
    done = true;
};
var __runInitializers = (this && this.__runInitializers) || function (thisArg, initializers, value) {
    var useValue = arguments.length > 2;
    for (var i = 0; i < initializers.length; i++) {
        value = useValue ? initializers[i].call(thisArg, value) : initializers[i].call(thisArg);
    }
    return useValue ? value : void 0;
};
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var __generator = (this && this.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g = Object.create((typeof Iterator === "function" ? Iterator : Object).prototype);
    return g.next = verb(0), g["throw"] = verb(1), g["return"] = verb(2), typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (g && (g = 0, op[0] && (_ = 0)), _) try {
            if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [op[0] & 2, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};
var __setFunctionName = (this && this.__setFunctionName) || function (f, name, prefix) {
    if (typeof name === "symbol") name = name.description ? "[".concat(name.description, "]") : "";
    return Object.defineProperty(f, "name", { configurable: true, value: prefix ? "".concat(prefix, " ", name) : name });
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.DashboardService = void 0;
var common_1 = require("@nestjs/common");
var ioredis_1 = require("ioredis");
var DashboardService = function () {
    var _classDecorators = [(0, common_1.Injectable)()];
    var _classDescriptor;
    var _classExtraInitializers = [];
    var _classThis;
    var DashboardService = _classThis = /** @class */ (function () {
        function DashboardService_1(config, jwtService, userImportsQueue, emailVerificationsQueue) {
            this.config = config;
            this.jwtService = jwtService;
            this.userImportsQueue = userImportsQueue;
            this.emailVerificationsQueue = emailVerificationsQueue;
            this.logger = new common_1.Logger(DashboardService.name);
            this.QUEUE_SUMMARY_KEY = 'dashboard:queue_summary';
            this.USERS_SUMMARY_KEY = 'dashboard:users_summary';
            this.REFRESH_CHANNEL = 'dashboard.summary.refresh';
            this.UPDATED_CHANNEL = 'dashboard.summary.updated';
            this.TTL = 3600; // 1 hour
            // Callback invoked when a pub/sub refresh arrives
            this.onRefreshCallback = null;
        }
        DashboardService_1.prototype.onModuleInit = function () {
            var _this = this;
            var redisOpts = {
                host: this.config.get('REDIS_HOST', '127.0.0.1'),
                port: this.config.get('REDIS_PORT', 6379),
                password: this.config.get('REDIS_PASSWORD') || undefined,
            };
            this.redisClient = new ioredis_1.default(redisOpts);
            this.redisSubscriber = new ioredis_1.default(redisOpts);
            // Subscribe to refresh channel
            this.redisSubscriber.subscribe(this.REFRESH_CHANNEL, function (err) {
                if (err)
                    _this.logger.error("Redis subscribe error: ".concat(err.message));
            });
            this.redisSubscriber.on('message', function (channel, _message) { return __awaiter(_this, void 0, void 0, function () {
                return __generator(this, function (_a) {
                    switch (_a.label) {
                        case 0:
                            if (!(channel === this.REFRESH_CHANNEL && this.onRefreshCallback)) return [3 /*break*/, 2];
                            return [4 /*yield*/, this.onRefreshCallback()];
                        case 1:
                            _a.sent();
                            _a.label = 2;
                        case 2: return [2 /*return*/];
                    }
                });
            }); });
        };
        DashboardService_1.prototype.onModuleDestroy = function () {
            var _a, _b;
            (_a = this.redisClient) === null || _a === void 0 ? void 0 : _a.disconnect();
            (_b = this.redisSubscriber) === null || _b === void 0 ? void 0 : _b.disconnect();
        };
        // ── Queue Summary ─────────────────────────────────────────────────────────
        DashboardService_1.prototype.getQueueSummary = function () {
            return __awaiter(this, void 0, void 0, function () {
                var cached;
                return __generator(this, function (_a) {
                    switch (_a.label) {
                        case 0: return [4 /*yield*/, this.redisClient.get(this.QUEUE_SUMMARY_KEY)];
                        case 1:
                            cached = _a.sent();
                            if (cached) {
                                try {
                                    return [2 /*return*/, JSON.parse(cached)];
                                }
                                catch (_b) {
                                    // fall through to rebuild
                                }
                            }
                            return [2 /*return*/, this.buildAndCacheQueueSummary()];
                    }
                });
            });
        };
        DashboardService_1.prototype.buildAndCacheQueueSummary = function () {
            return __awaiter(this, void 0, void 0, function () {
                var queueNames, _a, userImportsCounts, emailVerificationCounts, bullCountsMap, queues, allFailed, allCompleted, summary;
                var _this = this;
                return __generator(this, function (_b) {
                    switch (_b.label) {
                        case 0:
                            queueNames = this.config
                                .get('QUEUE_NAMES', 'default,user-imports,email-verifications')
                                .split(',')
                                .map(function (n) { return n.trim(); });
                            return [4 /*yield*/, Promise.all([
                                    this.userImportsQueue.getJobCounts(),
                                    this.emailVerificationsQueue.getJobCounts(),
                                ])];
                        case 1:
                            _a = _b.sent(), userImportsCounts = _a[0], emailVerificationCounts = _a[1];
                            bullCountsMap = {
                                'user-imports': userImportsCounts,
                                'email-verifications': emailVerificationCounts,
                            };
                            return [4 /*yield*/, Promise.all(queueNames.map(function (name) { return __awaiter(_this, void 0, void 0, function () {
                                    var _a, pending, reserved, delayed;
                                    return __generator(this, function (_b) {
                                        switch (_b.label) {
                                            case 0: return [4 /*yield*/, Promise.all([
                                                    this.redisClient.llen("bull:".concat(name, ":wait")),
                                                    this.redisClient.zcard("bull:".concat(name, ":active")),
                                                    this.redisClient.zcard("bull:".concat(name, ":delayed")),
                                                ])];
                                            case 1:
                                                _a = _b.sent(), pending = _a[0], reserved = _a[1], delayed = _a[2];
                                                return [2 /*return*/, { name: name, pending: pending, reserved: reserved, delayed: delayed }];
                                        }
                                    });
                                }); }))];
                        case 2:
                            queues = _b.sent();
                            allFailed = (userImportsCounts.failed || 0) + (emailVerificationCounts.failed || 0);
                            allCompleted = (userImportsCounts.completed || 0) + (emailVerificationCounts.completed || 0);
                            summary = {
                                queues: queues,
                                totals: {
                                    pending: queues.reduce(function (s, q) { return s + q.pending; }, 0),
                                    reserved: queues.reduce(function (s, q) { return s + q.reserved; }, 0),
                                    delayed: queues.reduce(function (s, q) { return s + q.delayed; }, 0),
                                    failed: allFailed,
                                    completed: allCompleted,
                                },
                                cachedAt: new Date().toISOString(),
                            };
                            return [4 /*yield*/, this.redisClient.setex(this.QUEUE_SUMMARY_KEY, this.TTL, JSON.stringify(summary))];
                        case 3:
                            _b.sent();
                            return [4 /*yield*/, this.redisClient.publish(this.UPDATED_CHANNEL, JSON.stringify({ type: 'queue' }))];
                        case 4:
                            _b.sent();
                            return [2 /*return*/, summary];
                    }
                });
            });
        };
        // ── Users Summary ─────────────────────────────────────────────────────────
        DashboardService_1.prototype.getUsersSummary = function () {
            return __awaiter(this, void 0, void 0, function () {
                var cached;
                return __generator(this, function (_a) {
                    switch (_a.label) {
                        case 0: return [4 /*yield*/, this.redisClient.get(this.USERS_SUMMARY_KEY)];
                        case 1:
                            cached = _a.sent();
                            if (cached) {
                                try {
                                    return [2 /*return*/, JSON.parse(cached)];
                                }
                                catch (_b) {
                                    return [2 /*return*/, null];
                                }
                            }
                            return [2 /*return*/, null];
                    }
                });
            });
        };
        DashboardService_1.prototype.cacheUsersSummary = function (summary) {
            return __awaiter(this, void 0, void 0, function () {
                var full;
                return __generator(this, function (_a) {
                    switch (_a.label) {
                        case 0:
                            full = __assign(__assign({}, summary), { cachedAt: new Date().toISOString() });
                            return [4 /*yield*/, this.redisClient.setex(this.USERS_SUMMARY_KEY, this.TTL, JSON.stringify(full))];
                        case 1:
                            _a.sent();
                            return [4 /*yield*/, this.redisClient.publish(this.UPDATED_CHANNEL, JSON.stringify({ type: 'users' }))];
                        case 2:
                            _a.sent();
                            return [2 /*return*/, full];
                    }
                });
            });
        };
        // ── Pub/Sub helpers ───────────────────────────────────────────────────────
        DashboardService_1.prototype.publishRefresh = function () {
            return __awaiter(this, void 0, void 0, function () {
                return __generator(this, function (_a) {
                    switch (_a.label) {
                        case 0: return [4 /*yield*/, this.redisClient.publish(this.REFRESH_CHANNEL, JSON.stringify({ ts: Date.now() }))];
                        case 1:
                            _a.sent();
                            return [2 /*return*/];
                    }
                });
            });
        };
        // ── Email Verification ────────────────────────────────────────────────────
        DashboardService_1.prototype.verifyEmailToken = function (token) {
            return __awaiter(this, void 0, void 0, function () {
                var payload;
                return __generator(this, function (_a) {
                    switch (_a.label) {
                        case 0:
                            try {
                                payload = this.jwtService.verify(token, {
                                    secret: this.config.get('JWT_SECRET', 'changeme'),
                                });
                            }
                            catch (_b) {
                                throw new common_1.UnauthorizedException('Invalid or expired verification token');
                            }
                            if (payload.type !== 'email-verification') {
                                throw new common_1.UnauthorizedException('Invalid token type');
                            }
                            // Invalidate cache so dashboard shows updated verified count
                            return [4 /*yield*/, this.redisClient.del(this.USERS_SUMMARY_KEY)];
                        case 1:
                            // Invalidate cache so dashboard shows updated verified count
                            _a.sent();
                            return [2 /*return*/, {
                                    message: "Email ".concat(payload.email, " verified successfully. You may now log in."),
                                }];
                    }
                });
            });
        };
        return DashboardService_1;
    }());
    __setFunctionName(_classThis, "DashboardService");
    (function () {
        var _metadata = typeof Symbol === "function" && Symbol.metadata ? Object.create(null) : void 0;
        __esDecorate(null, _classDescriptor = { value: _classThis }, _classDecorators, { kind: "class", name: _classThis.name, metadata: _metadata }, null, _classExtraInitializers);
        DashboardService = _classThis = _classDescriptor.value;
        if (_metadata) Object.defineProperty(_classThis, Symbol.metadata, { enumerable: true, configurable: true, writable: true, value: _metadata });
        __runInitializers(_classThis, _classExtraInitializers);
    })();
    return DashboardService = _classThis;
}();
exports.DashboardService = DashboardService;
