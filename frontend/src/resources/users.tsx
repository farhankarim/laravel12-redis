import {
  List,
  Datagrid,
  TextField,
  EmailField,
  DateField,
  FunctionField,
} from 'react-admin';

export const UserList = () => (
  <List perPage={20} sort={{ field: 'createdAt', order: 'DESC' }}>
    <Datagrid bulkActionButtons={false} rowClick={false}>
      <TextField source="id" label="ID" sortable={false} />
      <TextField source="name" />
      <EmailField source="email" />
      <FunctionField
        source="emailVerifiedAt"
        label="Email Verified"
        render={(record: { emailVerifiedAt?: string | null }) =>
          record.emailVerifiedAt ? '✅ Verified' : '⏳ Pending'
        }
      />
      <DateField source="createdAt" label="Registered" showTime />
    </Datagrid>
  </List>
);
