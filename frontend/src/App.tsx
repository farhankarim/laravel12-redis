import { Admin, Resource } from 'react-admin';
import PeopleIcon from '@mui/icons-material/People';
import SwapHorizIcon from '@mui/icons-material/SwapHoriz';
import { dataProvider } from './providers/dataProvider';
import { authProvider } from './providers/authProvider';
import { UserList } from './resources/users';
import {
  TransactionList,
  TransactionShow,
  TransactionCreate,
  TransactionEdit,
} from './resources/transactions';
import { Dashboard } from './resources/dashboard';

export default function App() {
  return (
    <Admin
      dataProvider={dataProvider}
      authProvider={authProvider}
      dashboard={Dashboard}
      title="Laravel12 Redis Admin"
    >
      <Resource
        name="users"
        list={UserList}
        icon={PeopleIcon}
        options={{ label: 'Users' }}
      />
      <Resource
        name="transactions"
        list={TransactionList}
        show={TransactionShow}
        create={TransactionCreate}
        edit={TransactionEdit}
        icon={SwapHorizIcon}
        options={{ label: 'Transactions' }}
      />
    </Admin>
  );
}
