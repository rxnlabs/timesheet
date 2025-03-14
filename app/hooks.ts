import { useDispatch, useSelector } from 'react-redux';
import type { AppDispatch, RootState } from './stores/ShiftStore';

// Use this instead of regular useDispatch and useSelector to make sure we are dispatching actions and selectors from our Redux store
export const useAppDispatch = useDispatch.withTypes<AppDispatch>();
export const useAppSelector = useSelector.withTypes<RootState>();
