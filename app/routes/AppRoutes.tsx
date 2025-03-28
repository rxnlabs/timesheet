import { Routes, Route } from 'react-router';
import React from 'react';
import Home from '../pages/Home';
import ArchiveLayout from '../layouts/ArchiveLayout';
import ArchiveHome from '../pages/ArchiveHome';
import ArchiveTimesheet from '../components/ArchiveTimesheet';
import { useAppDispatch } from '../hooks';
import { getLogWeeks } from '../slices/shift';

const AppRoutes = () => {
  return (
    <Routes>
      <Route index element={<Home/>} />
      <Route path="archive">
        <Route index element={<ArchiveHome/>} />
        <Route element={<ArchiveLayout/>}>
          <Route path=":year/:week" element={<ArchiveTimesheet/>}/>
        </Route>
      </Route>
    </Routes>
  );
};

export default AppRoutes;