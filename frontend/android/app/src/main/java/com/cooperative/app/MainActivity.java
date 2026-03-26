package com.cooperative.app;

import android.os.Bundle;
import com.getcapacitor.BridgeActivity;
// If using older @capgo versions, you might need this:
// import ee.forgr.biometric.NativeBiometric; 

public class MainActivity extends BridgeActivity {
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        // registerPlugin(NativeBiometric.class); // Uncomment if it still fails
    }
}
