#!/usr/bin/env ts-node
/**
 * Module Generator
 * ----------------
 * Generates all boilerplate files for a new NestJS module following the
 * repository pattern used in this project.
 *
 * Usage:
 *   npm run generate:module <ModuleName>
 *
 * Examples:
 *   npm run generate:module Payment
 *   npm run generate:module BlogPost
 *
 * Generated files for "Payment":
 *   src/payments/schemas/payment.schema.ts
 *   src/payments/repositories/payment.repository.interface.ts
 *   src/payments/repositories/payment.repository.ts
 *   src/payments/dto/create-payment.dto.ts
 *   src/payments/dto/update-payment.dto.ts
 *   src/payments/payments.service.ts
 *   src/payments/payments.controller.ts
 *   src/payments/payments.module.ts
 */

import * as fs from 'fs';
import * as path from 'path';

// ─── Helpers ────────────────────────────────────────────────────────────────

/** "BlogPost" → "blogPost" */
function toCamelCase(str: string): string {
  return str.charAt(0).toLowerCase() + str.slice(1);
}

/** "BlogPost" → "blog-post" */
function toKebabCase(str: string): string {
  return str
    .replace(/([A-Z])/g, (m) => `-${m.toLowerCase()}`)
    .replace(/^-/, '');
}

/** "BlogPost" → "blog_post" */
function toSnakeCase(str: string): string {
  return toKebabCase(str).replace(/-/g, '_');
}

/**
 * Naive English pluralisation (covers the common cases).
 * "Payment" → "payments", "Category" → "categories"
 */
function pluralise(word: string): string {
  if (/[^aeiou]y$/i.test(word)) return word.slice(0, -1) + 'ies';
  if (/(s|x|z|ch|sh)$/i.test(word)) return word + 'es';
  return word + 's';
}

function writeFile(filePath: string, content: string): void {
  if (fs.existsSync(filePath)) {
    console.error(`  ✖  Already exists, skipping: ${filePath}`);
    return;
  }
  fs.mkdirSync(path.dirname(filePath), { recursive: true });
  fs.writeFileSync(filePath, content, 'utf8');
  console.log(`  ✔  Created: ${filePath}`);
}

// ─── Template functions ──────────────────────────────────────────────────────

function schemaTemplate(Pascal: string, camel: string): string {
  return `import { Prop, Schema, SchemaFactory } from '@nestjs/mongoose';
import { Document } from 'mongoose';

export type ${Pascal}Document = ${Pascal} & Document & { createdAt: Date; updatedAt: Date };

@Schema({ timestamps: true })
export class ${Pascal} {
  @Prop({ required: true })
  name: string;
}

export const ${Pascal}Schema = SchemaFactory.createForClass(${Pascal});
`;
}

function repositoryInterfaceTemplate(
  Pascal: string,
  Plurals: string,
  kebab: string,
  SCREAMING: string,
): string {
  return `import { ${Pascal}Document } from '../schemas/${kebab}.schema';
import { IBaseRepository } from '../../common/interfaces/base-repository.interface';

export interface ${Plurals}PaginatedResult {
  data: ${Pascal}Document[];
  total: number;
  page: number;
  lastPage: number;
}

export interface ${Plurals}Filters {
  // Add filter fields here
  status?: number;
}

export const ${SCREAMING}_REPOSITORY = '${SCREAMING}_REPOSITORY';

export interface I${Pascal}Repository extends IBaseRepository<${Pascal}Document> {
  findPaginated(
    page: number,
    limit: number,
    filters?: ${Plurals}Filters,
  ): Promise<${Plurals}PaginatedResult>;
}
`;
}

