# Google Identity Services

Web and Android authenticate through one Laravel endpoint:

```text
POST /auth/google
```

Use the same Google **Web OAuth client ID** for the GIS web button, Android's
`serverClientId`, and Laravel's `GOOGLE_CLIENT_ID`. In Google Cloud Console, add
the production site to Authorized JavaScript origins and add
`https://your-domain.example/auth/google` to Authorized redirect URIs.

The Android OAuth client (package name plus SHA-1 certificate fingerprint) must
still be registered in the same Google Cloud project. It identifies the Android
app, while the Web OAuth client ID is the audience of the ID token sent to
Laravel.

## Web

The login and registration pages load `https://accounts.google.com/gsi/client`.
GIS posts these fields directly to the endpoint:

```text
credential=<Google ID token>
g_csrf_token=<GIS CSRF token>
```

Laravel verifies Google's double-submit CSRF token, verifies the ID token, logs
the user into the `web` guard, regenerates the session ID, and redirects to the
appropriate dashboard.

## Android Credential Manager

Use Android Credential Manager and Google's `googleid` library. Configure
`GetGoogleIdOption` with the Web OAuth client ID, not the Android client ID.
Generate a fresh cryptographically random nonce for each attempt, pass it to
Credential Manager, and send the same value beside the ID token so Laravel can
compare it with the signed `nonce` claim:

```kotlin
val googleIdOption = GetGoogleIdOption.Builder()
    .setFilterByAuthorizedAccounts(true)
    .setServerClientId(WEB_CLIENT_ID)
    .setAutoSelectEnabled(true)
    .setNonce(signInNonce)
    .build()

val request = GetCredentialRequest.Builder()
    .addCredentialOption(googleIdOption)
    .build()

val result = credentialManager.getCredential(request, activity)
val credential = result.credential

if (credential is CustomCredential &&
    credential.type == GoogleIdTokenCredential.TYPE_GOOGLE_ID_TOKEN_CREDENTIAL
) {
    val googleCredential = GoogleIdTokenCredential.createFrom(credential.data)
    authApi.google(
        GoogleLoginRequest(
            idToken = googleCredential.idToken,
            nonce = signInNonce,
            platform = "android",
            deviceInstallationId = installationId,
            deviceName = Build.MODEL,
            appVersion = BuildConfig.VERSION_NAME,
        )
    )
}
```

Example Retrofit contract:

```kotlin
data class GoogleLoginRequest(
    @SerializedName("id_token") val idToken: String,
    val nonce: String? = null,
    val platform: String = "android",
    @SerializedName("device_installation_id") val deviceInstallationId: String? = null,
    @SerializedName("device_name") val deviceName: String? = null,
    @SerializedName("app_version") val appVersion: String? = null,
)

interface AuthApi {
    @POST("auth/google")
    suspend fun google(@Body request: GoogleLoginRequest): AuthTokenResponse
}
```

For an explicit branded button, use `GetSignInWithGoogleOption.Builder(WEB_CLIENT_ID)`
with the same nonce instead of `GetGoogleIdOption`; token extraction and the
Laravel request stay the same.

The response contains `access_token`, `refresh_token`, their expiry timestamps,
`session_id`, and the compatibility alias `bearer_token`. On sign-out, revoke the
Laravel session and call `credentialManager.clearCredentialState()`.

If the authorized-account request throws `NoCredentialException`, retry with
`setFilterByAuthorizedAccounts(false)` so a new user can select any Google
account.
