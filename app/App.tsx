import React from 'react';
import { BrowserRouter } from 'react-router';
import AppRoutes from './routes/AppRoutes';

const App: React.FC = (): React.Element => {
  return (
    <BrowserRouter>
      <AppRoutes/>
    </BrowserRouter>
  );
};

export default App;