function repositoryTemplate(
  Pascal: string,
  Plurals: string,
  kebab: string,
  SCREAMING: string,
): string {
  return `import { Injectable } from '@nestjs/common';
import { InjectModel } from '@nestjs/mongoose';
import { FilterQuery, Model } from 'mongoose';
import { ${Pascal}, ${Pascal}Document } from '../schemas/${kebab}.schema';
import {
  I${Pascal}Repository,
  ${Plurals}Filters,
  ${Plurals}PaginatedResult,
} from './${kebab}.repository.interface';

@Injectable()
export class ${Pascal}Repository implements I${Pascal}Repository {
  constructor(
    @InjectModel(${Pascal}.name)
    private readonly ${toCamelCase(Pascal)}Model: Model<${Pascal}Document>,
  ) {}

  async findById(id: string): Promise<${Pascal}Document | null> {
    return this.${toCamelCase(Pascal)}Model.findById(id).exec();
  }

  async findAll(): Promise<${Pascal}Document[]> {
    return this.${toCamelCase(Pascal)}Model.find().sort({ createdAt: -1 }).exec();
  }

  async create(entity: Partial<${Pascal}Document>): Promise<${Pascal}Document> {
    const record = new this.${toCamelCase(Pascal)}Model(entity);
    return record.save();
  }

  async update(
    id: string,
    entity: Partial<${Pascal}Document>,
  ): Promise<${Pascal}Document | null> {
    return this.${toCamelCase(Pascal)}Model
      .findByIdAndUpdate(id, entity, { new: true })
      .exec();
  }

  async delete(id: string): Promise<boolean> {
    const result = await this.${toCamelCase(Pascal)}Model.findByIdAndDelete(id).exec();
    return result !== null;
  }

  async findPaginated(
    page: number,
    limit: number,
    filters: ${Plurals}Filters = {},
  ): Promise<${Plurals}PaginatedResult> {
    const skip = (page - 1) * limit;
    const query: FilterQuery<${Pascal}Document> = {};

    if (filters.status !== undefined) query.status = filters.status;

    const [data, total] = await Promise.all([
      this.${toCamelCase(Pascal)}Model
        .find(query)
        .skip(skip)
        .limit(limit)
        .sort({ createdAt: -1 })
        .exec(),
      this.${toCamelCase(Pascal)}Model.countDocuments(query).exec(),
    ]);

    return { data, total, page, lastPage: Math.ceil(total / limit) };
  }
}
`;
}

function createDtoTemplate(Pascal: string): string {
  return `import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';
import { IsNotEmpty, IsNumber, IsOptional, IsString } from 'class-validator';

export class Create${Pascal}Dto {
  @ApiProperty()
  @IsNotEmpty()
  @IsString()
  name: string;

  @ApiPropertyOptional({ example: 1, default: 1 })
  @IsOptional()
  @IsNumber()
  status?: number;
}
`;
}

function updateDtoTemplate(Pascal: string): string {
  return `import { PartialType } from '@nestjs/swagger';
import { Create${Pascal}Dto } from './create-${toKebabCase(Pascal)}.dto';

export class Update${Pascal}Dto extends PartialType(Create${Pascal}Dto) {}
`;
}

function serviceTemplate(
  Pascal: string,
  plural: string,
  kebabPlural: string,
  kebab: string,
  SCREAMING: string,
): string {
  return `import { Injectable, Inject, NotFoundException } from '@nestjs/common';
import { ${Pascal}Document } from './schemas/${kebab}.schema';
import { Create${Pascal}Dto } from './dto/create-${kebab}.dto';
import { Update${Pascal}Dto } from './dto/update-${kebab}.dto';
import {
  I${Pascal}Repository,
  ${capitalize(plural)}Filters,
  ${capitalize(plural)}PaginatedResult,
  ${SCREAMING}_REPOSITORY,
} from './repositories/${kebab}.repository.interface';

@Injectable()
export class ${capitalize(plural)}Service {
  constructor(
    @Inject(${SCREAMING}_REPOSITORY)
    private readonly ${toCamelCase(Pascal)}Repository: I${Pascal}Repository,
  ) {}

  async create(dto: Create${Pascal}Dto): Promise<${Pascal}Document> {
    return this.${toCamelCase(Pascal)}Repository.create(
      dto as unknown as Partial<${Pascal}Document>,
    );
  }

  async findAll(): Promise<${Pascal}Document[]> {
    return this.${toCamelCase(Pascal)}Repository.findAll();
  }

  async findPaginated(
    page: number,
    limit: number,
    filters: ${capitalize(plural)}Filters = {},
  ): Promise<${capitalize(plural)}PaginatedResult> {
    return this.${toCamelCase(Pascal)}Repository.findPaginated(page, limit, filters);
  }

  async findById(id: string): Promise<${Pascal}Document> {
    const record = await this.${toCamelCase(Pascal)}Repository.findById(id);
    if (!record) {
      throw new NotFoundException(\`${Pascal} #\${id} not found\`);
    }
    return record;
  }

  async update(id: string, dto: Update${Pascal}Dto): Promise<${Pascal}Document> {
    const updated = await this.${toCamelCase(Pascal)}Repository.update(
      id,
      dto as unknown as Partial<${Pascal}Document>,
    );
    if (!updated) {
      throw new NotFoundException(\`${Pascal} #\${id} not found\`);
    }
    return updated;
  }

  async remove(id: string): Promise<void> {
    const deleted = await this.${toCamelCase(Pascal)}Repository.delete(id);
    if (!deleted) {
      throw new NotFoundException(\`${Pascal} #\${id} not found\`);
    }
  }
}
`;
}

