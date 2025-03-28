import type { ReactNode } from 'react';
import React, { Fragment } from 'react';
import { Outlet, useLocation } from 'react-router';
import Header from '../components/Header';
import Footer from '../components/Footer';

const ArchiveLayout = ({ children }: {children: ReactNode}): React.JSX.Element => {
  // Force a remount when the dynamic segment changes by using the key to trigger the component. Kept having issue where clicking a paginated next week and previous week would not reload the table.
  // see https://github.com/remix-run/react-router/issues/8786#issuecomment-1635276597
  const location = useLocation();
  return (
    <Fragment>
      <Header/>
      <main>
        <Outlet key={location.key}/>
        {children}
      </main>
      <Footer/>
    </Fragment>
  );
};

export default ArchiveLayout;