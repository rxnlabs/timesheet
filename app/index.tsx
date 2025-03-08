import React from 'react';
import ReactDOM from 'react-dom/client';
import App from './App';
const ROOT_ELEMENT_ID = 'root';
const root = ReactDOM.createRoot(document.getElementById(ROOT_ELEMENT_ID));

root.render(
  <React.StrictMode>
    <App/>
  </React.StrictMode>
);
