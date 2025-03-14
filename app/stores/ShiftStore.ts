import type { Action, ThunkAction } from '@reduxjs/toolkit';
import { configureStore } from '@reduxjs/toolkit';
import shiftReducer from '../slices/shift';

/**
 * Redux store configured with a single reducer.
 *
 * The store is created using `configureStore` to simplify setup
 * of the Redux store. The reducer property is an object containing a
 * slice reducer, `shiftReducer`, under the key `shift`. This
 * reducer manages the state related to the "shift" feature of the application.
 */
export const store = configureStore({
  reducer: {
    shift: shiftReducer,
  },
});

export default store;

/**
 * The type of the application's Redux store.
 *
 * This is derived directly from the `store` object
 * to ensure accurate typing when working with the Redux store.
 */
export type AppStore = typeof store;

/**
 * The type for the root state of the Redux store.
 *
 * This represents the entire state managed by the Redux store
 * and is used throughout the app to ensure consistent state typing.
 */
export type RootState = ReturnType<AppStore['getState']>;

/**
 * The type for the Redux store's dispatch function.
 *
 * Use this to enforce type safety when dispatching actions in the app.
 */
export type AppDispatch = AppStore['dispatch'];

/**
 * A utility type for creating Redux thunk actions.
 *
 * @template ThunkReturnType - The return type of the thunk action (defaults to void).
 *
 * This is used for typing asynchronous actions that interact with the Redux store.
 */
export type AppThunk<ThunkReturnType = void> = ThunkAction<ThunkReturnType, RootState, unknown, Action>