function controllerTemplate(
  Pascal: string,
  plural: string,
  kebabPlural: string,
  kebab: string,
): string {
  return `import {
  Controller,
  Get,
  Post,
  Put,
  Delete,
  Body,
  Param,
  Query,
  UseGuards,
  HttpCode,
  HttpStatus,
} from '@nestjs/common';
import {
  ApiTags,
  ApiBearerAuth,
  ApiOperation,
  ApiResponse,
  ApiQuery,
  ApiParam,
} from '@nestjs/swagger';
import { JwtAuthGuard } from '../auth/jwt-auth.guard';
import { ${capitalize(plural)}Service } from './${kebabPlural}.service';
import { Create${Pascal}Dto } from './dto/create-${kebab}.dto';
import { Update${Pascal}Dto } from './dto/update-${kebab}.dto';

@ApiTags('${kebabPlural}')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard)
@Controller('${kebabPlural}')
export class ${capitalize(plural)}Controller {
  constructor(private readonly ${toCamelCase(capitalize(plural))}Service: ${capitalize(plural)}Service) {}

  @Post()
  @ApiOperation({ summary: 'Create a ${kebab}' })
  @ApiResponse({ status: 201, description: '${Pascal} created successfully' })
  async create(@Body() dto: Create${Pascal}Dto) {
    return this.${toCamelCase(capitalize(plural))}Service.create(dto);
  }

  @Get()
  @ApiOperation({ summary: 'List ${kebabPlural} with pagination' })
  @ApiQuery({ name: 'page', required: false, type: Number, example: 1 })
  @ApiQuery({ name: 'limit', required: false, type: Number, example: 20 })
  async findAll(
    @Query('page') page = 1,
    @Query('limit') limit = 20,
  ) {
    return this.${toCamelCase(capitalize(plural))}Service.findPaginated(+page, +limit);
  }

  @Get(':id')
  @ApiOperation({ summary: 'Get a ${kebab} by ID' })
  @ApiParam({ name: 'id', description: '${Pascal} MongoDB ObjectId' })
  @ApiResponse({ status: 200, description: '${Pascal} found' })
  @ApiResponse({ status: 404, description: '${Pascal} not found' })
  async findOne(@Param('id') id: string) {
    return this.${toCamelCase(capitalize(plural))}Service.findById(id);
  }

  @Put(':id')
  @ApiOperation({ summary: 'Update a ${kebab}' })
  @ApiParam({ name: 'id', description: '${Pascal} MongoDB ObjectId' })
  @ApiResponse({ status: 200, description: '${Pascal} updated successfully' })
  @ApiResponse({ status: 404, description: '${Pascal} not found' })
  async update(@Param('id') id: string, @Body() dto: Update${Pascal}Dto) {
    return this.${toCamelCase(capitalize(plural))}Service.update(id, dto);
  }

  @Delete(':id')
  @HttpCode(HttpStatus.NO_CONTENT)
  @ApiOperation({ summary: 'Delete a ${kebab}' })
  @ApiParam({ name: 'id', description: '${Pascal} MongoDB ObjectId' })
  @ApiResponse({ status: 204, description: '${Pascal} deleted successfully' })
  @ApiResponse({ status: 404, description: '${Pascal} not found' })
  async remove(@Param('id') id: string) {
    return this.${toCamelCase(capitalize(plural))}Service.remove(id);
  }
}
`;
}

function moduleTemplate(
  Pascal: string,
  plural: string,
  kebabPlural: string,
  kebab: string,
  SCREAMING: string,
): string {
  return `import { Module } from '@nestjs/common';
import { MongooseModule } from '@nestjs/mongoose';
import { ${Pascal}, ${Pascal}Schema } from './schemas/${kebab}.schema';
import { ${Pascal}Repository } from './repositories/${kebab}.repository';
import { ${SCREAMING}_REPOSITORY } from './repositories/${kebab}.repository.interface';
import { ${capitalize(plural)}Service } from './${kebabPlural}.service';
import { ${capitalize(plural)}Controller } from './${kebabPlural}.controller';

@Module({
  imports: [
    MongooseModule.forFeature([{ name: ${Pascal}.name, schema: ${Pascal}Schema }]),
  ],
  providers: [
    ${Pascal}Repository,
    { provide: ${SCREAMING}_REPOSITORY, useClass: ${Pascal}Repository },
    ${capitalize(plural)}Service,
  ],
  controllers: [${capitalize(plural)}Controller],
  exports: [${capitalize(plural)}Service],
})
export class ${capitalize(plural)}Module {}
`;
}

