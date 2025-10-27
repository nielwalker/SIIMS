import { configureStore } from "@reduxjs/toolkit";

import profileReducer from "../containers/Profiles/_redux/profileSlice";
import endorsementLetterReducer from "../containers/Endorsements/_redux/endorsementLetterSlice";
import { persistedEndorsementLetterDetailReducer } from "./persistConfig";
import { FLUSH, REHYDRATE, PAUSE, PERSIST, PURGE, REGISTER } from "redux-persist";

export const store = configureStore({
  reducer: {
    profile: profileReducer,
    endorsementLetter: endorsementLetterReducer,
    endorsementLetterDetail: persistedEndorsementLetterDetailReducer,
  },
  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware({
      serializableCheck: {
        ignoredActions: [FLUSH, REHYDRATE, PAUSE, PERSIST, PURGE, REGISTER],
      },
    }),
});

// store.js
import { persistStore } from "redux-persist"; // Import persistStore

export const persistor = persistStore(store); // Create persistor
