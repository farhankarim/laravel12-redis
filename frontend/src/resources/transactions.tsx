import {
  List,
  Datagrid,
  TextField,
  DateField,
  NumberField,
  FunctionField,
  Show,
  SimpleShowLayout,
  Create,
  SimpleForm,
  TextInput,
  DateInput,
  SelectInput,
  NumberInput,
  Edit,
  EditButton,
  DeleteButton,
} from 'react-admin';

const typeChoices = [
  { id: 'loan taken', name: 'Loan Taken' },
  { id: 'donation', name: 'Donation' },
  { id: 'loan returned', name: 'Loan Returned' },
];

export const TransactionList = () => (
  <List perPage={20} sort={{ field: 'createdAt', order: 'DESC' }}>
    <Datagrid rowClick="show">
      <TextField source="id" label="ID" sortable={false} />
      <TextField source="participantId" label="Participant ID" sortable={false} />
      <DateField source="date" />
      <FunctionField
        source="type"
        label="Type"
        render={(record: { type?: string }) => {
          const map: Record<string, string> = {
            'loan taken': '🏦 Loan Taken',
            donation: '🎁 Donation',
            'loan returned': '✅ Loan Returned',
          };
          return record.type ? map[record.type] ?? record.type : '—';
        }}
      />
      <TextField source="amount" />
      <TextField source="paymentStatus" label="Payment Status" />
      <DateField source="expectedReturnDate" label="Expected Return" />
      <NumberField source="repaymentAmount" label="Repayment (₹)" />
      <TextField source="tenure" />
      <EditButton />
      <DeleteButton />
    </Datagrid>
  </List>
);

export const TransactionShow = () => (
  <Show>
    <SimpleShowLayout>
      <TextField source="id" label="ID" />
      <TextField source="participantId" label="Participant ID" />
      <DateField source="date" />
      <TextField source="type" label="Type" />
      <TextField source="amount" />
      <NumberField source="status" />
      <DateField source="expectedReturnDate" label="Expected Return Date" />
      <NumberField source="repaymentAmount" label="Repayment Amount" />
      <TextField source="tenure" />
      <TextField source="paymentStatus" label="Payment Status" />
      <DateField source="createdAt" showTime />
    </SimpleShowLayout>
  </Show>
);

const TransactionForm = () => (
  <SimpleForm>
    <TextInput source="participantId" label="Participant ID" required />
    <DateInput source="date" />
    <SelectInput source="type" choices={typeChoices} />
    <TextInput source="amount" />
    <NumberInput source="status" defaultValue={1} />
    <DateInput source="expectedReturnDate" label="Expected Return Date" />
    <NumberInput source="repaymentAmount" label="Repayment Amount" />
    <TextInput source="tenure" />
    <TextInput source="paymentStatus" label="Payment Status" />
  </SimpleForm>
);

export const TransactionCreate = () => (
  <Create>
    <TransactionForm />
  </Create>
);

export const TransactionEdit = () => (
  <Edit>
    <TransactionForm />
  </Edit>
);