// ─── Utilities ───────────────────────────────────────────────────────────────

function capitalize(str: string): string {
  return str.charAt(0).toUpperCase() + str.slice(1);
}

// ─── Main ────────────────────────────────────────────────────────────────────

function main(): void {
  const arg = process.argv[2];

  if (!arg || arg === '--help' || arg === '-h') {
    console.log(`
  Usage: npm run generate:module <ModuleName>

  ModuleName must be PascalCase (e.g. Payment, BlogPost, ProductCategory)

  Examples:
    npm run generate:module Payment
    npm run generate:module BlogPost

  Generated files for "Payment":
    src/payments/schemas/payment.schema.ts
    src/payments/repositories/payment.repository.interface.ts
    src/payments/repositories/payment.repository.ts
    src/payments/dto/create-payment.dto.ts
    src/payments/dto/update-payment.dto.ts
    src/payments/payments.service.ts
    src/payments/payments.controller.ts
    src/payments/payments.module.ts

  After generation, register the module in src/app.module.ts:
    import { PaymentsModule } from './payments/payments.module';
    // add PaymentsModule to the @Module imports array
`);
    process.exit(0);
  }

  // Validate input — must start with uppercase letter, only word chars
  if (!/^[A-Z][A-Za-z0-9]*$/.test(arg)) {
    console.error(
      `\n  ✖ Error: ModuleName must be PascalCase (e.g. Payment, BlogPost).\n` +
        `    Received: "${arg}"\n`,
    );
    process.exit(1);
  }

  const Pascal = arg; // e.g. "BlogPost"
  const camel = toCamelCase(Pascal); // "blogPost"
  const kebab = toKebabCase(Pascal); // "blog-post"
  const snake = toSnakeCase(Pascal); // "blog_post"
  const pluralPascal = pluralise(Pascal); // "BlogPosts"
  const plural = toCamelCase(pluralPascal); // "blogPosts"
  const kebabPlural = toKebabCase(pluralPascal); // "blog-posts"
  const SCREAMING = snake.toUpperCase(); // "BLOG_POST"

  console.log(`\n  Generating module: ${Pascal}`);
  console.log(`  Plural:            ${pluralPascal}`);
  console.log(`  Route path:        /${kebabPlural}`);
  console.log(`  Output dir:        src/${kebabPlural}/\n`);

  const srcRoot = path.resolve(__dirname, '..', 'src');
  const moduleDir = path.join(srcRoot, kebabPlural);

  writeFile(
    path.join(moduleDir, 'schemas', `${kebab}.schema.ts`),
    schemaTemplate(Pascal, camel),
  );

  writeFile(
    path.join(moduleDir, 'repositories', `${kebab}.repository.interface.ts`),
    repositoryInterfaceTemplate(Pascal, capitalize(plural), kebab, SCREAMING),
  );

  writeFile(
    path.join(moduleDir, 'repositories', `${kebab}.repository.ts`),
    repositoryTemplate(Pascal, capitalize(plural), kebab, SCREAMING),
  );

  writeFile(
    path.join(moduleDir, 'dto', `create-${kebab}.dto.ts`),
    createDtoTemplate(Pascal),
  );

  writeFile(
    path.join(moduleDir, 'dto', `update-${kebab}.dto.ts`),
    updateDtoTemplate(Pascal),
  );

  writeFile(
    path.join(moduleDir, `${kebabPlural}.service.ts`),
    serviceTemplate(Pascal, plural, kebabPlural, kebab, SCREAMING),
  );

  writeFile(
    path.join(moduleDir, `${kebabPlural}.controller.ts`),
    controllerTemplate(Pascal, plural, kebabPlural, kebab),
  );

  writeFile(
    path.join(moduleDir, `${kebabPlural}.module.ts`),
    moduleTemplate(Pascal, plural, kebabPlural, kebab, SCREAMING),
  );

  console.log(`
  ✅ Module "${Pascal}" generated successfully!

  Next step — register it in src/app.module.ts:

    import { ${capitalize(plural)}Module } from './${kebabPlural}/${kebabPlural}.module';

    @Module({
      imports: [
        ...
        ${capitalize(plural)}Module,   // ← add this
      ],
    })
    export class AppModule {}
`);
}

main();
