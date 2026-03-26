"use strict";
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
var __setFunctionName = (this && this.__setFunctionName) || function (f, name, prefix) {
    if (typeof name === "symbol") name = name.description ? "[".concat(name.description, "]") : "";
    return Object.defineProperty(f, "name", { configurable: true, value: prefix ? "".concat(prefix, " ", name) : name });
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.QueueModule = void 0;
var common_1 = require("@nestjs/common");
var bull_1 = require("@nestjs/bull");
var config_1 = require("@nestjs/config");
var mongoose_1 = require("@nestjs/mongoose");
var queue_controller_1 = require("./queue.controller");
var insert_users_processor_1 = require("./processors/insert-users.processor");
var send_email_verification_processor_1 = require("./processors/send-email-verification.processor");
var users_module_1 = require("../users/users.module");
var mail_module_1 = require("../mail/mail.module");
var user_schema_1 = require("../users/schemas/user.schema");
var QueueModule = function () {
    var _classDecorators = [(0, common_1.Module)({
            imports: [
                // Register Bull with Redis configuration
                bull_1.BullModule.forRootAsync({
                    imports: [config_1.ConfigModule],
                    inject: [config_1.ConfigService],
                    useFactory: function (config) { return ({
                        redis: {
                            host: config.get('REDIS_HOST', '127.0.0.1'),
                            port: config.get('REDIS_PORT', 6379),
                            password: config.get('REDIS_PASSWORD') || undefined,
                        },
                    }); },
                }),
                bull_1.BullModule.registerQueue({ name: 'user-imports' }, { name: 'email-verifications' }),
                mongoose_1.MongooseModule.forFeature([{ name: user_schema_1.User.name, schema: user_schema_1.UserSchema }]),
                users_module_1.UsersModule,
                mail_module_1.MailModule,
            ],
            controllers: [queue_controller_1.QueueController],
            providers: [insert_users_processor_1.InsertUsersProcessor, send_email_verification_processor_1.SendEmailVerificationProcessor],
            exports: [bull_1.BullModule],
        })];
    var _classDescriptor;
    var _classExtraInitializers = [];
    var _classThis;
    var QueueModule = _classThis = /** @class */ (function () {
        function QueueModule_1() {
        }
        return QueueModule_1;
    }());
    __setFunctionName(_classThis, "QueueModule");
    (function () {
        var _metadata = typeof Symbol === "function" && Symbol.metadata ? Object.create(null) : void 0;
        __esDecorate(null, _classDescriptor = { value: _classThis }, _classDecorators, { kind: "class", name: _classThis.name, metadata: _metadata }, null, _classExtraInitializers);
        QueueModule = _classThis = _classDescriptor.value;
        if (_metadata) Object.defineProperty(_classThis, Symbol.metadata, { enumerable: true, configurable: true, writable: true, value: _metadata });
        __runInitializers(_classThis, _classExtraInitializers);
    })();
    return QueueModule = _classThis;
}();
exports.QueueModule = QueueModule;
